<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * https://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\Coupon44\Tests\Service\PurchaseFlow\Processor;

use Eccube\Entity\Customer;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Order;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Tests\EccubeTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Plugin\Coupon44\Entity\Coupon;
use Plugin\Coupon44\Entity\CouponOrder;
use Plugin\Coupon44\Repository\CouponOrderRepository;
use Plugin\Coupon44\Repository\CouponRepository;
use Plugin\Coupon44\Service\CouponService;
use Plugin\Coupon44\Service\PurchaseFlow\Processor\CouponStateProcessor;
use Plugin\Coupon44\Tests\Fixtures\CreateCouponTrait;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * CouponStateProcessorTest
 *
 * 受注ステータス変更時のクーポン利用枚数の増減を, Web リクエストを介さず直接検証する。
 *
 * キャンセル遷移を含む受注編集の Web テスト (OrderControllerTest::testOrderEditWithCouponCancel)
 * は本体のテストハーネス制約 (EC-CUBE/ec-cube#7016) で実行できないため, 本テストで代替する。
 * 画面を通したキャンセル遷移は E2E (e2e/admin_order_cancel_coupon.test.ts) で検証する。
 */
class CouponStateProcessorTest extends EccubeTestCase
{
    use CreateCouponTrait;

    /**
     * @var CouponStateProcessor
     */
    protected $processor;

    /**
     * @var CouponService
     */
    protected $couponService;

    /**
     * @var CouponOrderRepository
     */
    protected $couponOrderRepository;

    /**
     * @var CouponRepository
     */
    protected $couponRepository;

    /**
     * @var Customer
     */
    protected $Customer;

    /**
     * @var Order
     */
    protected $Order;

    /**
     * @var PurchaseContext
     */
    protected $context;

    public function setUp(): void
    {
        parent::setUp();
        $this->couponService = self::getContainer()->get(CouponService::class);
        $this->couponRepository = $this->entityManager->getRepository(Coupon::class);
        $this->couponOrderRepository = $this->entityManager->getRepository(CouponOrder::class);

        $this->processor = new CouponStateProcessor(
            $this->entityManager,
            $this->couponService,
            $this->couponRepository,
            $this->couponOrderRepository
        );

        $this->Customer = $this->createCustomer();
        $this->Order = $this->createOrder($this->Customer);
        $this->context = new PurchaseContext($this->Order, $this->Customer);
    }

    /**
     * キャンセル・返品へ遷移した場合はクーポンの利用枚数が戻る.
     */
    #[DataProvider('returnCouponOrderStatusProvider')]
    public function testProcessReturnsCouponUseTime(int $orderStatusId): void
    {
        $Coupon = $this->createCouponOrderForOrder();
        // 発行枚数 (100) で頭打ちになるため, 1枚使用済みの状態にする
        $Coupon->setCouponUseTime(99);
        $this->entityManager->flush();

        $this->Order->setOrderStatus($this->entityManager->find(OrderStatus::class, $orderStatusId));

        $this->processor->process($this->Order, $this->context);

        self::assertSame(100, $Coupon->getCouponUseTime(), 'クーポンの利用枚数が戻っている');

        $CouponOrder = $this->couponOrderRepository->getCouponOrder($this->Order->getPreOrderId());
        self::assertTrue($CouponOrder->getOrderChangeStatus(), 'ステータス変更済みになっている');
        self::assertNull($CouponOrder->getOrderDate(), '注文日が消えている');
    }

    /**
     * process() の分岐条件には PROCESSING も含まれるが, supports() が対象ステータスを
     * NEW / PAID / IN_PROGRESS / DELIVERED / CANCEL / RETURNED に限定しているため
     * PROCESSING は process() に到達しない (testProcessWithUnsupportedOrderStatus で検証).
     *
     * @return array<string, array<int>>
     */
    public static function returnCouponOrderStatusProvider(): array
    {
        return [
            'キャンセル' => [OrderStatus::CANCEL],
            '返品' => [OrderStatus::RETURNED],
        ];
    }

    /**
     * supports() の対象外ステータスでは何もしない.
     */
    #[DataProvider('unsupportedOrderStatusProvider')]
    public function testProcessWithUnsupportedOrderStatus(int $orderStatusId): void
    {
        $Coupon = $this->createCouponOrderForOrder();
        $Coupon->setCouponUseTime(99);
        $this->entityManager->flush();

        $this->Order->setOrderStatus($this->entityManager->find(OrderStatus::class, $orderStatusId));

        $this->processor->process($this->Order, $this->context);

        self::assertSame(99, $Coupon->getCouponUseTime(), 'クーポンの利用枚数は変わらない');

        $CouponOrder = $this->couponOrderRepository->getCouponOrder($this->Order->getPreOrderId());
        self::assertFalse($CouponOrder->getOrderChangeStatus(), 'ステータス変更フラグは変わらない');
    }

    /**
     * @return array<string, array<int>>
     */
    public static function unsupportedOrderStatusProvider(): array
    {
        return [
            '購入処理中' => [OrderStatus::PROCESSING],
            '決済処理中' => [OrderStatus::PENDING],
        ];
    }

    /**
     * 利用枚数の戻しは発行枚数を超えない.
     */
    public function testProcessDoesNotExceedCouponRelease(): void
    {
        $Coupon = $this->createCouponOrderForOrder();
        $Coupon->setCouponRelease(100);
        $Coupon->setCouponUseTime(100);
        $this->entityManager->flush();

        $this->Order->setOrderStatus($this->entityManager->find(OrderStatus::class, OrderStatus::CANCEL));

        $this->processor->process($this->Order, $this->context);

        self::assertSame(100, $Coupon->getCouponUseTime(), '発行枚数で頭打ちになっている');
    }

    /**
     * 既に戻し済みの受注を対象外のステータスへ戻した場合は再度クーポンを使用状態にする.
     */
    public function testProcessReUsesCouponWhenStatusIsRestored(): void
    {
        $Coupon = $this->createCouponOrderForOrder();
        $Coupon->setCouponUseTime(99);

        $CouponOrder = $this->couponOrderRepository->getCouponOrder($this->Order->getPreOrderId());
        // キャンセル等で戻し済みの状態にする
        $CouponOrder->setOrderChangeStatus(true);
        $CouponOrder->setOrderDate(null);
        $this->entityManager->flush();

        $this->Order->setOrderStatus($this->entityManager->find(OrderStatus::class, OrderStatus::NEW));

        $this->processor->process($this->Order, $this->context);

        self::assertSame(98, $Coupon->getCouponUseTime(), 'クーポンが再度使用されている');
        self::assertFalse($CouponOrder->getOrderChangeStatus(), 'ステータス変更フラグが戻っている');
        self::assertNotNull($CouponOrder->getOrderDate(), '注文日が設定されている');
    }

    /**
     * クーポン受注情報が存在しない場合は何もしない.
     */
    public function testProcessWithoutCouponOrder(): void
    {
        $Coupon = $this->getCoupon();
        $Coupon->setCouponUseTime(99);
        $this->entityManager->flush();

        $this->Order->setOrderStatus($this->entityManager->find(OrderStatus::class, OrderStatus::CANCEL));

        $this->processor->process($this->Order, $this->context);

        self::assertSame(99, $Coupon->getCouponUseTime(), 'クーポンの利用枚数は変わらない');
    }

    /**
     * テスト対象の受注にクーポンを適用し, 適用したクーポンを返す.
     */
    private function createCouponOrderForOrder(): Coupon
    {
        $Coupon = $this->getCoupon();
        self::getContainer()->get('security.token_storage')->setToken(
            new UsernamePasswordToken(
                $this->Customer, 'customer', $this->Customer->getRoles()
            )
        );
        $products = $this->couponService->existsCouponProduct($Coupon, $this->Order);
        $discount = $this->couponService->recalcOrder($Coupon, $products);
        $this->couponService->saveCouponOrder(
            $this->Order, $Coupon, $Coupon->getCouponCd(), $this->Customer, $discount
        );

        $CouponOrder = $this->couponOrderRepository->getCouponOrder($this->Order->getPreOrderId());
        $CouponOrder->setOrderDate(new \DateTime());
        $this->entityManager->flush();

        return $Coupon;
    }
}
