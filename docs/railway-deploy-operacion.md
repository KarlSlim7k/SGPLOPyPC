# Railway Deploy - Operacion Rapida

Ultima actualizacion: 2026-05-07 (America/Mexico_City)

## Regla Temporal (Importante)

Cuando Railway tenga cola larga o inestabilidad en builds de GitHub:

1. Ir a Railway Dashboard.
2. Abrir el servicio `SGPLOPyPC`.
3. Entrar a `Settings` -> `Source`.
4. Desactivar temporalmente auto-deploy de GitHub (en docs Railway aparece como `Disconnect` o deshabilitar trigger automatico).

Referencia oficial:
- https://docs.railway.com/deployments/github-autodeploys
- https://docs.railway.com/guides/github-autodeploys

## Flujo recomendado en incidentes

1. Confirmar rama y commits locales:
   - `git branch --show-current`
   - `git log --oneline -n 5`
2. Desplegar por CLI:
   - `railway up --detach -m "cli: deploy estable durante incidente github queue"`
3. Verificar estado:
   - `railway service status`
   - `railway deployment list --limit 10`
4. Validar endpoint:
   - `curl -fsSL https://sgplopypc-production.up.railway.app/ | head`

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
  --grep "admin can login, navigate modules, and logout|dark mode preference applies and persists"
```

Resultado esperado:
- Login/logout admin OK
- Navegacion de modulos admin OK
- Modo oscuro en Configuracion persiste tras recarga

## Volver a flujo normal

Cuando Railway estabilice GitHub builds:

1. Reactivar auto-deploy en `Settings` -> `Source`.
2. Mantener `railway up` solo como fallback/manual.
