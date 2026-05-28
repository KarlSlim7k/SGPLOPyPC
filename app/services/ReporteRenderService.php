<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/PlantillaRepository.php';
require_once __DIR__ . '/../repositories/LicitacionRepository.php';
require_once __DIR__ . '/../repositories/ContratoRepository.php';
require_once __DIR__ . '/../repositories/ParticipacionRepository.php';
require_once __DIR__ . '/../helpers/audit.php';

// Composer autoload (Dompdf, PHPWord)
$autoload = __DIR__ . '/../../vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

/**
 * Renderiza una plantilla (HTML con placeholders {{variable}}) a PDF, DOCX o Markdown,
 * inyectando datos derivados de una entidad (licitación, contrato).
 */
class ReporteRenderService {
    private PlantillaRepository $plantillaRepo;
    private LicitacionRepository $licitacionRepo;
    private ContratoRepository $contratoRepo;
    private ParticipacionRepository $participacionRepo;

    private const FORMATOS_VALIDOS = ['pdf', 'docx', 'md'];
    private const ENTIDADES_VALIDAS = ['licitacion', 'contrato'];

    public function __construct() {
        $this->plantillaRepo = new PlantillaRepository();
        $this->licitacionRepo = new LicitacionRepository();
        $this->contratoRepo = new ContratoRepository();
        $this->participacionRepo = new ParticipacionRepository();
    }

    /**
     * Renderiza un reporte.
     *
     * @param int   $idPlantilla
     * @param string $entidad      'licitacion' | 'contrato'
     * @param int   $idEntidad
     * @param string $formato      'pdf' | 'docx' | 'md'
     * @param array $parametros   Variables extra que sobrescriben/complementan las derivadas (nombre→valor)
     * @return array{ok: bool, content?: string, mime?: string, filename?: string,
     *               errors?: array, status?: int}
     */
    public function render(int $idPlantilla, string $entidad, int $idEntidad, string $formato, array $parametros = []): array {
        if (!in_array($formato, self::FORMATOS_VALIDOS, true)) {
            return ['ok' => false, 'errors' => ['Formato inválido. Permitidos: pdf, docx, md'], 'status' => 422];
        }
        if (!in_array($entidad, self::ENTIDADES_VALIDAS, true)) {
            return ['ok' => false, 'errors' => ['Entidad inválida. Permitidos: licitacion, contrato'], 'status' => 422];
        }

        $plantilla = $this->plantillaRepo->findById($idPlantilla, true);
        if (!$plantilla) {
            return ['ok' => false, 'errors' => ['Plantilla no encontrada.'], 'status' => 404];
        }
        if ((int) $plantilla['activa'] !== 1) {
            return ['ok' => false, 'errors' => ['La plantilla está inactiva.'], 'status' => 409];
        }

        // Derivar variables de la entidad
        $variables = $this->buildVariables($entidad, $idEntidad);
        if ($variables === null) {
            return ['ok' => false, 'errors' => ['Entidad no encontrada.'], 'status' => 404];
        }

        // Sobrescribir/agregar parámetros explícitos del usuario (sanitizados)
        foreach ($parametros as $k => $v) {
            if (is_string($k) && preg_match('/^[a-zA-Z_][a-zA-Z0-9_]{0,80}$/', $k) === 1 && (is_string($v) || is_numeric($v))) {
                $variables[$k] = (string) $v;
            }
        }

        // Reemplazar placeholders
        $html = $this->replacePlaceholders((string) $plantilla['contenido_html'], $variables);

        // Generar archivo según formato
        $baseFilename = $this->buildFilename($plantilla['nombre'], $entidad, $idEntidad, $formato);

        switch ($formato) {
            case 'pdf':
                $bin = $this->renderPdf($html);
                if ($bin === null) {
                    return ['ok' => false, 'errors' => ['No se pudo generar el PDF.'], 'status' => 500];
                }
                return ['ok' => true, 'content' => $bin, 'mime' => 'application/pdf', 'filename' => $baseFilename];

            case 'docx':
                $bin = $this->renderDocx($html, $plantilla['nombre']);
                if ($bin === null) {
                    return ['ok' => false, 'errors' => ['No se pudo generar el DOCX.'], 'status' => 500];
                }
                return [
                    'ok' => true,
                    'content' => $bin,
                    'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'filename' => $baseFilename,
                ];

            case 'md':
                $md = $this->renderMarkdown($html, $plantilla, $variables);
                return ['ok' => true, 'content' => $md, 'mime' => 'text/markdown; charset=utf-8', 'filename' => $baseFilename];
        }

        return ['ok' => false, 'errors' => ['Formato no soportado.'], 'status' => 500];
    }

    // ----- builders por entidad -----

    /**
     * Construye un mapa de variables disponibles para la plantilla a partir de la entidad.
     * Si la entidad no existe, devuelve null.
     */
    private function buildVariables(string $entidad, int $id): ?array {
        $vars = [
            'fecha_emision' => date('Y-m-d H:i'),
            'id_licitacion' => '',
            'id_contrato' => '',
        ];

        if ($entidad === 'licitacion') {
            $lic = $this->licitacionRepo->findById($id);
            if (!$lic) return null;
            $vars = array_merge($vars, [
                'id_licitacion' => (string) $lic['id_licitacion'],
                'numero_licitacion' => (string) ($lic['numero_licitacion'] ?? ''),
                'tipo_procedimiento' => $this->humanProcedimiento((string) ($lic['tipo_procedimiento'] ?? '')),
                'descripcion_proyecto' => (string) ($lic['descripcion_proyecto'] ?? ''),
                'presupuesto_estimado' => $this->formatMoney($lic['presupuesto_estimado'] ?? 0),
                'ubicacion_proyecto' => (string) ($lic['ubicacion_proyecto'] ?? ''),
                'estado_proceso' => (string) ($lic['estado_proceso'] ?? ''),
                'fecha_creacion' => (string) ($lic['fecha_creacion'] ?? ''),
                'fecha_actualizacion' => (string) ($lic['fecha_actualizacion'] ?? ''),
                'dependencia_nombre' => (string) ($lic['dependencia_nombre'] ?? ''),
                'dependencia_domicilio' => '', // No siempre disponible; se rellena con parámetros
                'responsable_nombre' => (string) ($lic['responsable_nombre'] ?? ''),
                'convocante_nombre' => (string) ($lic['dependencia_nombre'] ?? 'Gobierno'),
                'fecha_acto' => (string) ($lic['fecha_apertura_propuestas'] ?? $lic['fecha_junta_aclaraciones'] ?? ''),
                'lugar_acto' => (string) ($lic['ubicacion_proyecto'] ?? 'Por definir'),
                'fecha_publicacion_convocatoria' => (string) ($lic['fecha_publicacion_convocatoria'] ?? ''),
                'fecha_junta_aclaraciones' => (string) ($lic['fecha_junta_aclaraciones'] ?? ''),
                'fecha_recepcion_propuestas' => (string) ($lic['fecha_recepcion_propuestas'] ?? ''),
                'fecha_apertura_propuestas' => (string) ($lic['fecha_apertura_propuestas'] ?? ''),
                'fecha_fallo_adjudicacion' => (string) ($lic['fecha_fallo_adjudicacion'] ?? ''),
            ]);

            // Hitos como texto plano (para Resumen Licitación)
            $hitos = array_filter([
                $lic['fecha_publicacion_convocatoria'] ?? null
                    ? "Publicación: {$lic['fecha_publicacion_convocatoria']}" : null,
                $lic['fecha_junta_aclaraciones'] ?? null
                    ? "Junta de aclaraciones: {$lic['fecha_junta_aclaraciones']}" : null,
                $lic['fecha_apertura_propuestas'] ?? null
                    ? "Apertura: {$lic['fecha_apertura_propuestas']}" : null,
                $lic['fecha_fallo_adjudicacion'] ?? null
                    ? "Fallo: {$lic['fecha_fallo_adjudicacion']}" : null,
            ]);
            $vars['hitos'] = implode(' · ', $hitos);

            // Tabla de licitantes para Acta de Apertura
            $vars['licitantes_filas'] = $this->buildLicitantesFilas($id);

            // Resumen de adjudicación
            $vars['adjudicacion_resumen'] = !empty($lic['id_contrato_relacionado'])
                ? "Adjudicada (contrato #{$lic['id_contrato_relacionado']})"
                : 'Sin adjudicar';
        }

        if ($entidad === 'contrato') {
            $contrato = $this->contratoRepo->findById($id);
            if (!$contrato) return null;
            $vars['id_contrato'] = (string) ($contrato['id_contrato'] ?? '');
            $vars['numero_contrato'] = (string) ($contrato['numero_contrato'] ?? '');
            $vars['monto_adjudicado'] = $this->formatMoney($contrato['monto_contrato'] ?? 0);
            $vars['plazo_ejecucion'] = (string) ($contrato['fecha_inicio'] ?? '') . ' a ' . (string) ($contrato['fecha_fin'] ?? '');
            $vars['licitante_ganador'] = (string) ($contrato['nombre_empresa'] ?? '');
            $vars['licitante_rfc'] = (string) ($contrato['registro_fiscal'] ?? '');

            // Vincular con licitación si existe
            if (!empty($contrato['id_licitacion'])) {
                $lic = $this->licitacionRepo->findById((int) $contrato['id_licitacion']);
                if ($lic) {
                    $vars['id_licitacion'] = (string) $lic['id_licitacion'];
                    $vars['numero_licitacion'] = (string) ($lic['numero_licitacion'] ?? '');
                    $vars['tipo_procedimiento'] = $this->humanProcedimiento((string) ($lic['tipo_procedimiento'] ?? ''));
                    $vars['descripcion_proyecto'] = (string) ($lic['descripcion_proyecto'] ?? '');
                    $vars['convocante_nombre'] = (string) ($lic['dependencia_nombre'] ?? '');
                    $vars['dependencia_nombre'] = (string) ($lic['dependencia_nombre'] ?? '');
                }
            }
        }

        return $vars;
    }

    private function buildLicitantesFilas(int $idLicitacion): string {
        try {
            $stmt = (new PDO(...$this->dsnArgs()))->prepare(
                'SELECT pr.nombre_empresa, pr.registro_fiscal, p.id_participacion, prop.monto_propuesta
                 FROM participacion p
                 JOIN proveedor pr ON p.id_proveedor = pr.id_proveedor
                 LEFT JOIN propuesta prop ON prop.id_participacion = p.id_participacion
                 WHERE p.id_licitacion = :id
                 ORDER BY pr.nombre_empresa'
            );
        } catch (Throwable $e) {
            // Fallback: usar conexión global del proyecto
            $db = getDbConnection();
            $stmt = $db->prepare(
                'SELECT pr.nombre_empresa, pr.registro_fiscal, p.id_participacion, prop.monto_propuesta
                 FROM participacion p
                 JOIN proveedor pr ON p.id_proveedor = pr.id_proveedor
                 LEFT JOIN propuesta prop ON prop.id_participacion = p.id_participacion
                 WHERE p.id_licitacion = :id
                 ORDER BY pr.nombre_empresa'
            );
        }
        $stmt->execute(['id' => $idLicitacion]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        if (empty($rows)) {
            return '<tr><td colspan="4" style="text-align:center;color:#888">— Sin licitantes registrados —</td></tr>';
        }

        $html = '';
        foreach ($rows as $i => $r) {
            $html .= '<tr>'
                . '<td>' . ($i + 1) . '</td>'
                . '<td>' . htmlspecialchars((string) $r['nombre_empresa'], ENT_QUOTES, 'UTF-8')
                . '<br><small>' . htmlspecialchars((string) ($r['registro_fiscal'] ?? ''), ENT_QUOTES, 'UTF-8') . '</small></td>'
                . '<td>' . ($r['monto_propuesta'] !== null ? '$' . $this->formatMoney($r['monto_propuesta']) : '—') . '</td>'
                . '<td>Recibida</td>'
                . '</tr>';
        }
        return $html;
    }

    /** Pequeño truco para que el IDE no marque error cuando se pasa por argumento desempaquetado. */
    private function dsnArgs(): array {
        return ['mysql:host=invalid', '', '']; // se invoca dentro de try/catch para forzar fallback
    }

    // ----- renderers -----

    private function renderPdf(string $html): ?string {
        if (!class_exists('Dompdf\\Dompdf')) {
            return null;
        }
        try {
            $options = new \Dompdf\Options();
            $options->set('isRemoteEnabled', false); // seguridad: no fetch remoto
            $options->set('isHtml5ParserEnabled', true);
            $options->set('defaultFont', 'sans-serif');
            $dompdf = new \Dompdf\Dompdf($options);
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper('letter', 'portrait');
            $dompdf->render();
            return $dompdf->output();
        } catch (Throwable $e) {
            error_log('PDF render error: ' . $e->getMessage());
            return null;
        }
    }

    private function renderDocx(string $html, string $titulo): ?string {
        if (!class_exists('PhpOffice\\PhpWord\\PhpWord')) {
            return null;
        }
        try {
            $phpWord = new \PhpOffice\PhpWord\PhpWord();
            $phpWord->getDocInfo()->setTitle($titulo);
            $phpWord->getDocInfo()->setCreator('SGPLOPyPC');
            $section = $phpWord->addSection();

            // PHPWord puede importar HTML simple; sanitizamos a un subset compatible
            $cleanHtml = $this->sanitizeHtmlForPhpWord($html);
            try {
                \PhpOffice\PhpWord\Shared\Html::addHtml($section, $cleanHtml, false, false);
            } catch (Throwable $e) {
                // Fallback: convertir a texto plano si el HTML es complejo
                $plain = trim(preg_replace('/\s+/', ' ', strip_tags(str_replace(['<br>', '<br/>', '</p>'], "\n", $html))));
                $section->addText($plain);
            }

            $tmp = tempnam(sys_get_temp_dir(), 'sgpl_docx_');
            $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
            $writer->save($tmp);
            $bin = @file_get_contents($tmp);
            @unlink($tmp);
            return $bin === false ? null : $bin;
        } catch (Throwable $e) {
            error_log('DOCX render error: ' . $e->getMessage());
            return null;
        }
    }

    private function renderMarkdown(string $html, array $plantilla, array $variables): string {
        // Conversión simple HTML → Markdown
        $md = $html;
        // Encabezados
        $md = preg_replace('/<h1[^>]*>(.*?)<\/h1>/is', "\n# $1\n", $md);
        $md = preg_replace('/<h2[^>]*>(.*?)<\/h2>/is', "\n## $1\n", $md);
        $md = preg_replace('/<h3[^>]*>(.*?)<\/h3>/is', "\n### $1\n", $md);
        // Negrita
        $md = preg_replace('/<strong[^>]*>(.*?)<\/strong>/is', '**$1**', $md);
        $md = preg_replace('/<b[^>]*>(.*?)<\/b>/is', '**$1**', $md);
        // Itálicas
        $md = preg_replace('/<em[^>]*>(.*?)<\/em>/is', '*$1*', $md);
        $md = preg_replace('/<i[^>]*>(.*?)<\/i>/is', '*$1*', $md);
        // Saltos
        $md = preg_replace('/<br\s*\/?>/i', "\n", $md);
        $md = preg_replace('/<\/p>/i', "\n\n", $md);
        // Limpiar tags restantes
        $md = strip_tags($md);
        // Decodificar entidades HTML
        $md = html_entity_decode($md, ENT_QUOTES, 'UTF-8');
        // Colapsar saltos
        $md = preg_replace('/\n{3,}/', "\n\n", $md);
        $md = trim($md);

        // Encabezado YAML front-matter para metadatos
        $yaml = "---\n";
        $yaml .= 'plantilla: ' . $this->yamlEscape((string) $plantilla['nombre']) . "\n";
        $yaml .= 'tipo: ' . (string) $plantilla['tipo'] . "\n";
        $yaml .= 'fecha_emision: ' . ($variables['fecha_emision'] ?? '') . "\n";
        if (!empty($variables['id_licitacion'])) {
            $yaml .= 'id_licitacion: ' . $variables['id_licitacion'] . "\n";
        }
        $yaml .= "---\n\n";

        return $yaml . $md . "\n";
    }

    private function yamlEscape(string $v): string {
        return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $v) . '"';
    }

    private function sanitizeHtmlForPhpWord(string $html): string {
        // Quitar style/script y comentarios
        $html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $html) ?? $html;
        $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html) ?? $html;
        $html = preg_replace('/<!--.*?-->/s', '', $html) ?? $html;
        // Extraer body si existe
        if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $html, $m)) {
            return $m[1];
        }
        return $html;
    }

    // ----- placeholder engine -----

    private function replacePlaceholders(string $html, array $variables): string {
        return preg_replace_callback(
            '/\{\{\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*\}\}/',
            function (array $m) use ($variables): string {
                $key = $m[1];
                if (!array_key_exists($key, $variables)) {
                    return ''; // variable no provista → vacío (las plantillas asumen vacío válido)
                }
                $val = (string) $variables[$key];
                // Si la variable contiene HTML conocido (filas <tr>) la pasamos sin escapar.
                if ($key === 'licitantes_filas' || $key === 'evaluacion_tecnica_filas' || $key === 'evaluacion_economica_filas') {
                    return $val;
                }
                return htmlspecialchars($val, ENT_QUOTES, 'UTF-8');
            },
            $html
        ) ?? $html;
    }

    // ----- helpers -----

    private function formatMoney(mixed $v): string {
        $n = (float) $v;
        return number_format($n, 2, '.', ',');
    }

    private function humanProcedimiento(string $tipo): string {
        $map = [
            'LICITACION_PUBLICA' => 'Licitación Pública',
            'INVITACION_RESTRINGIDA' => 'Invitación a cuando menos tres personas',
            'ADJUDICACION_DIRECTA' => 'Adjudicación Directa',
        ];
        return $map[$tipo] ?? $tipo;
    }

    private function buildFilename(string $nombre, string $entidad, int $idEntidad, string $formato): string {
        $base = preg_replace('/[^a-zA-Z0-9_-]+/', '_', strtolower($nombre)) ?: 'reporte';
        $base = trim($base, '_');
        return sprintf('%s_%s%d_%s.%s', $base, $entidad, $idEntidad, date('Ymd_His'), $formato);
    }
}
