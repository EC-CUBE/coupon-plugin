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

namespace Plugin\Coupon44\Tests\Service;

use Eccube\Entity\ItemInterface;
use Eccube\Entity\Master\OrderItemType;
use Eccube\Entity\Master\RoundingType;
use Eccube\Entity\Order;
use Eccube\Entity\OrderItem;
use Eccube\Entity\ProductCategory;
use Eccube\Entity\TaxRule;
use Eccube\Repository\Master\OrderItemTypeRepository;
use Eccube\Repository\TaxRuleRepository;
use Eccube\Service\TaxRuleService;
use Eccube\Tests\EccubeTestCase;
use Eccube\Tests\Fixture\Generator;
use Plugin\Coupon44\Entity\Coupon;
use Plugin\Coupon44\Entity\CouponDetail;
use Plugin\Coupon44\Entity\CouponOrder;
use Plugin\Coupon44\Repository\CouponOrderRepository;
use Plugin\Coupon44\Repository\CouponRepository;
use Plugin\Coupon44\Service\CouponService;
use Plugin\Coupon44\Service\PurchaseFlow\Processor\CouponProcessor;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Class CouponServiceTest.
 */
class CouponServiceTest extends EccubeTestCase
{
    /**
     * @var CouponOrderRepository
     */
    private $couponOrderRepository;

    /**
     * @var CouponRepository
     */
    private $couponRepository;

    /**
     * @var TaxRuleRepository
     */
    private $taxRuleRepository;

    /**
     * @var TaxRuleService
     */
    private $taxRuleService;

    /**
     * @var CouponService
     */
    private $couponService;

    /**
     * @var OrderItemTypeRepository
     */
    private $orderItemTypeRepository;

    public function setUp(): void
    {
        parent::setUp();
        $this->couponRepository = $this->entityManager->getRepository(Coupon::class);
        $this->couponOrderRepository = $this->entityManager->getRepository(CouponOrder::class);
        $this->taxRuleRepository = $this->entityManager->getRepository(TaxRule::class);
        $this->taxRuleService = self::getContainer()->get(TaxRuleService::class);
        $this->couponService = self::getContainer()->get(CouponService::class);
        $this->orderItemTypeRepository = $this->entityManager->getRepository(OrderItemType::class);
    }

    /**
     * testGenerateCouponCd.
     */
    public function testGenerateCouponCd(): void
    {
        $couponCd = $this->couponService->generateCouponCd(20);

        $this->actual = strlen($couponCd);
        $this->expected = 20;

        $this->verify();
    }

    /**
     * testExistsCouponProduct.
     */
    public function testExistsCouponProduct(): void
    {
        /** @var Coupon $Coupon */
        $Coupon = $this->getCoupon();

        $Customer = $this->createCustomer();
        $Order = $this->createOrder($Customer);

        $details = $Coupon->getCouponDetails();
        /** @var CouponDetail $CouponDetail */
        $CouponDetail = $details[0];
        $Product = $CouponDetail->getProduct();
        $ProductClasses = $Product->getProductClasses();
        $ProductClass = $ProductClasses[0];

        $orderItem = new OrderItem();
        // デフォルト課税規則
        $TaxRule = $this->taxRuleRepository->getByRule();
        $OrderItemTypeProduct = $this->orderItemTypeRepository->find(OrderItemType::PRODUCT);
        $orderItem->setProduct($Product)
            ->setProductClass($ProductClass)
            ->setProductName($Product->getName())
            ->setProductCode($ProductClass->getCode())
            ->setOrderItemType($OrderItemTypeProduct)
            ->setPrice($ProductClass->getPrice02())
            ->setQuantity('1')
            ->setTaxRuleId($TaxRule->getId())
            ->setTaxRate($TaxRule->getTaxRate());
        $this->entityManager->persist($orderItem);
        $orderItem->setOrder($Order);
        $Order->addOrderItem($orderItem);
        $this->entityManager->flush();

        $products = $this->couponService->existsCouponProduct($Coupon, $Order);
        $this->actual = count($products);
        $this->expected = 1;
        $this->verify();
    }

    /**
     * testExistsCouponProductNot.
     */
    public function testExistsCouponProductNot(): void
    {
        /** @var Coupon $Coupon */
        $Coupon = $this->getCoupon();

        $Customer = $this->createCustomer();

        $Order = $this->createOrder($Customer);

        $products = $this->couponService->existsCouponProduct($Coupon, $Order);
        $this->actual = count($products);
        $this->expected = 0;
        $this->verify();
    }

    /**
     * testExistsCouponProduct2.
     */
    public function testExistsCouponProductTypeCategory(): void
    {
        /** @var Generator $Generator */
        $Generator = self::getContainer()->get(Generator::class);
        /** @var Coupon $Coupon */
        $Coupon = $this->getCoupon(Coupon::CATEGORY);

        $Customer = $this->createCustomer();

        $details = $Coupon->getCouponDetails();
        /** @var CouponDetail $CouponDetail */
        $CouponDetail = $details[0];
        $Category = $CouponDetail->getCategory();
        /** @var ProductCategory $ProductCategory */
        $ProductCategory = $Category->getProductCategories()->first();
        $Product = $ProductCategory->getProduct();
        $ProductClasses = $Product->getProductClasses()->filter(
            function ($ProductClass) {
                return $ProductClass->isVisible();
            });
        $Order = $Generator->createOrder($Customer, [$ProductClasses->first()]);
        $products = $this->couponService->existsCouponProduct($Coupon, $Order);

        $this->actual = count($products);

        $this->expected = 1;

        $this->verify();
    }

    /**
     * testExistsCouponProductAll
     */
    public function testExistsCouponProductAll(): void
    {
        $orderItemVolume = 5;
        /** @var Generator $Generator */
        $Generator = self::getContainer()->get(Generator::class);
        /** @var Coupon $Coupon */
        $Coupon = $this->getCoupon(Coupon::ALL);

        $Customer = $this->createCustomer();

        $details = $Coupon->getCouponDetails();
        $Product = $Generator->createProduct(null, $orderItemVolume);
        $Order = $Generator->createOrder($Customer, $Product->getProductClasses()->toArray());
        $products = $this->couponService->existsCouponProduct($Coupon, $Order);

        $this->actual = count($products);

        $this->expected = $orderItemVolume;

        $this->verify();
    }

    /**
     * testExistsCouponProductWithMultiple
     * https://github.com/EC-CUBE/coupon-plugin/issues/102 のテストケース
     */
    public function testExistsCouponProductWithMultiple(): void
    {
        $orderItemVolume = 2;
        /** @var Generator $Generator */
        $Generator = self::getContainer()->get(Generator::class);
        /** @var Coupon $Coupon */
        $Coupon = $this->getCoupon(Coupon::ALL);

        $Customer = $this->createCustomer();

        $details = $Coupon->getCouponDetails();
        $Product = $Generator->createProduct(null, $orderItemVolume);
        // 複数配送など, ProductClass が重複した明細を生成
        $Order = $Generator->createOrder(
            $Customer,
            array_merge(
                $Product->getProductClasses()->toArray(),
                $Product->getProductClasses()->toArray()
            )
        );
        $products = $this->couponService->existsCouponProduct($Coupon, $Order);

        $this->actual = count($products);
        $this->expected = $orderItemVolume;
        $this->verify();

        $this->actual = array_reduce($products, function ($carry, $item) {
            return $carry + $item['quantity'];
        }, 0);
        $this->expected = array_reduce($Order->getProductOrderItems(), function (int $carry, OrderItem $item): int {
            return $carry + (int) $item->getQuantity();
        }, 0);
        $this->verify('ProductClass が重複していても数量が一致するはず');
    }

    /**
     * testSaveCouponOrder.
     */
    public function testSaveCouponOrder(): void
    {
        /** @var Coupon $Coupon */
        $Coupon = $this->getCoupon();

        $Customer = $this->createCustomer();

        $Order = $this->createOrder($Customer);
        if (!$Order->getPreOrderId()) {
            $Order->setPreOrderId('dummy');
        }

        $discount = 200;

        self::getContainer()->get('security.token_storage')->setToken(
            new UsernamePasswordToken(
                $Customer, 'customer', $Customer->getRoles()
            )
        );

        $this->couponService->saveCouponOrder($Order, $Coupon, $Coupon->getCouponCd(), $Customer, $discount);

        /** @var CouponOrder $CouponOrder */
        $CouponOrder = $this->couponOrderRepository->findOneBy(['coupon_cd' => $Coupon->getCouponCd()]);

        $this->actual = $discount;
        $this->expected = $CouponOrder->getDiscount();

        // decimal カラムは Doctrine が文字列で返すため数値等価で比較する
        self::assertEquals($this->expected, $this->actual);
    }

    /**
     * testRecalcOrder.
     */
    public function testRecalcOrderDiscountPrice(): void
    {
        /** @var Coupon $Coupon */
        $Coupon = $this->getCoupon(Coupon::PRODUCT, Coupon::DISCOUNT_PRICE);

        $discountPrice = 100;
        $Coupon->setDiscountPrice($discountPrice);

        $Customer = $this->createCustomer();
        $Order = $this->createOrder($Customer);

        $details = $Coupon->getCouponDetails();
        /** @var CouponDetail $CouponDetail */
        $CouponDetail = $details[0];
        $Product = $CouponDetail->getProduct();
        $ProductClasses = $Product->getProductClasses();
        $ProductClass = $ProductClasses[0];

        $orderItem = new OrderItem();
        // デフォルト課税規則
        $TaxRule = $this->taxRuleRepository->getByRule();
        $OrderItemTypeProduct = $this->orderItemTypeRepository->find(OrderItemType::PRODUCT);
        $orderItem->setProduct($Product)
            ->setProductClass($ProductClass)
            ->setProductName($Product->getName())
            ->setProductCode($ProductClass->getCode())
            ->setOrderItemType($OrderItemTypeProduct)
            ->setPrice($ProductClass->getPrice02())
            ->setQuantity('1')
            ->setTaxRuleId($TaxRule->getId())
            ->setTaxRate($TaxRule->getTaxRate());
        $this->entityManager->persist($orderItem);
        $orderItem->setOrder($Order);
        $Order->addOrderItem($orderItem);
        $this->entityManager->flush();

        $products = $this->couponService->existsCouponProduct($Coupon, $Order);
        $discount = $this->couponService->recalcOrder($Coupon, $products);

        $this->actual = $discount;
        $this->expected = $discountPrice;
        // decimal カラムは Doctrine が文字列で返すため数値等価で比較する
        self::assertEquals($this->expected, $this->actual);
    }

    /**
     * testRecalcOrder.
     */
    public function testRecalcOrderDiscountRate(): void
    {
        /** @var Coupon $Coupon */
        $Coupon = $this->getCoupon(Coupon::PRODUCT, Coupon::DISCOUNT_RATE);

        $discountRate = 10;
        $Coupon->setDiscountRate($discountRate);

        $Customer = $this->createCustomer();
        $Order = $this->createOrder($Customer);

        $details = $Coupon->getCouponDetails();
        /** @var CouponDetail $CouponDetail */
        $CouponDetail = $details[0];
        $Product = $CouponDetail->getProduct();
        $ProductClasses = $Product->getProductClasses();
        $ProductClass = $ProductClasses[0];

        // remove old item
        foreach ($Order->getOrderItems() as $orderItem) {
            $Order->removeOrderItem($orderItem);
            $this->entityManager->remove($orderItem);
        }

        $orderItem = new OrderItem();
        // デフォルト課税規則
        $TaxRule = $this->taxRuleRepository->getByRule();
        $OrderItemTypeProduct = $this->orderItemTypeRepository->find(OrderItemType::PRODUCT);
        $orderItem->setProduct($Product)
            ->setProductClass($ProductClass)
            ->setProductName($Product->getName())
            ->setProductCode($ProductClass->getCode())
            ->setOrderItemType($OrderItemTypeProduct)
            ->setPrice($ProductClass->getPrice02())
            ->setQuantity('1')
            ->setTaxRuleId($TaxRule->getId())
            ->setTaxRate($TaxRule->getTaxRate());
        $this->entityManager->persist($orderItem);
        $orderItem->setOrder($Order);
        $Order->addOrderItem($orderItem);
        $this->entityManager->flush();

        $products = $this->couponService->existsCouponProduct($Coupon, $Order);
        $discount = $this->couponService->recalcOrder($Coupon, $products);

        // expected price
        $total = 0;
        // include tax
        foreach ($products as $key => $value) {
            $total += ($value['price'] + $value['price'] * $TaxRule->getTaxRate() / 100) * $value['quantity'];
        }

        $this->actual = $discount;
        $this->expected = (int) round(round($total) * $discountRate / 100);
        // decimal カラムは Doctrine が文字列で返すため数値等価で比較する
        self::assertEquals($this->expected, $this->actual);
    }

    /**
     * recalcOrder の第2引数から税率が取得できない場合は TaxRule から取得する
     *
     * @see https://github.com/EC-CUBE/coupon-plugin/pull/106/commits/d47f60745b283023cd7a990c609e6399701ddce1
     */
    public function testRecalcOrderWithTaxRateIsEmpty(): void
    {
        /** @var Coupon $Coupon */
        $Coupon = $this->getCoupon(Coupon::PRODUCT, Coupon::DISCOUNT_RATE);

        $discountRate = 10;
        $Coupon->setDiscountRate($discountRate);

        $Customer = $this->createCustomer();
        $Order = $this->createOrder($Customer);

        $details = $Coupon->getCouponDetails();
        /** @var CouponDetail $CouponDetail */
        $CouponDetail = $details[0];
        $Product = $CouponDetail->getProduct();
        $ProductClasses = $Product->getProductClasses();
        $ProductClass = $ProductClasses[0];

        // remove old item
        foreach ($Order->getOrderItems() as $orderItem) {
            $Order->removeOrderItem($orderItem);
            $this->entityManager->remove($orderItem);
        }

        $orderItem = new OrderItem();
        $OrderItemTypeProduct = $this->orderItemTypeRepository->find(OrderItemType::PRODUCT);
        $orderItem->setProduct($Product)
            ->setProductClass($ProductClass)
            ->setProductName($Product->getName())
            ->setProductCode($ProductClass->getCode())
            ->setOrderItemType($OrderItemTypeProduct)
            ->setPrice($ProductClass->getPrice02())
            ->setQuantity('1')
            // OrderItem に税率は設定しない
            ->setTaxRuleId(null)
            ->setTaxRate('0');
        $this->entityManager->persist($orderItem);
        $orderItem->setOrder($Order);
        $Order->addOrderItem($orderItem);
        $this->entityManager->flush();

        $products = $this->couponService->existsCouponProduct($Coupon, $Order);
        $discount = $this->couponService->recalcOrder($Coupon, $products);

        // デフォルト課税規則が適用されているはず
        $TaxRule = $this->taxRuleRepository->getByRule();
        // expected price
        $total = 0;
        // include tax
        foreach ($products as $key => $value) {
            $total += ($value['price'] + $value['price'] * $TaxRule->getTaxRate() / 100) * $value['quantity'];
        }

        $this->actual = $discount;
        $this->expected = (int) round(round($total) * $discountRate / 100);
        // decimal カラムは Doctrine が文字列で返すため数値等価で比較する
        self::assertEquals($this->expected, $this->actual);
    }

    /**
     * RoundingType 未設定の OrderItem でも isLowerLimitCoupon() が計算できること.
     *
     * 複数配送の確定時 (ShippingMultipleController) は RoundingType を設定せずに OrderItem が
     * 作り直されるため rounding_type_id が null で渡ってくる。本体 4.4 の
     * TaxRuleService::calcTax() は第3引数が非 nullable な int なので, フォールバックが無いと
     * TypeError で購入フローが 500 になる。
     */
    public function testIsLowerLimitCouponWithoutRoundingType(): void
    {
        /** @var Coupon $Coupon */
        $Coupon = $this->getCoupon(Coupon::PRODUCT);

        $Customer = $this->createCustomer();
        $Order = $this->createOrder($Customer);

        $details = $Coupon->getCouponDetails();
        /** @var CouponDetail $CouponDetail */
        $CouponDetail = $details[0];
        $Product = $CouponDetail->getProduct();
        $ProductClasses = $Product->getProductClasses();
        $ProductClass = $ProductClasses[0];

        // remove old item
        foreach ($Order->getOrderItems() as $orderItem) {
            $Order->removeOrderItem($orderItem);
            $this->entityManager->remove($orderItem);
        }

        // デフォルト課税規則
        $TaxRule = $this->taxRuleRepository->getByRule();
        $orderItem = new OrderItem();
        $OrderItemTypeProduct = $this->orderItemTypeRepository->find(OrderItemType::PRODUCT);
        $orderItem->setProduct($Product)
            ->setProductClass($ProductClass)
            ->setProductName($Product->getName())
            ->setProductCode($ProductClass->getCode())
            ->setOrderItemType($OrderItemTypeProduct)
            ->setPrice($ProductClass->getPrice02())
            ->setQuantity('1')
            ->setTaxRate($TaxRule->getTaxRate());
        // 丸め規則は設定しない (複数配送確定時と同じ状態)
        $this->entityManager->persist($orderItem);
        $orderItem->setOrder($Order);
        $Order->addOrderItem($orderItem);
        $this->entityManager->flush();

        $products = $this->couponService->existsCouponProduct($Coupon, $Order);
        self::assertCount(1, $products);
        // rounding_type_id が null のまま渡ってくることを確認する
        self::assertNull(current($products)['rounding_type_id']);

        // デフォルト課税規則で計算した税込金額
        $price = (string) $ProductClass->getPrice02();
        $tax = $this->taxRuleService->calcTax(
            $price,
            (string) $TaxRule->getTaxRate(),
            $TaxRule->getRoundingType()->getId(),
            (string) $TaxRule->getTaxAdjust()
        );
        $subTotal = (int) ((float) $price + (float) $tax);

        self::assertTrue($this->couponService->isLowerLimitCoupon($products, $subTotal));
        self::assertFalse($this->couponService->isLowerLimitCoupon($products, $subTotal + 1));
    }

    /**
     * @dataProvider roundingTypeProvider
     *
     * https://github.com/EC-CUBE/coupon-plugin/issues/120
     *
     * @param mixed $taxRate
     * @param mixed $discountRate
     * @param mixed $roundingTypeId
     * @param mixed $price
     * @param mixed $discount
     */
    public function testReCalcOrderWithRoundingType(
        mixed $taxRate,
        mixed $discountRate,
        mixed $roundingTypeId,
        mixed $price,
        mixed $discount,
    ): void {
        $TaxRule = $this->taxRuleRepository->find(TaxRule::DEFAULT_TAX_RULE_ID);
        $TaxRule->setTaxRate($taxRate);
        $TaxRule->setRoundingType($this->entityManager->find(RoundingType::class, $roundingTypeId));
        $this->entityManager->flush();

        /** @var Coupon $Coupon */
        $Coupon = $this->getCoupon(null, Coupon::DISCOUNT_RATE);
        $Coupon->setDiscountRate($discountRate);

        $couponProducts[] = [
            'price' => $price,
            'quantity' => 1,
            'tax_rate' => $taxRate,
            'rounding_type_id' => $roundingTypeId,
        ];

        $result = $this->couponService->recalcOrder($Coupon, $couponProducts);
        self::assertEquals($discount, $result);
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public static function roundingTypeProvider(): array
    {
        return [
            [8, 10, RoundingType::ROUND, 4222, 456],
            [8, 10, RoundingType::FLOOR, 4222, 455],
            [8, 10, RoundingType::CEIL, 4222, 456],
        ];
    }

    /**
     * @dataProvider lowerLimitProvider
     *
     * https://github.com/EC-CUBE/coupon-plugin/issues/120
     *
     * @param mixed $taxRate
     * @param mixed $roundingTypeId
     * @param mixed $price
     * @param mixed $lowerLimit
     * @param mixed $expected
     */
    public function testIsLowerLimitCoupon(mixed $taxRate, mixed $roundingTypeId, mixed $price, mixed $lowerLimit, mixed $expected): void
    {
        $TaxRule = $this->taxRuleRepository->find(TaxRule::DEFAULT_TAX_RULE_ID);
        $TaxRule->setTaxRate($taxRate);
        $this->entityManager->flush();

        /** @var Coupon $Coupon */
        $Coupon = $this->getCoupon(null, Coupon::DISCOUNT_RATE);
        $Coupon->setCouponLowerLimit($lowerLimit);
        $couponProducts[] = [
            'price' => $price,
            'quantity' => 1,
            'tax_rate' => $taxRate,
            'rounding_type_id' => $roundingTypeId,
        ];

        $result = $this->couponService->isLowerLimitCoupon($couponProducts, $lowerLimit);
        self::assertEquals($expected, $result);
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public static function lowerLimitProvider(): array
    {
        return [
            // 税抜4222円, 税込4560円
            [8, RoundingType::ROUND, 4222, 4559, true],
            [8, RoundingType::ROUND, 4222, 4560, true],
            [8, RoundingType::ROUND, 4222, 4561, false],

            // 税抜4222円, 税込4559円
            [8, RoundingType::FLOOR, 4222, 4558, true],
            [8, RoundingType::FLOOR, 4222, 4559, true],
            [8, RoundingType::FLOOR, 4222, 4560, false],
            [8, RoundingType::FLOOR, 4222, 4561, false],

            // 税抜4222円, 税込4560円
            [8, RoundingType::CEIL, 4222, 4559, true],
            [8, RoundingType::CEIL, 4222, 4560, true],
            [8, RoundingType::CEIL, 4222, 4561, false],
        ];
    }

    /**
     * @see https://github.com/EC-CUBE/coupon-plugin/issues/121
     */
    public function testContainsCategory(): void
    {
        /** @var Coupon $Coupon */
        $Coupon = $this->getTestData(Coupon::CATEGORY, Coupon::DISCOUNT_PRICE);
        $Coupon->setVisible(true);

        $Product = $this->createProduct(null, 0);
        $ProductClass = $Product->getProductClasses()->first();

        $ProductCategories = $Product->getProductCategories();
        $Customer = $this->createCustomer();
        $Order = $this->createOrderWithProductClasses($Customer, [$ProductClass]);

        $quantity = $Order->getProductOrderItems()[0]->getQuantity();

        foreach ($ProductCategories as $ProductCategory) {
            $CouponDetail = new CouponDetail();
            $CouponDetail->setCoupon($Coupon);
            $CouponDetail->setCouponType($Coupon->getCouponType());
            $CouponDetail->setUpdateDate($Coupon->getUpdateDate());
            $CouponDetail->setCreateDate($Coupon->getCreateDate());
            $CouponDetail->setVisible(true);
            $CouponDetail->setCategory($ProductCategory->getCategory());
            $Coupon->addCouponDetail($CouponDetail);
            $this->entityManager->persist($CouponDetail);
            $this->entityManager->flush();
        }

        $this->entityManager->flush();

        $method = new \ReflectionMethod(CouponService::class, 'containsCategory');
        $method->setAccessible(true);
        $result = $method->invoke($this->couponService, $Coupon, $Order);

        self::assertEquals($quantity, $result[$ProductClass->getId()]['quantity']);
    }

    public function testSetOrderCompleteMailMessage(): void
    {
        $Order = new Order();
        $Order->setCompleteMailMessage('追加完了メッセージ'.PHP_EOL);
        $couponCd = 'aaaaaa';
        $couponName = 'coupon aaa';

        $this->couponService->setOrderCompleteMailMessage($Order, $couponCd, $couponName);
        $this->assertMatchesRegularExpression('/クーポン情報/u', $Order->getCompleteMailMessage());
        $this->assertMatchesRegularExpression('/クーポンコード: '.$couponCd.' '.$couponName.'/u', $Order->getCompleteMailMessage());

        $this->couponService->setOrderCompleteMailMessage($Order, null, null);
        $this->assertDoesNotMatchRegularExpression('/クーポン情報/u', $Order->getCompleteMailMessage());
        $this->assertDoesNotMatchRegularExpression('/クーポンコード: '.$couponCd.' '.$couponName.'/u', $Order->getCompleteMailMessage());
    }

    public function testRemoveCouponOrder(): void
    {
        /** @var Coupon $Coupon */
        $Coupon = $this->getCoupon(Coupon::PRODUCT, Coupon::DISCOUNT_RATE);
        $Customer = $this->createCustomer();
        $Order = $this->createOrder($Customer);
        $OtherOrder = $this->createOrder($Customer);
        $discount = 100;

        self::getContainer()->get('security.token_storage')->setToken(
            new UsernamePasswordToken(
                $Customer, 'customer', $Customer->getRoles()
            )
        );

        $this->couponService->saveCouponOrder($Order, $Coupon, $Coupon->getCouponCd(), $Customer, $discount);
        $this->couponService->saveCouponOrder($OtherOrder, $Coupon, $Coupon->getCouponCd(), $Customer, $discount);

        $CouponProcessor = new CouponProcessor(
            $this->entityManager,
            $this->couponService,
            $this->couponRepository,
            $this->couponOrderRepository,
            $this->taxRuleService,
            $this->taxRuleRepository
        );
        $refMethod = new \ReflectionMethod(CouponProcessor::class, 'addCouponDiscountItem');
        $refMethod->setAccessible(true);
        foreach ([$Order, $OtherOrder] as $O) {
            $CouponOrder = $this->couponOrderRepository->findOneBy(['coupon_cd' => $Coupon->getCouponCd(), 'order_id' => $O->getId()]);
            $refMethod->invoke($CouponProcessor, $O, $CouponOrder);
        }
        $this->entityManager->flush();

        $this->couponService->removeCouponOrder($Order);

        $CouponOrderItems = $Order->getItems()->filter(
            function (ItemInterface $OrderItem) {
                return $OrderItem instanceof OrderItem && $OrderItem->getProcessorName() === CouponProcessor::class;
            }
        );
        $this->assertTrue($CouponOrderItems->isEmpty(), 'クーポン明細が削除されている');

        // https://github.com/EC-CUBE/coupon-plugin/pull/110 のテストケース
        $CouponOrderItems = $OtherOrder->getItems()->filter(
            function (ItemInterface $OrderItem) {
                return $OrderItem instanceof OrderItem && $OrderItem->getProcessorName() === CouponProcessor::class;
            }
        );
        $this->assertFalse($CouponOrderItems->isEmpty());
    }

    /**
     * getCoupon.
     *
     * @param int|null $couponType
     * @param mixed $discountType
     *
     * @return Coupon
     */
    private function getCoupon(?int $couponType = Coupon::PRODUCT, mixed $discountType = Coupon::DISCOUNT_PRICE): Coupon
    {
        /** @var Coupon $Coupon */
        $Coupon = $this->getTestData($couponType, $discountType);

        $Product = $this->createProduct();

        $CouponDetail = new CouponDetail();
        $CouponDetail->setCoupon($Coupon);
        $CouponDetail->setCouponType($Coupon->getCouponType());
        $CouponDetail->setUpdateDate($Coupon->getUpdateDate());
        $CouponDetail->setCreateDate($Coupon->getCreateDate());
        $CouponDetail->setVisible(true);

        switch ($couponType) {
            case Coupon::PRODUCT:
                $CouponDetail->setProduct($Product);
                break;
            case Coupon::CATEGORY:
                $Categories = $Product->getProductCategories();
                /** @var ProductCategory $ProductCategory */
                $ProductCategory = $Categories[0];
                $CouponDetail->setCategory($ProductCategory->getCategory());
                break;
            default:
                break;
        }
        $Coupon->addCouponDetail($CouponDetail);

        return $Coupon;
    }

    /**
     * getTestData.
     *
     * @param int|null $couponType
     * @param mixed $discountType
     *
     * @return Coupon
     */
    private function getTestData(?int $couponType = Coupon::PRODUCT, mixed $discountType = Coupon::DISCOUNT_PRICE): Coupon
    {
        $Coupon = new Coupon();

        $date1 = new \DateTime();
        $date2 = new \DateTime();

        $Coupon->setCouponCd('aaaaaaaa');
        $Coupon->setCouponType($couponType);
        $Coupon->setCouponName('クーポン');
        $Coupon->setDiscountType($discountType);
        $Coupon->setCouponRelease(100);
        $Coupon->setCouponUseTime(100);
        $Coupon->setDiscountPrice(100);
        $Coupon->setDiscountRate(10);
        $Coupon->setCouponLowerLimit(100);
        $Coupon->setCouponMember(0);
        $Coupon->setEnableFlag(1);
        $Coupon->setVisible(false);
        $d1 = $date1->setDate(2016, 1, 1);
        $Coupon->setAvailableFromDate($d1);
        $d2 = $date2->setDate(2040, 12, 31);
        $Coupon->setAvailableToDate($d2);

        // クーポン情報を登録する
        $this->entityManager->persist($Coupon);
        $this->entityManager->flush();

        return $Coupon;
    }
}
