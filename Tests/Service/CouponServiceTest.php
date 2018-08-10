<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\Coupon\Tests\Service;

use Eccube\Entity\Master\OrderItemType;
use Eccube\Entity\Order;
use Eccube\Entity\OrderItem;
use Eccube\Entity\ProductCategory;
use Eccube\Repository\Master\OrderItemTypeRepository;
use Eccube\Repository\TaxRuleRepository;
use Eccube\Tests\EccubeTestCase;
use Plugin\Coupon\Entity\Coupon;
use Plugin\Coupon\Entity\CouponDetail;
use Plugin\Coupon\Repository\CouponOrderRepository;
use Plugin\Coupon\Repository\CouponRepository;
use Plugin\Coupon\Service\CouponService;
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
     * @var CouponService
     */
    private $couponService;

    /**
     * @var OrderItemTypeRepository
     */
    private $orderItemTypeRepository;

    public function setUp()
    {
        parent::setUp();
        $this->couponOrderRepository = $this->container->get(CouponOrderRepository::class);
        $this->couponRepository = $this->container->get(CouponRepository::class);
        $this->taxRuleRepository = $this->container->get(TaxRuleRepository::class);
        $this->couponService = $this->container->get(CouponService::class);
        $this->orderItemTypeRepository = $this->container->get(OrderItemTypeRepository::class);
    }

    /**
     * testGenerateCouponCd.
     */
    public function testGenerateCouponCd()
    {
        $couponCd = $this->couponService->generateCouponCd(20);

        $this->actual = strlen($couponCd);
        $this->expected = 20;

        $this->verify();
    }

    /**
     * testExistsCouponProduct.
     */
    public function testExistsCouponProduct()
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
            ->setQuantity(1)
            ->setTaxRule($TaxRule->getRoundingType()->getId())
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
    public function testExistsCouponProductNot()
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
    public function testExistsCouponProductTypeCategory()
    {
        /** @var Coupon $Coupon */
        $Coupon = $this->getCoupon(Coupon::CATEGORY);

        $Customer = $this->createCustomer();
        $Order = $this->createOrder($Customer);

        $details = $Coupon->getCouponDetails();
        /** @var CouponDetail $CouponDetail */
        $CouponDetail = $details[0];
        $Category = $CouponDetail->getCategory();
        /** @var ProductCategory $ProductCategory */
        $ProductCategory = $Category->getProductCategories()->first();
        $Product = $ProductCategory->getProduct();
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
            ->setQuantity(1)
            ->setTaxRule($TaxRule->getRoundingType()->getId())
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
     * testSaveCouponOrder.
     */
    public function testSaveCouponOrder()
    {
        /** @var Coupon $Coupon */
        $Coupon = $this->getCoupon();

        $Customer = $this->createCustomer();

        $Order = $this->createOrder($Customer);
        if (!$Order->getPreOrderId()) {
            $Order->setPreOrderId('dummy');
        }

        $discount = 200;

        $this->container->get('security.token_storage')->setToken(
            new UsernamePasswordToken(
                $Customer, null, 'customer', $Customer->getRoles()
            )
        );

        $this->couponService->saveCouponOrder($Order, $Coupon, $Coupon->getCouponCd(), $Customer, $discount);

        /** @var \Plugin\Coupon\Entity\CouponOrder $CouponOrder */
        $CouponOrder = $this->couponOrderRepository->findOneBy(['coupon_cd' => $Coupon->getCouponCd()]);

        $this->actual = $discount;
        $this->expected = $CouponOrder->getDiscount();

        $this->verify();
    }

    /**
     * testRecalcOrder.
     */
    public function testRecalcOrderDiscountPrice()
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
            ->setQuantity(1)
            ->setTaxRule($TaxRule->getRoundingType()->getId())
            ->setTaxRate($TaxRule->getTaxRate());
        $this->entityManager->persist($orderItem);
        $orderItem->setOrder($Order);
        $Order->addOrderItem($orderItem);
        $this->entityManager->flush();

        $products = $this->couponService->existsCouponProduct($Coupon, $Order);
        $discount = $this->couponService->recalcOrder($Coupon, $products);

        $this->actual = $discount;
        $this->expected = $discountPrice;
        $this->verify();
    }

    /**
     * testRecalcOrder.
     */
    public function testRecalcOrderDiscountRate()
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
            ->setQuantity(1)
            ->setTaxRule($TaxRule->getRoundingType()->getId())
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
        $this->expected = (int) round($total * $discountRate / 100);
        $this->verify();
    }

    public function testSetOrderCompleteMailMessage()
    {
        $Order = new Order();
        $Order->setCompleteMailMessage('追加完了メッセージ'.PHP_EOL);
        $couponCd = 'aaaaaa';
        $couponName = 'coupon aaa';

        $this->couponService->setOrderCompleteMailMessage($Order, $couponCd, $couponName);
        $this->assertRegExp('/クーポン情報/u', $Order->getCompleteMailMessage());
        $this->assertRegExp('/クーポンコード: '.$couponCd.' '.$couponName.'/u', $Order->getCompleteMailMessage());

        $this->couponService->setOrderCompleteMailMessage($Order, null, null);
        $this->assertNotRegExp('/クーポン情報/u', $Order->getCompleteMailMessage());
        $this->assertNotRegExp('/クーポンコード: '.$couponCd.' '.$couponName.'/u', $Order->getCompleteMailMessage());
    }

    /**
     * getCoupon.
     *
     * @param int $couponType
     *
     * @return Coupon
     */
    private function getCoupon($couponType = Coupon::PRODUCT, $discountType = Coupon::DISCOUNT_PRICE)
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
                /** @var \Eccube\Entity\ProductCategory $Category */
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
     * @param int $couponType
     *
     * @return Coupon
     */
    private function getTestData($couponType = Coupon::PRODUCT, $discountType = Coupon::DISCOUNT_PRICE)
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
        $this->entityManager->flush($Coupon);

        return $Coupon;
    }
}
