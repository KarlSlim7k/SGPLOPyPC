# Railway Deploy - Operacion Rapida

Ultima actualizacion: 2026-05-12 (America/Mexico_City)

## Politica actual

El despliegue productivo se realiza desde GitHub (auto-deploy de Railway).  
No usar `railway up` para publicar cambios de aplicacion en flujo normal.

Referencia oficial:
- https://docs.railway.com/deployments/github-autodeploys
- https://docs.railway.com/guides/github-autodeploys

## Flujo de entrega (GitHub -> Railway)

1. Confirmar rama y cambios:
   - `git branch --show-current`
   - `git log --oneline -n 5`
2. Publicar a GitHub:
   - `git push`
3. Esperar build/deploy automatico en Railway Dashboard.
4. Verificar estado del servicio (solo consulta):
   - `railway service status`
   - `railway deployment list --limit 10`

## Smoke tecnico post-deploy

Validar endpoints base:

```bash
curl -fsSL https://sgplopypc-production.up.railway.app/ | head
curl -fsSL https://sgplopypc-production.up.railway.app/healthz
curl -fsSL https://sgplopypc-production.up.railway.app/api/v1/health
```

Para cierres documentales u operativos sin cambios funcionales:
- Mantener el flujo GitHub -> Railway.
- No usar `railway up`.
- Confirmar el deployment posterior al push con `railway deployment list --limit 10`.
- Repetir smoke base; no repetir E2E completos salvo que el smoke falle o se hayan tocado flujos funcionales.

Validar endpoints de soporte (admin):

1. Obtener token de admin:
```bash
curl -fsSL -X POST https://sgplopypc-production.up.railway.app/api/v1/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"admin@sgplopypc.gob.mx","password":"admin123"}'
```
2. Consultar bandeja:
```bash
curl -fsSL 'https://sgplopypc-production.up.railway.app/api/v1/soporte/tickets?page=1&limit=5' \
  -H "Authorization: Bearer <TOKEN_ADMIN>"
```

## Smoke E2E de verificacion (Chrome)

Desde la raiz del repo:

```bash
cd e2e
npm ci --no-audit --no-fund
E2E_BROWSER_CHANNEL=chrome \
E2E_BASE_URL='https://sgplopypc-production.up.railway.app' \
npx playwright test \
  tests/admin-auth-and-navigation.spec.ts \
  tests/admin-deep-flows.spec.ts \
  tests/admin-support-ticket-flow.spec.ts \
  tests/public-basic-flows.spec.ts \
  --grep "admin can login, navigate modules, and logout|dark mode preference applies and persists|admin can filter and update a public support ticket status"
```

Resultado esperado:
- Login/logout admin OK
- Navegacion de modulos admin OK
- Modo oscuro en Configuracion persiste tras recarga
- Bandeja de soporte admin permite filtrar y actualizar estado

## Checklist de entrega

1. Commit y push a GitHub completados.
2. Deployment en Railway en estado `SUCCESS`.
3. `GET /` y `GET /api/v1/health` responden `200`.
4. Smoke admin de soporte validado:
   - `/api/v1/soporte/tickets` responde con datos y resumen.
   - cambio de estado (`PATCH /api/v1/soporte/tickets/{id}/estado`) funcional.
5. Evidencia guardada:
   - hash de commit,
   - URL de produccion,
   - salida de healthcheck,
   - resultado de E2E/smoke.
