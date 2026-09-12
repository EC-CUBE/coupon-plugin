import { expect, type Page } from '@playwright/test';

/** 管理画面にログインする */
export async function adminLogin(page: Page): Promise<void> {
  await page.goto('/admin/login');
  await page.fill('input[name="login_id"]', 'admin');
  await page.fill('input[name="password"]', 'password');
  await page.locator('button[type="submit"]').click();
  await expect(page).toHaveURL('/admin/');
}

/** 管理画面からログアウトする */
export async function adminLogout(page: Page): Promise<void> {
  await page.goto('/admin/logout');
  await expect(page).toHaveURL(/\/admin\/login/);
}

function formatDate(date: Date): string {
  const y = date.getFullYear();
  const m = String(date.getMonth() + 1).padStart(2, '0');
  const d = String(date.getDate()).padStart(2, '0');
  return `${y}-${m}-${d}`;
}

export type CouponOptions = {
  couponCd: string;
  couponName?: string;
  discountPrice: number;
  couponRelease?: number;
};

/**
 * 管理画面からクーポンを登録する.
 *
 * 対象商品は「全商品」, 利用制限は「なし」(ゲストでも利用できる), 値引き種別は「値引き額」で固定。
 */
export async function createCoupon(page: Page, options: CouponOptions): Promise<void> {
  const { couponCd, discountPrice } = options;
  const couponName = options.couponName ?? `E2E クーポン ${couponCd}`;
  const couponRelease = options.couponRelease ?? 10;

  await page.goto('/admin/plugin/coupon/new');

  await page.fill('#coupon_coupon_cd', couponCd);
  await page.fill('#coupon_coupon_name', couponName);
  // 対象商品: 全商品 (Coupon::ALL) — 商品/カテゴリ明細が不要になる
  await page.check('#coupon_coupon_type_2');
  // 利用制限: なし (ゲスト購入でも利用できる)
  await page.check('#coupon_coupon_member_1');
  // 値引き種別: 値引き額
  await page.check('#coupon_discount_type_0');
  await page.fill('#coupon_discount_price', String(discountPrice));
  await page.fill('#coupon_coupon_release', String(couponRelease));

  const today = new Date();
  const nextMonth = new Date(today.getTime());
  nextMonth.setMonth(nextMonth.getMonth() + 1);
  await page.fill('#coupon_available_from_date', formatDate(today));
  await page.fill('#coupon_available_to_date', formatDate(nextMonth));

  await page.locator('button:has-text("登録")').first().click();
  await expect(page).toHaveURL('/admin/plugin/coupon');
  await expect(page.locator('body')).toContainText(couponCd);
}

/**
 * クーポン一覧から残発行枚数 (plg_coupon.coupon_use_time) を取得する.
 *
 * 一覧の残発行枚数カラムは「残発行枚数 / 発行枚数」形式で, admin/index.twig の 7 列目。
 */
export async function couponRemaining(page: Page, couponCd: string): Promise<number> {
  await page.goto('/admin/plugin/coupon');
  const row = page.locator('tbody tr', { hasText: couponCd }).first();
  const text = (await row.locator('td').nth(6).innerText()).trim();
  const matched = text.match(/^([\d,]+)\s*\/\s*([\d,]+)$/);
  expect(matched, `残発行枚数が取得できない: ${text}`).toBeTruthy();
  return Number((matched as RegExpMatchArray)[1].replace(/,/g, ''));
}

/** ゲストでカートに商品を入れ, 購入手続き画面 (/shopping) まで進む */
export async function goToShoppingAsGuest(page: Page): Promise<void> {
  await page.goto('/');

  await page.getByRole('link', { name: '新入荷' }).click();
  await expect(page).toHaveURL('/products/list?category_id=2');
  await page
    .locator('li:has-text("チェリーアイスサンド")')
    .getByRole('button', { name: 'カートに入れる' })
    .first()
    .click();

  // EC-CUBE 4.4 のカートボタンは AJAX リクエストを送るため完了を待ってから遷移する
  await page.waitForLoadState('networkidle');
  await page.goto('/cart');

  await page.getByRole('link', { name: 'レジに進む' }).click();
  await expect(page).toHaveURL('/shopping/login');

  await page.getByRole('link', { name: 'ゲスト購入' }).click();
  await expect(page).toHaveURL('/shopping/nonmember');

  await page.getByPlaceholder('姓').fill('石');
  await page.getByPlaceholder('名', { exact: true }).fill('九部');
  await page.getByPlaceholder('セイ').fill('イーシー');
  await page.getByPlaceholder('メイ').fill('キューブ');
  await page.getByLabel('会社名').fill('イーシーキューブ');
  await page.getByPlaceholder('例：5300001').fill('5430001');
  await page.locator('select[name="nonmember\\[address\\]\\[pref\\]"]').selectOption('1');
  await page.getByPlaceholder('市区町村名(例：大阪市北区)').fill('大阪市北区');
  await page.getByPlaceholder('番地・ビル名(例：西梅田1丁目6-8)').fill('1');
  await page.getByPlaceholder('例：11122223333').fill('0633334444');
  await page.getByPlaceholder('例：ec-cube@example.com').fill('user@example.com');
  await page.getByPlaceholder('確認のためもう一度入力してください').fill('user@example.com');

  await page.getByRole('button', { name: '次へ' }).click();
  await expect(page).toHaveURL('/shopping');
}

/** 購入手続き画面からクーポンコードを入力して適用する */
export async function applyCoupon(page: Page, couponCd: string): Promise<void> {
  // クーポン欄の「クーポンを変更する」は ajax 後にクーポン入力画面へ遷移する
  await page.locator('#coupon_button').click();
  await expect(page).toHaveURL('/plugin/coupon/shopping/shopping_coupon');

  await page.check('#coupon_use_coupon_use_1');
  await page.fill('#coupon_use_coupon_cd', couponCd);
  await page.getByRole('button', { name: '登録' }).click();
  await expect(page).toHaveURL('/shopping');
}

/** 購入手続き画面から注文を確定する */
export async function placeOrder(page: Page): Promise<void> {
  await page.getByRole('button', { name: '確認する' }).click();
  await expect(page).toHaveURL('/shopping/confirm');

  await page.getByRole('button', { name: '注文する' }).click();
  await expect(page).toHaveURL('/shopping/complete');
}

/** 購入手続き画面 (/shopping) の「お支払い合計」を取得する */
export async function shoppingPaymentTotal(page: Page): Promise<number> {
  const text = await page.locator('.ec-totalBox__paymentTotal .ec-totalBox__price').first().innerText();
  const matched = text.match(/([\d,]+)/);
  expect(matched, `お支払い合計が取得できない: ${text}`).toBeTruthy();
  return Number((matched as RegExpMatchArray)[1].replace(/,/g, ''));
}

/** 受注一覧から最新の受注の編集画面 URL を取得する */
export async function latestOrderEditUrl(page: Page): Promise<string> {
  await page.goto('/admin/order');
  const href = await page
    .locator('a[href*="/admin/order/"][href*="/edit"]')
    .first()
    .getAttribute('href');
  expect(href, '受注編集リンクが見つからない').toBeTruthy();
  return href as string;
}
