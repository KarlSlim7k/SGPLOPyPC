<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/OcdsRepository.php';

/**
 * OcdsService — construye estructuras conforme al Open Contracting Data Standard 1.1.
 *
 * Mapping detallado en docs/fases/mejoras/FASE3_OCDS_MAPPING.md
 */
class OcdsService {
    private OcdsRepository $repo;

    private const OCDS_VERSION = '1.1';
    private const OCID_PREFIX = 'ocds-sgplopypc-';
    private const CURRENCY = 'MXN';
    private const COUNTRY = 'Mexico';
    private const PUBLISHER_NAME = 'SGPLOPyPC';
    private const PUBLISHER_URI = 'https://sgplopypc.up.railway.app';
    private const LICENSE = 'https://creativecommons.org/licenses/by/4.0/';
    private const PUBLICATION_POLICY = 'https://sgplopypc.up.railway.app/legal/datos-abiertos';

    public function __construct() {
        $this->repo = new OcdsRepository();
    }

    /**
     * @return array{releases: array, pagination: array}
     */
    public function listReleases(array $filters, int $page, int $limit): array {
        $data = $this->repo->findReleasesData($filters, $page, $limit);
        $releases = [];
        foreach ($data['licitaciones'] as $lic) {
            $actors = $this->repo->findRelatedActors((int) $lic['id_licitacion']);
            $releases[] = $this->buildRelease($lic, $actors);
        }
        return [
            'releases' => $releases,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $data['total'],
                'pages' => (int) ceil($data['total'] / max(1, $limit)),
            ],
        ];
    }

    /**
     * Devuelve el release de una licitación específica por OCID o por número de licitación.
     */
    public function getReleaseByOcid(string $ocidOrNumero): ?array {
        $numero = str_starts_with($ocidOrNumero, self::OCID_PREFIX)
            ? substr($ocidOrNumero, strlen(self::OCID_PREFIX))
            : $ocidOrNumero;
        $lic = $this->repo->findByNumeroLicitacion($numero);
        if (!$lic) return null;
        $actors = $this->repo->findRelatedActors((int) $lic['id_licitacion']);
        return $this->buildRelease($lic, $actors);
    }

    /**
     * Construye un Release Package OCDS 1.1 con todas las licitaciones publicables.
     * Limita a 'maxReleases' para evitar respuestas gigantes.
     */
    public function buildReleasePackage(int $maxReleases = 200): array {
        $data = $this->repo->findReleasesData([], 1, $maxReleases);
        $releases = [];
        foreach ($data['licitaciones'] as $lic) {
            $actors = $this->repo->findRelatedActors((int) $lic['id_licitacion']);
            $releases[] = $this->buildRelease($lic, $actors);
        }
        return [
            'uri' => self::PUBLISHER_URI . '/api/v1/datos-abiertos/release-package',
            'version' => self::OCDS_VERSION,
            'extensions' => [],
            'publishedDate' => $this->iso8601(),
            'publisher' => [
                'name' => self::PUBLISHER_NAME,
                'scheme' => 'MX-GOB',
                'uid' => 'SGPLOPyPC',
                'uri' => self::PUBLISHER_URI,
            ],
            'license' => self::LICENSE,
            'publicationPolicy' => self::PUBLICATION_POLICY,
            'releases' => $releases,
        ];
    }

    // ----- builders internos -----

    private function buildRelease(array $lic, array $actors): array {
        $ocid = self::OCID_PREFIX . $lic['numero_licitacion'];
        $tags = $this->derivedTags($lic, $actors);
        $tender = $this->buildTender($lic);
        $awards = [];
        $contracts = [];

        if (!empty($actors['contrato'])) {
            $awards[] = $this->buildAward($lic, $actors['contrato']);
            $contracts[] = $this->buildContract($actors['contrato']);
        }

        $parties = $this->buildParties($lic, $actors);

        $release = [
            'ocid' => $ocid,
            'id' => $ocid . '-r' . time(),
            'date' => $this->iso8601($lic['fecha_actualizacion'] ?? null),
            'tag' => $tags,
            'initiationType' => 'tender',
            'language' => 'es',
            'parties' => $parties,
            'buyer' => [
                'id' => 'buyer-' . $lic['id_dependencia'],
                'name' => $lic['dependencia_nombre'],
            ],
            'tender' => $tender,
        ];

        if (!empty($awards)) {
            $release['awards'] = $awards;
        }
        if (!empty($contracts)) {
            $release['contracts'] = $contracts;
        }

        return $release;
    }

    private function derivedTags(array $lic, array $actors): array {
        $estado = (string) ($lic['estado_proceso'] ?? '');
        $hasContract = !empty($actors['contrato']);

        switch ($estado) {
            case 'BORRADOR':
                return ['planning'];
            case 'PUBLICADA':
            case 'EN_ACLARACIONES':
            case 'RECEPCION_PROPUESTAS':
            case 'EN_EVALUACION':
                return ['tender'];
            case 'ADJUDICADA':
                return $hasContract ? ['tender', 'award', 'contract'] : ['tender', 'award'];
            case 'DESIERTA':
            case 'CANCELADA':
                return ['tender', 'tenderUpdate'];
            default:
                return ['tender'];
        }
    }

    private function buildTender(array $lic): array {
        $tender = [
            'id' => (string) $lic['numero_licitacion'],
            'title' => $this->truncate((string) ($lic['descripcion_proyecto'] ?? ''), 150),
            'description' => (string) ($lic['descripcion_proyecto'] ?? ''),
            'status' => $this->derivedTenderStatus((string) $lic['estado_proceso']),
            'procurementMethod' => $this->procurementMethod((string) $lic['tipo_procedimiento']),
            'procurementMethodDetails' => $this->humanProcedimiento((string) $lic['tipo_procedimiento']),
            'value' => [
                'amount' => $this->safeFloat($lic['presupuesto_estimado']),
                'currency' => self::CURRENCY,
            ],
            'procuringEntity' => [
                'id' => 'buyer-' . $lic['id_dependencia'],
                'name' => $lic['dependencia_nombre'],
            ],
            'items' => [
                [
                    'id' => 'item-1',
                    'description' => (string) ($lic['descripcion_proyecto'] ?? ''),
                    'classification' => [
                        'scheme' => 'CPV',
                        'id' => '45000000',
                        'description' => 'Trabajos de construcción y obra pública',
                    ],
                    'quantity' => 1,
                    'unit' => [
                        'name' => 'global',
                        'value' => [
                            'amount' => $this->safeFloat($lic['presupuesto_estimado']),
                            'currency' => self::CURRENCY,
                        ],
                    ],
                ],
            ],
        ];

        if (!empty($lic['ubicacion_proyecto'])) {
            $tender['mainProcurementCategory'] = 'works';
        }

        // Períodos
        $tenderPeriod = [];
        if (!empty($lic['fp_recepcion'])) {
            $tenderPeriod['startDate'] = $this->iso8601($lic['fp_recepcion']);
        }
        if (!empty($lic['fp_apertura'])) {
            $tenderPeriod['endDate'] = $this->iso8601($lic['fp_apertura']);
        }
        if (!empty($tenderPeriod)) {
            $tender['tenderPeriod'] = $tenderPeriod;
        }

        if (!empty($lic['fp_junta'])) {
            $tender['enquiryPeriod'] = ['endDate' => $this->iso8601($lic['fp_junta'])];
        }
        if (!empty($lic['fp_fallo'])) {
            $tender['awardPeriod'] = ['endDate' => $this->iso8601($lic['fp_fallo'])];
        }

        return $tender;
    }

    private function buildAward(array $lic, array $contrato): array {
        return [
            'id' => 'award-' . $contrato['id_contrato'],
            'title' => $this->truncate('Adjudicación: ' . ($lic['descripcion_proyecto'] ?? ''), 150),
            'status' => $this->derivedAwardStatus((string) $contrato['estatus']),
            'date' => $this->iso8601($contrato['fecha_adjudicacion'] ?? null),
            'value' => [
                'amount' => $this->safeFloat($contrato['monto_contrato']),
                'currency' => self::CURRENCY,
            ],
            'suppliers' => [
                [
                    'id' => 'supplier-' . $contrato['id_proveedor'],
                    'name' => (string) ($contrato['proveedor_nombre'] ?? ''),
                ],
            ],
            'contractPeriod' => $this->buildPeriod($contrato['fecha_inicio'] ?? null, $contrato['fecha_fin'] ?? null),
        ];
    }

    private function buildContract(array $contrato): array {
        $contract = [
            'id' => 'contract-' . $contrato['id_contrato'],
            'awardID' => 'award-' . $contrato['id_contrato'],
            'title' => (string) ($contrato['numero_contrato'] ?? ''),
            'status' => $this->derivedContractStatus((string) $contrato['estatus']),
            'value' => [
                'amount' => $this->safeFloat($contrato['monto_contrato']),
                'currency' => self::CURRENCY,
            ],
            'period' => $this->buildPeriod($contrato['fecha_inicio'] ?? null, $contrato['fecha_fin'] ?? null),
        ];

        if (!empty($contrato['fecha_firma_proveedor'])) {
            $contract['dateSigned'] = $this->iso8601($contrato['fecha_firma_proveedor']);
        }

        return $contract;
    }

    private function buildParties(array $lic, array $actors): array {
        $parties = [];

        // Buyer / procuringEntity (siempre)
        $parties[] = [
            'id' => 'buyer-' . $lic['id_dependencia'],
            'name' => (string) $lic['dependencia_nombre'],
            'roles' => ['buyer', 'procuringEntity'],
        ];

        // Suppliers / tenderers (de participación + contrato)
        $idsAgregados = [];
        $idAdjudicado = $actors['contrato']['id_proveedor'] ?? null;

        foreach ($actors['participantes'] as $p) {
            $idProv = (int) $p['id_proveedor'];
            $roles = ['tenderer'];
            if ($idAdjudicado !== null && (int) $idAdjudicado === $idProv) {
                $roles[] = 'supplier';
            }
            $parties[] = $this->buildSupplierParty([
                'id_proveedor' => $idProv,
                'nombre_empresa' => $p['nombre_empresa'],
                'representante_legal' => $p['representante_legal'] ?? null,
                'registro_fiscal' => $p['registro_fiscal'] ?? null,
                'domicilio' => $p['domicilio'] ?? null,
                'telefono' => $p['telefono'] ?? null,
                'contacto_email' => $p['contacto_email'] ?? null,
            ], $roles);
            $idsAgregados[$idProv] = true;
        }

        // Si el adjudicado no estaba en la lista de participantes, agregarlo
        if ($idAdjudicado !== null && empty($idsAgregados[$idAdjudicado])) {
            $c = $actors['contrato'];
            $parties[] = $this->buildSupplierParty([
                'id_proveedor' => (int) $c['id_proveedor'],
                'nombre_empresa' => $c['proveedor_nombre'] ?? '',
                'representante_legal' => $c['proveedor_representante'] ?? null,
                'registro_fiscal' => $c['proveedor_rfc'] ?? null,
                'domicilio' => $c['proveedor_domicilio'] ?? null,
                'telefono' => $c['proveedor_telefono'] ?? null,
                'contacto_email' => $c['proveedor_email'] ?? null,
            ], ['supplier']);
        }

        return $parties;
    }

    private function buildSupplierParty(array $p, array $roles): array {
        $party = [
            'id' => 'supplier-' . $p['id_proveedor'],
            'name' => (string) $p['nombre_empresa'],
            'roles' => $roles,
        ];
        if (!empty($p['registro_fiscal'])) {
            $party['identifier'] = [
                'scheme' => 'MX-RFC',
                'id' => $p['registro_fiscal'],
                'legalName' => (string) $p['nombre_empresa'],
            ];
        }
        $address = [];
        if (!empty($p['domicilio'])) $address['streetAddress'] = $p['domicilio'];
        $address['countryName'] = self::COUNTRY;
        $party['address'] = $address;

        $contact = [];
        if (!empty($p['representante_legal'])) $contact['name'] = $p['representante_legal'];
        if (!empty($p['telefono'])) $contact['telephone'] = $p['telefono'];
        if (!empty($p['contacto_email'])) $contact['email'] = $p['contacto_email'];
        if (!empty($contact)) {
            $party['contactPoint'] = $contact;
        }

        return $party;
    }

    // ----- helpers -----

    private function derivedTenderStatus(string $estado): string {
        return match ($estado) {
            'BORRADOR' => 'planning',
            'PUBLICADA', 'EN_ACLARACIONES', 'RECEPCION_PROPUESTAS', 'EN_EVALUACION' => 'active',
            'ADJUDICADA' => 'complete',
            'DESIERTA' => 'unsuccessful',
            'CANCELADA' => 'cancelled',
            default => 'active',
        };
    }

    private function derivedAwardStatus(string $estatusContrato): string {
        return match ($estatusContrato) {
            'EN_FORMALIZACION', 'VIGENTE', 'EN_EJECUCION', 'CONCLUIDO' => 'active',
            'RESCINDIDO' => 'cancelled',
            default => 'active',
        };
    }

    private function derivedContractStatus(string $estatusContrato): string {
        return match ($estatusContrato) {
            'EN_FORMALIZACION' => 'pending',
            'VIGENTE', 'EN_EJECUCION' => 'active',
            'CONCLUIDO' => 'terminated',
            'RESCINDIDO' => 'cancelled',
            default => 'pending',
        };
    }

    private function procurementMethod(string $tipo): string {
        return match ($tipo) {
            'LICITACION_PUBLICA' => 'open',
            'INVITACION_RESTRINGIDA' => 'selective',
            'ADJUDICACION_DIRECTA' => 'direct',
            default => 'open',
        };
    }

    private function humanProcedimiento(string $tipo): string {
        return match ($tipo) {
            'LICITACION_PUBLICA' => 'Licitación Pública',
            'INVITACION_RESTRINGIDA' => 'Invitación a cuando menos tres personas',
            'ADJUDICACION_DIRECTA' => 'Adjudicación Directa',
            default => $tipo,
        };
    }

    private function buildPeriod(?string $start, ?string $end): array {
        $period = [];
        if (!empty($start)) $period['startDate'] = $this->iso8601($start);
        if (!empty($end)) $period['endDate'] = $this->iso8601($end);
        return $period;
    }

    private function iso8601(?string $datetime = null): string {
        if ($datetime === null) {
            return gmdate('Y-m-d\TH:i:s\Z');
        }
        $ts = strtotime($datetime);
        if ($ts === false) {
            return gmdate('Y-m-d\TH:i:s\Z');
        }
        return gmdate('Y-m-d\TH:i:s\Z', $ts);
    }

    private function safeFloat(mixed $v): float {
        return round((float) ($v ?? 0), 2);
    }

    private function truncate(string $s, int $maxLen): string {
        if (mb_strlen($s) <= $maxLen) return $s;
        return mb_substr($s, 0, $maxLen - 3) . '...';
    }
}
