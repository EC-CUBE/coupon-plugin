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

use Eccube\Entity\Cart;
use Eccube\Entity\Customer;
use Eccube\Entity\ItemHolderInterface;
use Eccube\Entity\ItemInterface;
use Eccube\Entity\Master\TaxType;
use Eccube\Entity\Order;
use Eccube\Entity\OrderItem;
use Eccube\Entity\TaxRule;
use Eccube\Repository\TaxRuleRepository;
use Eccube\Service\PurchaseFlow\InvalidItemException;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\TaxRuleService;
use Eccube\Tests\EccubeTestCase;
use Plugin\Coupon44\Entity\Coupon;
use Plugin\Coupon44\Entity\CouponOrder;
use Plugin\Coupon44\Repository\CouponOrderRepository;
use Plugin\Coupon44\Repository\CouponRepository;
use Plugin\Coupon44\Service\CouponService;
use Plugin\Coupon44\Service\PurchaseFlow\Processor\CouponProcessor;
use Plugin\Coupon44\Tests\Fixtures\CreateCouponTrait;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * CouponProcessorTest
 */
class CouponProcessorTest extends EccubeTestCase
{
    use CreateCouponTrait;

    /**
     * @var CouponProcessor
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
     * @var TaxRuleService
     */
    protected $taxRuleService;

    /**
     * @var TaxRuleRepository
     */
    protected $taxRuleRepository;

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
        $this->taxRuleService = self::getContainer()->get(TaxRuleService::class);
        $this->taxRuleRepository = $this->entityManager->getRepository(TaxRule::class);

        $this->processor = new CouponProcessor(
            $this->entityManager,
            $this->couponService,
            $this->couponRepository,
            $this->couponOrderRepository,
            $this->taxRuleService,
            $this->taxRuleRepository
        );

        $this->Customer = $this->createCustomer();
        $this->Order = $this->createOrder($this->Customer);
        $this->context = new PurchaseContext($this->Order, $this->Customer);
    }

    public function testGetInstance(): void
    {
        $this->assertInstanceOf(CouponProcessor::class, $this->processor);
    }

    public function testSupportWithCart(): void
    {
        $this->assertFalse($this->wrapperOfSupports($this->processor, new Cart()));
    }

    public function testSupportWithOrder(): void
    {
        $this->assertTrue($this->wrapperOfSupports($this->processor, $this->Order));
    }

    public function testAddCouponDiscountItem(): void
    {
        $Coupon = $this->getCoupon();
        self::getContainer()->get('security.token_storage')->setToken(
            new UsernamePasswordToken(
                $this->Customer, 'customer', $this->Customer->getRoles()
            )
        );
        $this->couponService->saveCouponOrder($this->Order, $Coupon, $Coupon->getCouponCd(), $this->Customer, 1000);
        /** @var CouponOrder $CouponOrder */
        $CouponOrder = $this->couponOrderRepository->findOneBy(['order_id' => $this->Order->getId()]);

        $this->wrapperOfAddCouponDiscountItem($this->processor, $this->Order, $CouponOrder);

        $OrderItems = $this->Order->getItems()->filter(function (ItemInterface $OrderItem) {
            return $OrderItem instanceof OrderItem && $OrderItem->getProcessorName() === CouponProcessor::class;
        });

        $this->assertCount(1, $OrderItems->toArray());

        /** @var OrderItem */
        $OrderItem = $OrderItems->first();
        $this->assertEquals(-1000, $OrderItem->getPrice());
        $this->assertEquals($Coupon->getCouponName(), $OrderItem->getProductName());
        // クーポン値引き明細は不課税で追加される
        $this->assertSame(TaxType::NON_TAXABLE, $OrderItem->getTaxType()->getId());
        // OrderItemType が「値引き」で追加される (受注金額の集計に影響する)
        $this->assertTrue($OrderItem->isDiscount(), '値引き明細として追加されている');
    }

    public function testRemoveCouponDiscountItem(): void
    {
        $Coupon = $this->getCoupon();
        self::getContainer()->get('security.token_storage')->setToken(
            new UsernamePasswordToken(
                $this->Customer, 'customer', $this->Customer->getRoles()
            )
        );
        $this->couponService->saveCouponOrder($this->Order, $Coupon, $Coupon->getCouponCd(), $this->Customer, 1000);
        /** @var CouponOrder $CouponOrder */
        $CouponOrder = $this->couponOrderRepository->findOneBy(['order_id' => $this->Order->getId()]);

        $this->wrapperOfAddCouponDiscountItem($this->processor, $this->Order, $CouponOrder);

        // Remove to OrderItem of Coupon
        $this->wrapperOfRemoveCouponDiscountItem($this->processor, $this->Order, $CouponOrder);

        $OrderItems = $this->Order->getItems()->filter(function (ItemInterface $OrderItem) {
            return $OrderItem instanceof OrderItem && $OrderItem->getProcessorName() === CouponProcessor::class;
        });

        $this->assertTrue($OrderItems->isEmpty(), 'クーポンの明細が削除されている');
    }

    public function testProcess(): void
    {
        $Coupon = $this->getCoupon();
        self::getContainer()->get('security.token_storage')->setToken(
            new UsernamePasswordToken(
                $this->Customer, 'customer', $this->Customer->getRoles()
            )
        );
        $this->couponService->saveCouponOrder($this->Order, $Coupon, $Coupon->getCouponCd(), $this->Customer, 1000);
        /** @var CouponOrder $CouponOrder */
        $CouponOrder = $this->couponOrderRepository->findOneBy(['order_id' => $this->Order->getId()]);

        // Add to exists OrderItem
        $this->wrapperOfAddCouponDiscountItem($this->processor, $this->Order, $CouponOrder);

        $this->processor->process($this->Order, $this->context);

        $OrderItems = $this->Order->getItems()->filter(function (ItemInterface $OrderItem) {
            return $OrderItem instanceof OrderItem && $OrderItem->getProcessorName() === CouponProcessor::class;
        });

        $this->assertFalse($OrderItems->isEmpty(), 'クーポン明細が追加されている');

        /** @var OrderItem */
        $OrderItem = $OrderItems->first();
        $this->assertEquals(-1000, $OrderItem->getPrice());
        $this->assertEquals($Coupon->getCouponName(), $OrderItem->getProductName());
        // クーポン値引き明細は不課税で追加される
        $this->assertSame(TaxType::NON_TAXABLE, $OrderItem->getTaxType()->getId());
        // OrderItemType が「値引き」で追加される (受注金額の集計に影響する)
        $this->assertTrue($OrderItem->isDiscount(), '値引き明細として追加されている');
    }

    public function testProcessWithNotExistsOrderItem(): void
    {
        $Coupon = $this->getCoupon();
        self::getContainer()->get('security.token_storage')->setToken(
            new UsernamePasswordToken(
                $this->Customer, 'customer', $this->Customer->getRoles()
            )
        );
        $this->couponService->saveCouponOrder($this->Order, $Coupon, $Coupon->getCouponCd(), $this->Customer, 1000);

        $this->processor->process($this->Order, $this->context);

        $OrderItems = $this->Order->getItems()->filter(function (ItemInterface $OrderItem) {
            return $OrderItem instanceof OrderItem && $OrderItem->getProcessorName() === CouponProcessor::class;
        });

        $this->assertFalse($OrderItems->isEmpty(), 'クーポン明細が追加されている');

        /** @var OrderItem */
        $OrderItem = $OrderItems->first();
        $this->assertEquals(-1000, $OrderItem->getPrice());
        $this->assertEquals($Coupon->getCouponName(), $OrderItem->getProductName());
        // クーポン値引き明細は不課税で追加される
        $this->assertSame(TaxType::NON_TAXABLE, $OrderItem->getTaxType()->getId());
        // OrderItemType が「値引き」で追加される (受注金額の集計に影響する)
        $this->assertTrue($OrderItem->isDiscount(), '値引き明細として追加されている');
    }

    public function testProcessWithCouponOrderIsNotFound(): void
    {
        $this->processor->process($this->Order, $this->context);

        $OrderItems = $this->Order->getItems()->filter(function (ItemInterface $OrderItem) {
            return $OrderItem instanceof OrderItem && $OrderItem->getProcessorName() === CouponProcessor::class;
        });

        $this->assertTrue($OrderItems->isEmpty(), 'クーポン明細は存在しない');
    }

    public function testProcessWithNotSupport(): void
    {
        $this->processor->process(new Cart(), $this->context);

        $OrderItems = $this->Order->getItems()->filter(function (ItemInterface $OrderItem) {
            return $OrderItem instanceof OrderItem && $OrderItem->getProcessorName() === CouponProcessor::class;
        });

        $this->assertTrue($OrderItems->isEmpty(), 'クーポン明細は存在しない');
    }

    public function testValidate(): void
    {
        $Coupon = $this->getCoupon();
        self::getContainer()->get('security.token_storage')->setToken(
            new UsernamePasswordToken(
                $this->Customer, 'customer', $this->Customer->getRoles()
            )
        );
        $products = $this->couponService->existsCouponProduct($Coupon, $this->Order);
        $discount = $this->couponService->recalcOrder($Coupon, $products);
        $this->couponService->saveCouponOrder($this->Order, $Coupon, $Coupon->getCouponCd(), $this->Customer, $discount);

        try {
            $this->wrapperOfValidate($this->processor, $this->Order, $this->context);
            $this->addToAssertionCount(1);
        } catch (InvalidItemException $e) {
            $this->fail($e->getMessage());
        }
    }

    /**
     * RoundingType 未設定の明細でも validate() が失敗しないこと.
     *
     * 複数配送の確定時 (ShippingMultipleController) は RoundingType を設定せずに OrderItem が
     * 作り直され, 本体の TaxProcessor より先に itemHolderValidator が走る。フォールバックが
     * 無いと CouponService::isLowerLimitCoupon() で TypeError になり購入フローが 500 になる。
     */
    public function testValidateWithoutRoundingType(): void
    {
        $Coupon = $this->getCoupon();
        self::getContainer()->get('security.token_storage')->setToken(
            new UsernamePasswordToken(
                $this->Customer, 'customer', $this->Customer->getRoles()
            )
        );

        // 複数配送の確定時と同じ状態 (RoundingType 未設定) にする
        foreach ($this->Order->getProductOrderItems() as $OrderItem) {
            $OrderItem->setRoundingType(null);
        }
        $this->entityManager->flush();

        $products = $this->couponService->existsCouponProduct($Coupon, $this->Order);
        self::assertNull(current($products)['rounding_type_id']);

        $discount = $this->couponService->recalcOrder($Coupon, $products);
        $this->couponService->saveCouponOrder($this->Order, $Coupon, $Coupon->getCouponCd(), $this->Customer, $discount);

        try {
            $this->wrapperOfValidate($this->processor, $this->Order, $this->context);
            $this->addToAssertionCount(1);
        } catch (InvalidItemException $e) {
            $this->fail($e->getMessage());
        }
    }

    public function testValidateWithNotSupport(): void
    {
        try {
            $this->wrapperOfValidate($this->processor, new Cart(), $this->context);
            $this->addToAssertionCount(1);
        } catch (InvalidItemException $e) {
            $this->fail($e->getMessage());
        }
    }

    public function testValidateWithNotCouponOrder(): void
    {
        try {
            $this->wrapperOfValidate($this->processor, $this->Order, $this->context);
            $this->addToAssertionCount(1);
        } catch (InvalidItemException $e) {
            $this->fail($e->getMessage());
        }
    }

    public function testValidateWithNotActiveCoupon(): void
    {
        $Coupon = $this->getCoupon();
        self::getContainer()->get('security.token_storage')->setToken(
            new UsernamePasswordToken(
                $this->Customer, 'customer', $this->Customer->getRoles()
            )
        );
        $products = $this->couponService->existsCouponProduct($Coupon, $this->Order);
        $discount = $this->couponService->recalcOrder($Coupon, $products);
        $this->couponService->saveCouponOrder($this->Order, $Coupon, $Coupon->getCouponCd(), $this->Customer, $discount);

        $Coupon->setEnableFlag(false);
        $this->entityManager->flush();
        try {
            $this->wrapperOfValidate($this->processor, $this->Order, $this->context);
            $this->fail();
        } catch (InvalidItemException $e) {
            $this->assertEquals(trans('plugin_coupon.front.shopping.notfound'), $e->getMessage());
        }
    }

    public function testValidateMemberOnlyCouponWithNonCustomer(): void
    {
        $Coupon = $this->getCoupon();
        $Coupon->setCouponMember(true);

        // ゲスト購入の Order を生成する
        $this->Order->setCustomer(null);
        $this->entityManager->flush();
        self::getContainer()->get('security.token_storage')->setToken(
            new UsernamePasswordToken(
                new Customer(), 'customer', ['IS_AUTHENTICATED_ANONYMOUSLY']
            )
        );

        $products = $this->couponService->existsCouponProduct($Coupon, $this->Order);
        $discount = $this->couponService->recalcOrder($Coupon, $products);
        $this->couponService->saveCouponOrder($this->Order, $Coupon, $Coupon->getCouponCd(), new Customer(), $discount);

        try {
            $this->wrapperOfValidate($this->processor, $this->Order, $this->context);
            $this->fail();
        } catch (InvalidItemException $e) {
            $this->assertEquals(trans('plugin_coupon.front.shopping.member'), $e->getMessage());
        }
    }

    public function testValidateWithNonCustomer(): void
    {
        $Coupon = $this->getCoupon();
        $Coupon->setCouponMember(false);

        // ゲスト購入の Order を生成する
        $this->Order->setCustomer(null);
        $this->entityManager->flush();
        self::getContainer()->get('security.token_storage')->setToken(
            new UsernamePasswordToken(
                new Customer(), 'customer', ['IS_AUTHENTICATED_ANONYMOUSLY']
            )
        );

        $products = $this->couponService->existsCouponProduct($Coupon, $this->Order);
        $discount = $this->couponService->recalcOrder($Coupon, $products);
        $this->couponService->saveCouponOrder($this->Order, $Coupon, $Coupon->getCouponCd(), new Customer(), $discount);

        try {
            $this->wrapperOfValidate($this->processor, $this->Order, $this->context);
            $this->addToAssertionCount(1);
        } catch (InvalidItemException $e) {
            $this->fail('ゲストでも使用可能なクーポンであるはず');
        }
    }

    public function testValidateWithProduct(): void
    {
        $Coupon = $this->getCoupon(Coupon::PRODUCT);
        self::getContainer()->get('security.token_storage')->setToken(
            new UsernamePasswordToken(
                $this->Customer, 'customer', $this->Customer->getRoles()
            )
        );
        $products = $this->couponService->existsCouponProduct($Coupon, $this->Order);
        $discount = $this->couponService->recalcOrder($Coupon, $products);
        $this->couponService->saveCouponOrder($this->Order, $Coupon, $Coupon->getCouponCd(), $this->Customer, $discount);

        try {
            $this->wrapperOfValidate($this->processor, $this->Order, $this->context);
            $this->fail();
        } catch (InvalidItemException $e) {
            $this->assertEquals(trans('plugin_coupon.front.shopping.couponusetime'), $e->getMessage());
        }
    }

    public function testValidateWithChangeOrder(): void
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
            $this->Order, $Coupon, $Coupon->getCouponCd(), $this->Customer,
            1); // CouponOrder の金額を変更する

        try {
            $this->wrapperOfValidate($this->processor, $this->Order, $this->context);
            $this->fail();
        } catch (InvalidItemException $e) {
            $this->assertEquals(trans('plugin_coupon.front.shopping.changeorder'), $e->getMessage());
        }
    }

    public function testValidateWithLowerLimit(): void
    {
        $Coupon = $this->getCoupon();
        $Coupon->setCouponLowerLimit(9999999999);
        $this->entityManager->flush();
        self::getContainer()->get('security.token_storage')->setToken(
            new UsernamePasswordToken(
                $this->Customer, 'customer', $this->Customer->getRoles()
            )
        );
        $products = $this->couponService->existsCouponProduct($Coupon, $this->Order);
        $discount = $this->couponService->recalcOrder($Coupon, $products);
        $this->couponService->saveCouponOrder(
            $this->Order, $Coupon, $Coupon->getCouponCd(), $this->Customer, $discount);

        try {
            $this->wrapperOfValidate($this->processor, $this->Order, $this->context);
            $this->fail();
        } catch (InvalidItemException $e) {
            $this->assertEquals(trans('plugin_coupon.front.shopping.lowerlimit', ['lowerLimit' => number_format(9999999999)]), $e->getMessage());
        }
    }

    public function testValidateWithUseTime(): void
    {
        $Coupon = $this->getCoupon();
        $Coupon->setCouponUseTime(0);
        $this->entityManager->flush();
        self::getContainer()->get('security.token_storage')->setToken(
            new UsernamePasswordToken(
                $this->Customer, 'customer', $this->Customer->getRoles()
            )
        );
        $products = $this->couponService->existsCouponProduct($Coupon, $this->Order);
        $discount = $this->couponService->recalcOrder($Coupon, $products);
        $this->couponService->saveCouponOrder(
            $this->Order, $Coupon, $Coupon->getCouponCd(), $this->Customer, $discount);

        try {
            $this->wrapperOfValidate($this->processor, $this->Order, $this->context);
            $this->fail();
        } catch (InvalidItemException $e) {
            $this->assertEquals(trans('plugin_coupon.front.shopping.couponusetime'), $e->getMessage());
        }
    }

    public function testPrepare(): void
    {
        $Coupon = $this->getCoupon();
        $useTime = $Coupon->getCouponUseTime();
        self::getContainer()->get('security.token_storage')->setToken(
            new UsernamePasswordToken(
                $this->Customer, 'customer', $this->Customer->getRoles()
            )
        );
        $products = $this->couponService->existsCouponProduct($Coupon, $this->Order);
        $discount = $this->couponService->recalcOrder($Coupon, $products);
        $this->couponService->saveCouponOrder($this->Order, $Coupon, $Coupon->getCouponCd(), $this->Customer, $discount);

        $this->processor->prepare($this->Order, $this->context);

        $this->expected = $useTime - 1;
        $this->actual = $Coupon->getCouponUseTime();
        $this->verify();
    }

    public function testPrepareWithNotSupport(): void
    {
        $Coupon = $this->getCoupon();
        $useTime = $Coupon->getCouponUseTime();
        self::getContainer()->get('security.token_storage')->setToken(
            new UsernamePasswordToken(
                $this->Customer, 'customer', $this->Customer->getRoles()
            )
        );
        $products = $this->couponService->existsCouponProduct($Coupon, $this->Order);
        $discount = $this->couponService->recalcOrder($Coupon, $products);
        $this->couponService->saveCouponOrder($this->Order, $Coupon, $Coupon->getCouponCd(), $this->Customer, $discount);

        $this->processor->prepare(new Cart(), $this->context);

        $this->expected = $useTime - 1;
        $this->actual = $Coupon->getCouponUseTime();
        $this->assertNotEquals($this->expected, $this->actual, 'サポートしない ItemHolder なので一致しないはず');
    }

    public function testPrepareWithCouponOrderNotFound(): void
    {
        $Coupon = $this->getCoupon();
        $useTime = $Coupon->getCouponUseTime();
        self::getContainer()->get('security.token_storage')->setToken(
            new UsernamePasswordToken(
                $this->Customer, 'customer', $this->Customer->getRoles()
            )
        );
        $products = $this->couponService->existsCouponProduct($Coupon, $this->Order);
        $discount = $this->couponService->recalcOrder($Coupon, $products);
        // CouponOrder を登録しない $this->couponService->saveCouponOrder($this->Order, $Coupon, $Coupon->getCouponCd(), $this->Customer, $discount);

        $this->processor->prepare($this->Order, $this->context);

        $this->expected = $useTime - 1;
        $this->actual = $Coupon->getCouponUseTime();
        $this->assertNotEquals($this->expected, $this->actual, 'CouponOrder が存在しないので一致しないはず');
    }

    public function testPrepareWithCouponNotActive(): void
    {
        $Coupon = $this->getCoupon();
        $useTime = $Coupon->getCouponUseTime();
        self::getContainer()->get('security.token_storage')->setToken(
            new UsernamePasswordToken(
                $this->Customer, 'customer', $this->Customer->getRoles()
            )
        );
        $products = $this->couponService->existsCouponProduct($Coupon, $this->Order);
        $discount = $this->couponService->recalcOrder($Coupon, $products);
        $this->couponService->saveCouponOrder($this->Order, $Coupon, $Coupon->getCouponCd(), $this->Customer, $discount);

        $Coupon->setEnableFlag(false);
        $this->entityManager->flush();

        $this->processor->prepare($this->Order, $this->context);

        $this->expected = $useTime - 1;
        $this->actual = $Coupon->getCouponUseTime();
        $this->assertNotEquals($this->expected, $this->actual, 'Coupon が無効なので一致しないはず');
    }

    public function testRollback(): void
    {
        $Coupon = $this->getCoupon();
        self::getContainer()->get('security.token_storage')->setToken(
            new UsernamePasswordToken(
                $this->Customer, 'customer', $this->Customer->getRoles()
            )
        );
        $this->couponService->saveCouponOrder($this->Order, $Coupon, $Coupon->getCouponCd(), $this->Customer, 1000);
        /** @var CouponOrder $CouponOrder */
        $CouponOrder = $this->couponOrderRepository->findOneBy(['order_id' => $this->Order->getId()]);
        $this->wrapperOfAddCouponDiscountItem($this->processor, $this->Order, $CouponOrder);
        $this->entityManager->flush();

        // rollback to Coupon
        $this->processor->rollback($this->Order, $this->context);

        $OrderItems = $this->Order->getItems()->filter(function (ItemInterface $OrderItem) {
            return $OrderItem instanceof OrderItem && $OrderItem->getProcessorName() === CouponProcessor::class;
        });

        $this->assertTrue($OrderItems->isEmpty(), 'クーポンの明細が削除されている');
    }

    public function testRollbackWithNotSupport(): void
    {
        $Coupon = $this->getCoupon();
        $useTime = $Coupon->getCouponUseTime();
        self::getContainer()->get('security.token_storage')->setToken(
            new UsernamePasswordToken(
                $this->Customer, 'customer', $this->Customer->getRoles()
            )
        );
        $products = $this->couponService->existsCouponProduct($Coupon, $this->Order);
        $discount = $this->couponService->recalcOrder($Coupon, $products);
        $this->couponService->saveCouponOrder($this->Order, $Coupon, $Coupon->getCouponCd(), $this->Customer, $discount);

        $this->processor->rollback(new Cart(), $this->context);

        $this->expected = $useTime - 1;
        $this->actual = $Coupon->getCouponUseTime();
        $this->assertNotEquals($this->expected, $this->actual, 'サポートしない ItemHolder なので一致しないはず');
    }

    private function wrapperOfSupports(CouponProcessor $instance, ItemHolderInterface $itemHolder): mixed
    {
        $refMethod = new \ReflectionMethod(CouponProcessor::class, 'supports');
        $refMethod->setAccessible(true);

        return $refMethod->invoke($instance, $itemHolder);
    }

    private function wrapperOfAddCouponDiscountItem(CouponProcessor $instance, ItemHolderInterface $itemHolder, CouponOrder $CouponOrder): mixed
    {
        $refMethod = new \ReflectionMethod(CouponProcessor::class, 'addCouponDiscountItem');
        $refMethod->setAccessible(true);

        return $refMethod->invoke($instance, $itemHolder, $CouponOrder);
    }

    private function wrapperOfRemoveCouponDiscountItem(CouponProcessor $instance, ItemHolderInterface $itemHolder, CouponOrder $CouponOrder): mixed
    {
        $refMethod = new \ReflectionMethod(CouponProcessor::class, 'removeCouponDiscountItem');
        $refMethod->setAccessible(true);

        return $refMethod->invoke($instance, $itemHolder, $CouponOrder);
    }

    private function wrapperOfValidate(CouponProcessor $instance, ItemHolderInterface $itemHolder, PurchaseContext $context): mixed
    {
        $refMethod = new \ReflectionMethod(CouponProcessor::class, 'validate');
        $refMethod->setAccessible(true);

        return $refMethod->invoke($instance, $itemHolder, $context);
    }
}
