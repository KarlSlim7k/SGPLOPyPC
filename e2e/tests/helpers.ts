import type { APIRequestContext, Page } from '@playwright/test';
import { expect } from '@playwright/test';

/** IP única por test para evitar colisiones de rate limit entre tests. */
export function fakeIp(): string {
  const n = Date.now() % 0xFFFFFF;
  return `10.${(n >> 16) & 0xFF}.${(n >> 8) & 0xFF}.${n & 0xFF}`;
}

/** Headers con X-Forwarded-For único para bypass de rate limit en tests. */
export function rlHeaders(ip: string, extra: Record<string, string> = {}): Record<string, string> {
  return { 'X-Forwarded-For': ip, ...extra };
}

/** Login via API con IP única para evitar rate limit. Devuelve el token. */
export async function loginToken(
  request: APIRequestContext,
  email: string,
  password: string,
  ip = fakeIp()
): Promise<string> {
  const res = await request.post('/api/v1/auth/login', {
    data: { email, password },
    headers: rlHeaders(ip),
  });
  expect(res.ok(), `login failed ${res.status()}: ${await res.text()}`).toBeTruthy();
  return (await res.json()).data.token as string;
}

/** Login via UI con IP única inyectada en el header de la página. */
export async function loginUI(
  page: Page,
  email: string,
  password: string,
  redirectPattern: string | RegExp,
  ip = fakeIp()
): Promise<void> {
  await page.setExtraHTTPHeaders({ 'X-Forwarded-For': ip });
  await page.goto('/frontend/auth/login.html');
  await page.locator('#email').fill(email);
  await page.locator('#password').fill(password);
  await page.getByRole('button', { name: /iniciar sesi(?:ó|o)n/i }).click();
  await page.waitForURL(redirectPattern, { timeout: 15000 });
}
