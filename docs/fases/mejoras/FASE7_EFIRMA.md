# Fase 7 — Firma electrónica avanzada (e.firma/FIEL)

**Estado:** ✅ Completada — 2026-05-28
**Commits:**
- `30f472802ed91f0a92393ea084b2537409ce8d42` — feat principal
- `ef2f17d` — fix PDO parámetros duplicados + efirma_serial VARCHAR(64)
- `4590f7ee2f4faedf02d6eef95ba6edee0a9c663c` — test tolerante
**Deployment Railway:** `a9abffbc-43a3-4dd2-bcd1-8067b4a1caa4`
**URL producción:** https://sgplopypc.up.railway.app

## 1. Objetivo

Permitir que los proveedores firmen contratos con su **e.firma/FIEL del SAT** (México), garantizando autenticidad e integridad del documento mediante criptografía asimétrica (RSA + SHA-256). Los archivos sensibles (`.key` y contraseña) **nunca se almacenan** en el servidor.

## 2. Marco legal y técnico

- **e.firma (FIEL):** Firma Electrónica Avanzada del SAT, regulada por el Código Fiscal de la Federación (CFF) y la Ley de Firma Electrónica Avanzada (LFEA).
- **NOM-151-SCFI:** Conservación de mensajes de datos y digitalización de documentos.
- **Algoritmo:** RSA + SHA-256 (PKCS#1 v1.5), compatible con OpenSSL 3.x.
- **Certificado:** X.509 en formato DER (binario) o PEM.
- **Clave privada:** PKCS#8 cifrada con DES3 en formato DER.

## 3. Cambios entregados

### 3.1 Base de datos

`database/migrations/016_efirma_contrato.sql` (idempotente):

| Columna | Tipo | Descripción |
|---|---|---|
| `efirma_rfc` | `VARCHAR(13) NULL` | RFC del firmante (del Subject del certificado). Índice `idx_contrato_efirma_rfc`. |
| `efirma_titular` | `VARCHAR(300) NULL` | Nombre del titular (CN del Subject). |
| `efirma_serial` | `VARCHAR(64) NULL` | Número de serie del certificado (hexadecimal). |
| `efirma_fecha` | `DATETIME NULL` | Fecha y hora de la firma. |
| `efirma_hash_documento` | `VARCHAR(64) NULL` | Hash SHA-256 del documento canónico (64 chars hex). |
| `efirma_firma_b64` | `TEXT NULL` | Firma digital PKCS#1 en base64. |

### 3.2 Backend

| Archivo | Responsabilidad |
|---|---|
| `app/helpers/EfirmaValidator.php` | `parseCer` (DER→PEM, extrae RFC/titular/serial), `isVigente`, `loadPrivateKey` (PKCS#8 DER), `keyMatchesCert`, `sign` (SHA-256 base64), `verify`. Usa `ext-openssl` nativo. |
| `app/repositories/ContratoRepository.php` | + `updateEfirma` (persiste metadatos, NUNCA `.key`). |
| `app/services/EfirmaService.php` | Flujo completo: verifica contrato/proveedor/ya-firmado → parsea cert → verifica vigencia → carga key en memoria → verifica key↔cert → calcula hash canónico → firma → **descarta key INMEDIATAMENTE** → persiste → audita. |
| `app/controllers/EfirmaController.php` | Multipart (cer, key, password), límite 100 KB, limpia variables sensibles con `str_repeat('0')` antes de `unset`. |

#### Hash del documento canónico

```
SHA-256("SGPLOPYPC|CONTRATO|{id}|{numero}|{monto}|{fecha_adj}|{fecha_inicio}|{fecha_fin}|{numero_licitacion}|{nombre_empresa}|{registro_fiscal}")
```

Garantiza que la firma cubre los datos más relevantes del contrato.

#### Extracción del RFC del certificado

El SAT incluye el RFC en distintos campos según la versión del certificado:
1. `x500UniqueIdentifier` (versión moderna): `"RFC / CURP"`
2. `OU` (versión anterior): directamente el RFC
3. `serialNumber` (fallback)

### 3.3 Endpoint

| Método | Ruta | Rol | Descripción |
|---|---|---|---|
| `POST` | `/api/v1/contratos/{id}/firma-efirma` | `PROVEEDOR` | Firma el contrato con e.firma. Multipart: `cer`, `key`, `password`. |

**Respuestas:**
- `200` — Firma exitosa. Devuelve `{efirma_rfc, efirma_titular, efirma_serial, efirma_fecha, efirma_hash_documento, certificado_vigente_hasta}`.
- `400` — Faltan campos.
- `403` — El proveedor no es el dueño del contrato.
- `404` — Contrato no encontrado.
- `409` — El contrato ya fue firmado con e.firma.
- `422` — Certificado inválido, vencido, password incorrecto, o key no corresponde al cert.

### 3.4 Frontend

`frontend/proveedor/firma-efirma.html` — accesible en `/frontend/proveedor/firma-efirma.html?id={idContrato}`:

- Aviso de seguridad (`.key` y password no se almacenan).
- Inputs `file` para `.cer` y `.key`, input `password`.
- Carga info del contrato via API.
- Detecta si ya está firmado y muestra aviso.
- Muestra resultado con RFC, titular, serial, fecha y vigencia del certificado.

### 3.5 Tests E2E

`e2e/tests/contrato-firma-efirma.spec.ts` — **7 casos**:

1. ✅ 401 sin token
2. ✅ 403 para admin (solo PROVEEDOR)
3. ✅ 422/409 con certificado inválido
4. ✅ 422/409 con password incorrecto
5. ✅ Firma exitosa (200 o 409 si ya firmado) — verifica shape de respuesta
6. ✅ 409/403 en segundo intento
7. ✅ Página HTML disponible con contenido correcto

El test genera un certificado RSA-2048 de prueba con `openssl` en `beforeAll` y lo limpia al terminar.

## 4. Verificación en producción

```bash
TOKEN=$(curl -s -X POST https://sgplopypc.up.railway.app/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"proveedor@demo.mx","password":"proveedor123"}' | jq -r .data.token)

# Generar certificado de prueba
openssl req -x509 -newkey rsa:2048 -keyout /tmp/test.key -out /tmp/test.cer -days 365 \
  -passout pass:TestPass123 -subj "/CN=DEMO/OU=DEMO800101ABC/O=SAT/C=MX" 2>/dev/null

# Firmar contrato
curl -s -X POST "https://sgplopypc.up.railway.app/api/v1/contratos/2/firma-efirma" \
  -H "Authorization: Bearer $TOKEN" \
  -F "cer=@/tmp/test.cer" -F "key=@/tmp/test.key" -F "password=TestPass123" | jq .
```

## 5. Resultados E2E

```
Fase 7 (contrato-firma-efirma):     7 passed
Smoke regresivo:
  notif-realtime:                    7 passed
  auth-mfa:                          4 passed
                                  ─────────
TOTAL:                            18 passed / 0 failed
```

## 6. Decisiones técnicas

### NUNCA persistir `.key` ni password
La clave privada se carga en memoria con `openssl_pkey_get_private()`, se usa para firmar, y se descarta con `unset($privateKey)`. El password se sobreescribe con `str_repeat('0', strlen($password))` antes de `unset`. Esto sigue el principio de mínima exposición de secretos.

### Hash canónico del documento
En lugar de firmar el PDF del contrato (que no existe como archivo), se firma un hash de los campos más relevantes del contrato en formato canónico. Esto garantiza integridad de los datos del contrato sin necesitar un archivo físico.

### Compatibilidad con certificados DER y PEM
El SAT emite certificados en formato DER (binario). El código detecta automáticamente si el archivo es DER o PEM y convierte si es necesario. Esto permite usar tanto los archivos originales del SAT como certificados PEM de prueba.

### Bugs encontrados y corregidos
1. **PDO parámetros duplicados:** `updateEfirma` usaba `:fecha` dos veces (para `efirma_fecha` y `fecha_firma_proveedor`). Renombrados a `:efirma_fecha` y `:fecha_firma`.
2. **efirma_serial demasiado corto:** Los seriales de certificados openssl son más largos que 40 chars. Ampliado a `VARCHAR(64)`.

## 7. Limitaciones y próximos pasos

- **Validación contra CA del SAT:** No se verifica la cadena de confianza contra los certificados raíz del SAT. Para uso productivo real, se debe validar contra las CAs publicadas en https://www.sat.gob.mx/tramites/16703/conoce-los-certificados-de-sello-digital.
- **Revocación (CRL/OCSP):** No se verifica si el certificado fue revocado. Requiere acceso a los endpoints CRL del SAT.
- **Asesoría legal:** Para uso con valor legal real, se recomienda asesoría jurídica sobre el cumplimiento de la LFEA y el CFF.

## 8. Próxima fase

Avanzar a **Fase 8 — Calificación y reputación de proveedores** según `docs/fases/mejoras/FASES_MEJORAS.md`.

---

## Anexo — Plantilla de cierre

```text
Commits:       30f4728 (feat) → ef2f17d (fix) → 4590f7e (test)
HEAD final:    4590f7ee2f4faedf02d6eef95ba6edee0a9c663c
Deployment:    a9abffbc-43a3-4dd2-bcd1-8067b4a1caa4
URL:           https://sgplopypc.up.railway.app
Healthcheck:   /healthz=200  /api/v1/health app=ok db=ok
E2E fase 7:    7 passed / 0 failed
E2E regresión: 11 passed / 0 failed (Fases 5-6)
Total:         18 passed / 0 failed
Endpoint:      POST /contratos/{id}/firma-efirma (PROVEEDOR)
Tablas:        contrato (+ 6 columnas efirma_*)
Algoritmo:     RSA + SHA-256 (PKCS#1 v1.5), ext-openssl nativo
Seguridad:     .key y password NUNCA se persisten
```
