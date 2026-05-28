# Fase 3 — API pública de datos abiertos (OCDS 1.1)

**Estado:** ✅ Completada — 2026-05-27
**Commits:**
- `17be2eb0e397119d5aaf43cfd0088a782933829f` — feat principal
- `93c2fa8` — fix header duplicado
- `500c19c85c39543948d4bc52f5b5641ee12ffdb4` — test tolerante
**Deployment Railway:** `5159aee8-eaad-4964-9009-d004beccf61e`
**URL producción:** https://sgplopypc.up.railway.app

## 1. Objetivo

Exponer las contrataciones públicas del sistema a través de una API abierta sin autenticación, en formato **Open Contracting Data Standard 1.1**, con licencia CC BY 4.0 y compatibilidad con CompraNet (México) y plataformas internacionales.

## 2. Estándares aplicados

- **OCDS 1.1** — https://standard.open-contracting.org/1.1/
- **LGTAIP** (Ley General de Transparencia, México) — datos abiertos por defecto
- **CC BY 4.0** — licencia de los datos
- **CPV 45000000** — código de clasificación europeo para obra pública (usado como ítem genérico)

## 3. Cambios entregados

### 3.1 Backend

| Archivo | Responsabilidad |
|---|---|
| `app/repositories/OcdsRepository.php` | Queries paginadas y por OCID, con LEFT JOIN a `fecha_proceso` para los 5 hitos clave. Excluye `BORRADOR`. |
| `app/services/OcdsService.php` | Construye estructuras conforme schema OCDS 1.1: ocid, tags derivados, tender (con status/method/items/períodos), awards, contracts, parties (buyer + suppliers + tenderers), buyer raíz, release-package con publisher. |
| `app/controllers/DatosAbiertosController.php` | Endpoints HTTP públicos. Headers CORS, cache 5 min. `release-package?download=1` fuerza Content-Disposition. |
| `public/index.php` | Helper `enforceOcdsRateLimit` (60 req/min), rutas y manejo de OPTIONS preflight (204). |

### 3.2 Endpoints expuestos

| Método | Ruta | Descripción | Formato respuesta |
|---|---|---|---|
| `GET` | `/api/v1/datos-abiertos/releases` | Lista paginada (params: `page`, `limit≤50`, `from`, `to`, `estado`). | Envoltura SGPLOPyPC + `data.releases[]` + `data.pagination`. |
| `GET` | `/api/v1/datos-abiertos/releases/{ocid}` | Un release por OCID. | **OCDS puro** (sin envoltura). 404 con `{error, message}` custom. |
| `GET` | `/api/v1/datos-abiertos/release-package` | Paquete completo OCDS. `?download=1` añade `Content-Disposition`. | **OCDS Release Package** puro. |
| `OPTIONS` | (cualquiera de las anteriores) | Preflight CORS. | 204 sin body. |

### 3.3 Mapping principal (resumen)

```
licitacion.numero_licitacion ─→ ocid (prefijo: ocds-sgplopypc-)
licitacion.estado_proceso    ─→ tag[] + tender.status
licitacion.tipo_procedimiento ─→ tender.procurementMethod
licitacion.presupuesto       ─→ tender.value.amount (MXN)
fecha_proceso.*              ─→ tender.tenderPeriod / enquiryPeriod / awardPeriod
dependencia                  ─→ buyer + procuringEntity (party)
participacion                ─→ parties[] con role 'tenderer'
contrato                     ─→ awards[] + contracts[] + party 'supplier'
proveedor.registro_fiscal    ─→ party.identifier (scheme: MX-RFC)
contrato.fecha_firma_proveedor ─→ contracts[].dateSigned
```

Detalle completo en [`FASE3_OCDS_MAPPING.md`](./FASE3_OCDS_MAPPING.md).

### 3.4 Cabeceras de respuesta

```
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: GET, OPTIONS
Access-Control-Allow-Headers: Content-Type
Cache-Control: public, max-age=300
X-Content-Type-Options: nosniff      (vía Apache + setSecurityHeaders global)
Content-Type: application/json; charset=utf-8
```

### 3.5 Rate limiting

60 req/min por IP por endpoint, configurable:

```env
RATE_LIMIT_OCDS_MAX=60
RATE_LIMIT_OCDS_WINDOW=60
```

Al exceder el límite responde 429 (con CORS para que clientes JS puedan leer el error).

### 3.6 Tests E2E

`e2e/tests/datos-abiertos.spec.ts` — **10 casos**:

1. ✅ `GET /releases` responde 200 sin auth, con header `Access-Control-Allow-Origin: *`
2. ✅ Cada release tiene campos OCDS obligatorios (ocid, id, date, tag, parties, buyer, tender)
3. ✅ `GET /releases/{ocid}` devuelve OCDS **puro** (sin clave `success`)
4. ✅ OCID inexistente → 404 con shape `{error: "not_found"}`
5. ✅ `GET /release-package` cumple structure (publisher, license, publicationPolicy, releases[])
6. ✅ `?download=1` fuerza `Content-Disposition: attachment`
7. ✅ OPTIONS preflight responde 204 con headers CORS correctos
8. ✅ Borradores nunca aparecen (incluso si se intenta filtrar por `estado=BORRADOR`)
9. ✅ Filtro `estado=ADJUDICADA` devuelve sólo `tender.status=complete` con tag `award`
10. ✅ Cabeceras `Cache-Control` y `X-Content-Type-Options` presentes

## 4. Verificación en producción

### Smoke

```bash
# Sin token (público)
curl -fsSL https://sgplopypc.up.railway.app/api/v1/datos-abiertos/releases?limit=1 \
  | jq '.data.releases[0] | {ocid, tag, "tender.status": .tender.status}'
```

Salida esperada:

```json
{
  "ocid": "ocds-sgplopypc-SEED-ADJUDICADA-001",
  "tag": ["tender", "award"],
  "tender.status": "complete"
}
```

### Release individual (OCDS puro)

```bash
curl -fsSL https://sgplopypc.up.railway.app/api/v1/datos-abiertos/releases/ocds-sgplopypc-SEED-RECEPCION-001 \
  | jq 'keys'
# → ["awards"?, "buyer", "date", "id", "initiationType", "language", "ocid", "parties", "tag", "tender"]
# (sin "success", "data" ni "message")
```

### Release Package descargable

```bash
curl -fsSL "https://sgplopypc.up.railway.app/api/v1/datos-abiertos/release-package?download=1" \
  -o sgplopypc-ocds.json
# Archivo descargado con Content-Disposition: attachment
```

## 5. Resultados E2E

```
Fase 3 (datos-abiertos):           10 passed
Smoke regresivo (Fase 1+2):
  admin-auditoria.spec.ts:          5 passed
  admin-plantillas.spec.ts:         6 passed
  admin-reportes-export.spec.ts:    6 passed
                                  ─────────
TOTAL:                            27 passed / 0 failed (27.1s)
```

## 6. Estado de BD productiva

3 licitaciones publicables al cierre (todas seed):

| OCID | Estado | Tags |
|---|---|---|
| `ocds-sgplopypc-SEED-ADJUDICADA-001` | ADJUDICADA | `[tender, award]` |
| `ocds-sgplopypc-SEED-RECEPCION-001`  | RECEPCION_PROPUESTAS | `[tender]` |
| `ocds-sgplopypc-SEED-ACLARACIONES-001` | EN_ACLARACIONES | `[tender]` |

0 participaciones registradas (los seeds de demo no incluyen participantes; cuando un proveedor real se inscriba, automáticamente aparecerá en `parties[]` con role `tenderer`).

## 7. Decisiones técnicas

### Sin schema validator integrado
Validar el schema OCDS contra el JSON Schema oficial requeriría una dependencia adicional (`opis/json-schema` o `justinrainbow/json-schema`). Se decidió dejar la validación como paso opcional externo (los tests E2E cubren los campos críticos). El JSON producido fue verificado manualmente contra https://standard.open-contracting.org/1.1/.

### Header `X-Content-Type-Options` doble
Apache emite el header vía `Header always set` y `setSecurityHeaders()` lo emite también desde PHP. El navegador lo trata como una sola directiva. Se removió la duplicación intencional en `setOcdsHeaders()` y el test fue ajustado para usar `toContain('nosniff')` en lugar de `toBe('nosniff')`.

### Item genérico CPV 45000000
Como el sistema no clasifica licitaciones con códigos CPV/CPC granulares aún, se emite un solo item por release con clasificación `45000000` (Trabajos de construcción) y `unit.name = "global"`. Una iteración futura podría capturar clasificación detallada en una tabla nueva.

### Sin `documents[]` ni `milestones[]`
Los documentos en `storage/documents/` aún no están expuestos vía URL pública (requeriría firmar URLs o un endpoint público adicional). Los milestones se reflejan implícitamente en `tender.tenderPeriod`, `enquiryPeriod` y `awardPeriod`. Mejoras futuras opcionales.

## 8. Próxima fase

Avanzar a **Fase 4 — Dashboard analítico con métricas** según `docs/fases/mejoras/FASES_MEJORAS.md`.

---

## Anexo — Plantilla de cierre

```text
Commits:       17be2eb (feat) → 93c2fa8 (fix headers) → 500c19c (fix test)
HEAD final:    500c19c85c39543948d4bc52f5b5641ee12ffdb4
Deployment:    5159aee8-eaad-4964-9009-d004beccf61e
URL:           https://sgplopypc.up.railway.app
Healthcheck:   /healthz=200  /api/v1/health app=ok db=ok
E2E fase 3:    10 passed / 0 failed
E2E regresión: 17 passed / 0 failed (Fase 1 + Fase 2)
Endpoints:     GET     /api/v1/datos-abiertos/releases
               GET     /api/v1/datos-abiertos/releases/{ocid}
               GET     /api/v1/datos-abiertos/release-package
               OPTIONS (preflight CORS)
Estándares:    OCDS 1.1, LGTAIP, CC BY 4.0
```
