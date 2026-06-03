import { defineConfig, devices } from '@playwright/test';

const baseURL = process.env.E2E_BASE_URL || 'https://sgplopypc.up.railway.app';
const browserChannel = process.env.E2E_BROWSER_CHANNEL || '';

const browserUse = browserChannel
  ? { ...devices['Desktop Chrome'], channel: browserChannel as 'chrome' }
  : { ...devices['Desktop Chrome'] };

const projectName = browserChannel || 'chromium';

export default defineConfig({
  testDir: './tests',
  fullyParallel: false,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: 1,
  reporter: [['list'], ['html', { open: 'never' }]],
  use: {
    baseURL,
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
    ignoreHTTPSErrors: true,
  },
  projects: [
    {
      name: projectName,
      use: browserUse,
    },
  ],
});
