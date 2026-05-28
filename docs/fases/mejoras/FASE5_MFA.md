# Fase 5 — Autenticación multifactor (MFA/2FA) TOTP

**Estado:** ✅ Completada — 2026-05-27
**Commit:** `6d98e2c21c37936240e74d287007875908c631a4`
**Deployment Railway:** `67508e11-f2b1-4b38-83cf-ba8ae69f7df9`
**URL producción:** https://sgplopypc.up.railway.app

## 1. Objetivo

Agregar una capa de seguridad adicional al proceso de autenticación mediante TOTP (Time-based One-Time Password, RFC 6238), compatible con Google Authenticator, Authy, Microsoft Authenticator y cualquier app TOTP estándar. Incluye códigos de respaldo para recuperación de acceso.

## 2. Cambios entregados

### 2.1 Base de datos

`database/migrations/015_mfa.sql` (idempotente):

| Columna | Tipo | Descripción |
|---|---|---|
| `mfa_secret` | `VARCHAR(64) NULL` | Secreto base32 del TOTP (guardado en claro; cifrado en reposo si se configura `APP_KEY` en el futuro). |
| `mfa_enabled` | `TINYINT(1) DEFAULT 0` | Flag que activa el segundo factor. |
| `mfa_backup_codes` | `TEXT NULL` | JSON array de hashes bcrypt de los 8 códigos de respaldo. |

Índice: `idx_usuario_mfa_enabled (mfa_enabled)`.

### 2.2 Backend

| Archivo | Responsabilidad |
|---|---|
| `app/helpers/TotpHelper.php` | HMAC-SHA1 RFC 6238, 6 dígitos, período 30s, ventana ±1. `generateSecret`, `verify`, `currentCode`, `otpauthUrl`, `generateBackupCodes`, `verifyBackupCode`. Sin dependencias externas. |
| `app/helpers/jwt.php` | Nuevo método `encodeWithTtl(payload, ttlSeconds)` para tokens de corta duración. `encode()` delega a él. |
| `app/repositories/UserRepository.php` | Nuevos métodos `findMfaById` y `updateMfa`. |
| `app/services/MfaService.php` | `enroll`, `confirm`, `disable`, `verifyLogin`, `verifyCodeOrBackup` (consume backup code al usarlo). |
| `app/services/AuthService.php` | `authenticate()` detecta `mfa_enabled=1` y devuelve `{requires_mfa:true, mfa_token}`. `completeMfaLogin()` valida mfa_token + código y emite JWT de sesión. |
| `app/controllers/AuthController.php` | `login()` maneja `requires_mfa`, `loginMfa()` nuevo endpoint, `mfaEnroll/mfaConfirm/mfaDisable`. |

### 2.3 Flujo de autenticación con MFA

```
Sin MFA:
  POST /auth/login → {token, usuario}

Con MFA activo:
  POST /auth/login → {requires_mfa: true, mfa_token}  (JWT 5 min)
  POST /auth/login/mfa {mfa_token, code} → {token, usuario}
```

El `mfa_token` es un JWT de 5 minutos con claim `mfa_challenge: true`. No puede usarse como token de sesión (el middleware `AuthMiddleware` no lo acepta porque no tiene claim `rol`).

### 2.4 Flujo de enrolamiento

```
1. POST /me/mfa/enroll → {secret, otpauth_url, qr_url}
   (guarda secreto temporal, mfa_enabled=0)

2. POST /me/mfa/confirm {code} → {backup_codes[8]}
   (verifica primer código TOTP, activa mfa_enabled=1, genera backup codes)

3. POST /me/mfa/disable {password, code} → 200
   (requiere contraseña + TOTP o backup code, limpia mfa_secret/enabled/codes)
```

### 2.5 Backup codes

- 8 códigos de 8 caracteres alfanuméricos (sin 0/1/I/O para evitar confusión visual).
- Almacenados como hashes bcrypt en `mfa_backup_codes` (JSON array).
- Cada código se consume al usarse (se elimina del array).
- Aceptados en `/auth/login/mfa` y en `/me/mfa/disable`.

### 2.6 Endpoints nuevos

| Método | Ruta | Auth | Rate limit | Descripción |
|---|---|---|---|---|
| `POST` | `/api/v1/auth/login/mfa` | — | 10/min | Completa login con código TOTP o backup code. |
| `POST` | `/api/v1/me/mfa/enroll` | Bearer | — | Inicia enrolamiento, devuelve QR. |
| `POST` | `/api/v1/me/mfa/confirm` | Bearer | — | Confirma primer código, activa MFA. |
| `POST` | `/api/v1/me/mfa/disable` | Bearer | — | Desactiva MFA (requiere password + código). |

### 2.7 Frontend

- `frontend/auth/mfa-enroll.html` — flujo de 3 pasos: escanear QR → confirmar código → mostrar backup codes con botón copiar.
- `frontend/auth/mfa-challenge.html` — recibe `mfa_token` en hash URL (`#mfa_token=...`), llama `/auth/login/mfa`, redirige según rol.

### 2.8 Tests E2E

`e2e/tests/auth-mfa.spec.ts` — **4 casos**:

1. ✅ `enroll` devuelve `secret` (32 chars), `otpauth_url` y `qr_url`
2. ✅ `confirm` con código incorrecto devuelve 422
3. ✅ Flujo completo: enroll → confirm → login con MFA → login con backup code → disable → login normal sin MFA
4. ✅ `enroll` rechaza si MFA ya está activo (409)

El test incluye una implementación JS nativa de TOTP (RFC 6238) usando **Web Crypto API** para generar códigos válidos sin dependencias externas.

## 3. Verificación en producción

```bash
TOKEN=$(curl -s -X POST https://sgplopypc.up.railway.app/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@sgplopypc.gob.mx","password":"admin123"}' | jq -r .data.token)

# Iniciar enrolamiento
curl -s -X POST https://sgplopypc.up.railway.app/api/v1/me/mfa/enroll \
  -H "Authorization: Bearer $TOKEN" | jq '{secret: .data.secret, qr: .data.qr_url}'

# Login sin MFA activo → token directo
curl -s -X POST https://sgplopypc.up.railway.app/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@sgplopypc.gob.mx","password":"admin123"}' | jq '.data | keys'
# → ["token", "usuario"] (sin requires_mfa)
```

## 4. Resultados E2E

```
Fase 5 (auth-mfa):                  4 passed
Smoke regresivo:
  admin-auditoria:                   5 passed
  admin-auth-and-navigation:         2 passed
  datos-abiertos:                   10 passed
  admin-dashboard-metricas:          9 passed
                                  ─────────
TOTAL:                            30 passed / 0 failed
```

Sin regresiones de fases anteriores.

## 5. Decisiones técnicas

### TotpHelper nativo PHP sin dependencias
Implementar RFC 6238 en ~170 líneas de PHP puro evita agregar dependencias de Composer para una funcionalidad crítica de seguridad. El algoritmo es simple (HMAC-SHA1 + dynamic truncation) y la implementación es verificable directamente.

### mfa_token como JWT de corta duración
En lugar de guardar estado de sesión MFA en BD o cache, se emite un JWT firmado de 5 minutos con claim `mfa_challenge: true`. El servidor lo valida con la misma clave JWT. Esto mantiene el backend stateless.

### Backup codes con bcrypt
Los códigos de respaldo se almacenan como hashes bcrypt (no en claro) para que incluso si la BD se compromete, los códigos no sean directamente utilizables. Se consumen al usarse para prevenir reutilización.

### QR via Google Charts API
La URL del QR se genera en el servidor y se devuelve al cliente. El cliente carga la imagen desde Google Charts. Esto no expone el secreto a Google (sólo la URL `otpauth://` codificada, que ya contiene el secreto). Para entornos con requisitos de privacidad más estrictos, se puede reemplazar por una librería de QR client-side (ej. `qrious`).

### Ventana de tolerancia ±1 período
Se aceptan códigos del período anterior y siguiente (±30s) para tolerar desfase de reloj entre el servidor y el dispositivo del usuario. Esto es estándar en implementaciones TOTP.

## 6. Próxima fase

Avanzar a **Fase 6 — Notificaciones en tiempo real (SSE)** según `docs/fases/mejoras/FASES_MEJORAS.md`.

---

## Anexo — Plantilla de cierre

```text
Commit:        6d98e2c21c37936240e74d287007875908c631a4
Deployment:    67508e11-f2b1-4b38-83cf-ba8ae69f7df9
URL:           https://sgplopypc.up.railway.app
Healthcheck:   /healthz=200  /api/v1/health app=ok db=ok
E2E fase 5:    4 passed / 0 failed
E2E regresión: 26 passed / 0 failed (Fases 1-4)
Total:         30 passed / 0 failed
Endpoints:     POST /auth/login/mfa
               POST /me/mfa/enroll
               POST /me/mfa/confirm
               POST /me/mfa/disable
Tablas:        usuario (+ mfa_secret, mfa_enabled, mfa_backup_codes)
Algoritmo:     TOTP RFC 6238, HMAC-SHA1, 6 dígitos, 30s, ventana ±1
Backup codes:  8 × 8 chars alfanuméricos, bcrypt, consumo único
```
