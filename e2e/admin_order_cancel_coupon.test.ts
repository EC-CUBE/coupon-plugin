import { expect, test } from './fixtures';

import {
  adminLogin,
  adminLogout,
  applyCoupon,
  createCoupon,
  couponRemaining,
  goToShoppingAsGuest,
  latestOrderEditUrl,
  placeOrder,
} from './helpers';

/**
 * 受注ステータスを「注文取消し」(OrderStatus::CANCEL) へ変更したときのクーポンの挙動を確認する.
 *
 * このケースは PHPUnit の Web テストでは検証できない。
 * キャンセル遷移では本体の StockReduceProcessor が在庫を戻すために
 * EntityManager::lock(PESSIMISTIC_WRITE) を行うが, APP_ENV=test では
 * dama/doctrine-test-bundle のトランザクションが DBAL Connection 上では
 * 開いていないため TransactionRequiredException になる
 * (Tests/Web/Admin/OrderControllerTest::testOrderEditWithCouponCancel は
 *  そのためスキップしている)。
 *
 * E2E は prod と同じく TransactionListener が有効な環境で実リクエストを送るため,
 * この制約を受けずにキャンセル遷移を検証できる。
 *
 * @see https://github.com/EC-CUBE/ec-cube/issues/7016
 */
test('受注をキャンセルするとクーポンが戻り, 受注編集画面に案内が表示される', async ({ page }) => {
  const couponCd = `E2E${Date.now()}`;
  const couponRelease = 10;

  await adminLogin(page);
  await createCoupon(page, { couponCd, discountPrice: 1000, couponRelease });
  await adminLogout(page);

  await goToShoppingAsGuest(page);
  await applyCoupon(page, couponCd);
  await placeOrder(page);

  await adminLogin(page);
  expect(await couponRemaining(page, couponCd)).toBe(couponRelease - 1);

  const orderEditUrl = await latestOrderEditUrl(page);
  const response = await page.goto(orderEditUrl);
  expect(response?.status()).toBe(200);
  await expect(page.locator('#coupon')).toContainText(couponCd);

  // 受注ステータスを「注文取消し」(OrderStatus::CANCEL = 3) に変更して登録する
  await page.locator('#order_OrderStatus').selectOption('3');
  await page.getByRole('button', { name: '登録' }).first().click();
  await expect(page).toHaveURL(orderEditUrl);

  // 在庫を戻す StockReduceProcessor が悲観ロックを取るため, ここが 500 になっていないこと
  await expect(page.locator('body')).not.toContainText('システムエラーが発生しました');
  await expect(page.locator('#order_OrderStatus')).toHaveValue('3');

  // キャンセルされたためクーポンが適用されていない旨が表示される
  await expect(page.locator('#coupon')).toContainText('注文がキャンセルされたため、クーポンが適用されていません。');

  // クーポンは未使用に戻る
  expect(await couponRemaining(page, couponCd)).toBe(couponRelease);
});
