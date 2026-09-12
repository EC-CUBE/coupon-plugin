import { test as base } from '@playwright/test';

/**
 * Coupon44 E2E 用の test フィクスチャ.
 *
 * docker-compose.dev.yml の EC-CUBE は APP_DEBUG=1 で動くため、画面下部に Symfony の
 * Web デバッグツールバーが固定表示される。フォーム末尾の「登録」ボタンなどがツールバーに
 * 隠れてクリックを奪われるので、E2E の間は CSS で非表示にする。
 */
export const test = base.extend({
  page: async ({ page }, use) => {
    await page.addInitScript(() => {
      const hideToolbar = () => {
        const style = document.createElement('style');
        style.textContent = '.sf-toolbar, .sf-minitoolbar { display: none !important; }';
        document.head?.appendChild(style);
      };
      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', hideToolbar);
      } else {
        hideToolbar();
      }
    });
    await use(page);
  },
});

export { expect } from '@playwright/test';
