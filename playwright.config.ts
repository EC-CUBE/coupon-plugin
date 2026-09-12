import { defineConfig, devices } from '@playwright/test';

/**
 * Coupon44 の E2E テスト設定.
 *
 * docker compose (docker-compose.yml + docker-compose.dev.yml) で起動した
 * EC-CUBE (プラグイン有効化済み) に対して実行する。
 *
 * @see https://playwright.dev/docs/test-configuration
 */
export default defineConfig({
  testDir: './e2e',
  timeout: 60 * 1000,
  expect: {
    timeout: 10 * 1000,
  },
  /*
   * 1 つの EC-CUBE インスタンスを共有し, クーポンの発行枚数や受注ステータスという
   * グローバルな状態を書き換えるため, 並列実行はしない。
   */
  fullyParallel: false,
  workers: 1,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 1 : 0,
  reporter: process.env.CI ? [['list'], ['html', { open: 'never' }]] : 'list',
  use: {
    /* docker-compose.yml が 8080:80 を公開する。dev 環境は HTTP でログインできるよう
       dockerbuild/dev-framework.yaml で Cookie 設定を上書きしている。 */
    baseURL: process.env.ECCUBE_BASE_URL ?? 'http://localhost:8080',
    ignoreHTTPSErrors: true,
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    locale: 'ja-JP',
    timezoneId: 'Asia/Tokyo',
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],
});
