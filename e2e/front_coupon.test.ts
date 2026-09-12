import { expect, test } from './fixtures';

import {
  adminLogin,
  adminLogout,
  applyCoupon,
  createCoupon,
  couponRemaining,
  goToShoppingAsGuest,
  placeOrder,
  shoppingPaymentTotal,
} from './helpers';

/**
 * 購入フローでクーポンコードによる値引きが適用されることを確認する.
 *
 * PHPUnit の Web テスト (Tests/Web/CouponControllerTest) と重なる部分もあるが, こちらは
 * 実ブラウザ・実リクエストで JS (クーポン入力画面への遷移) まで含めて検証する。
 */
test('クーポンコードを適用すると値引きされ, 発行枚数が減る', async ({ page }) => {
  const couponCd = `E2E${Date.now()}`;
  const couponName = `E2E クーポン ${couponCd}`;
  const discount = 1000;
  const couponRelease = 10;

  await adminLogin(page);
  await createCoupon(page, { couponCd, couponName, discountPrice: discount, couponRelease });
  expect(await couponRemaining(page, couponCd)).toBe(couponRelease);
  await adminLogout(page);

  await goToShoppingAsGuest(page);
  const paymentTotalBeforeCoupon = await shoppingPaymentTotal(page);

  await applyCoupon(page, couponCd);
  await expect(page.locator('#coupon')).toContainText(`クーポンコード ${couponCd} を利用しています。`);

  // 値引き明細 (商品名 = クーポン名) が追加され, お支払い合計が値引き額だけ下がる
  await expect(page.locator('.ec-orderRole')).toContainText(couponName);
  expect(await shoppingPaymentTotal(page)).toBe(paymentTotalBeforeCoupon - discount);

  await placeOrder(page);

  // 利用済みになった分, 残発行枚数が 1 枚減る
  await adminLogin(page);
  expect(await couponRemaining(page, couponCd)).toBe(couponRelease - 1);
});
