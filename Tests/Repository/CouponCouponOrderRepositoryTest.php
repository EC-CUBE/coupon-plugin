<?php
/*
 * This file is part of the Coupon plugin
 *
 * Copyright (C) 2016 LOCKON CO.,LTD. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\Coupon\Tests\Repository;

use Eccube\Common\Constant;
use Eccube\Entity\OrderDetail;
use Eccube\Tests\EccubeTestCase;
use Eccube\Util\Str;
use Plugin\Coupon\Entity\Coupon;
use Plugin\Coupon\Entity\CouponDetail;
use Plugin\Coupon\Entity\CouponOrder;

/**
 * Class CouponCouponOrderRepositoryTest.
 */
class CouponCouponOrderRepositoryTest extends EccubeTestCase
{
    /**
     * @var
     */
    private $Customer;

    /**
     * testSave.
     */
    public function testSave()
    {
        $Coupon = $this->getCoupon();

        $discount = 200;
        $preOrderId = sha1(Str::random(32));

        $CouponOrder = $this->getCouponOrder($Coupon, $discount, $preOrderId);

        $this->app['coupon.repository.coupon_order']->save($CouponOrder);

        $CouponOrder1 = $this->app['coupon.repository.coupon_order']->findOneBy(array('pre_order_id' => $preOrderId));

        $this->actual = $CouponOrder1->getDiscount();

        $this->expected = $discount;

        $this->verify();
    }

    /**
     * testFindUseCouponByOrderId.
     */
    public function testFindUseCouponByOrderId()
    {
        $Coupon = $this->getCoupon();

        $discount = 200;
        $preOrderId = sha1(Str::random(32));

        $CouponOrder = $this->getCouponOrder($Coupon, $discount, $preOrderId);

        $CouponOrder->setOrderDate(new \DateTime());

        $this->app['coupon.repository.coupon_order']->save($CouponOrder);

        $Order = $this->app['eccube.repository.order']->find($CouponOrder->getOrderId());

        $CouponOrder1 = $this->app['coupon.repository.coupon_order']->findUseCouponByOrderId($Order->getId());

        $this->actual = $CouponOrder1->getDiscount();

        $this->expected = $discount;

        $this->verify();
    }

    /**
     * testFindUseCouponNonMember.
     */
    public function testFindUseCouponNonMember()
    {
        $Coupon = $this->getCoupon();

        $discount = 200;
        $preOrderId = sha1(Str::random(32));

        $CouponOrder = $this->getCouponOrder($Coupon, $discount, $preOrderId);

        $CouponOrder->setEmail($this->Customer->getEmail());
        $CouponOrder->setOrderDate(new \DateTime());

        $this->app['coupon.repository.coupon_order']->save($CouponOrder);

        $CouponOrder1 = $this->app['coupon.repository.coupon_order']->findUseCoupon($Coupon->getCouponCd(), $this->Customer->getEmail());

        $this->actual = $CouponOrder1[0]->getDiscount();

        $this->expected = $discount;

        $this->verify();
    }

    /**
     * testCountCouponByCd.
     */
    public function testCountCouponByCd()
    {
        $Coupon = $this->getCoupon();

        $discount = 200;
        $preOrderId = sha1(Str::random(32));

        $CouponOrder = $this->getCouponOrder($Coupon, $discount, $preOrderId);

        $CouponOrder->setOrderDate(new \DateTime());

        $this->app['coupon.repository.coupon_order']->save($CouponOrder);

        $count = $this->app['coupon.repository.coupon_order']->countCouponByCd($Coupon->getCouponCd());

        $this->actual = $count['1'];

        $this->expected = 1;

        $this->verify();
    }

    /**
     * getCouponOrder.
     *
     * @param Coupon $Coupon
     * @param $discount
     * @param $preOrderId
     *
     * @return CouponOrder
     */
    private function getCouponOrder(Coupon $Coupon, $discount, $preOrderId)
    {
        $this->Customer = $this->createCustomer('aaa@example.com');

        $Order = $this->createOrder($this->Customer);

        $Order->setPreOrderId($preOrderId);

        $details = $Coupon->getCouponDetails();

        /** @var CouponDetail $CouponDetail */
        $CouponDetail = $details[0];

        $Product = $CouponDetail->getProduct();

        $ProductClasses = $Product->getProductClasses();
        $ProductClass = $ProductClasses[0];

        $orderDetails = $Order->getOrderDetails();
        foreach ($orderDetails as $OrderDetail) {
            $Order->removeOrderDetail($OrderDetail);
        }

        $OrderDetail = new OrderDetail();
        $TaxRule = $this->app['eccube.repository.tax_rule']->getByRule();
        $OrderDetail->setProduct($Product)
            ->setProductClass($ProductClass)
            ->setProductName($Product->getName())
            ->setProductCode($ProductClass->getCode())
            ->setPrice($ProductClass->getPrice02())
            ->setQuantity(1)
            ->setTaxRule($TaxRule->getCalcRule()->getId())
            ->setTaxRate($TaxRule->getTaxRate());
        $this->app['orm.em']->persist($OrderDetail);
        $OrderDetail->setOrder($Order);
        $Order->addOrderDetail($OrderDetail);

        $CouponOrder = new CouponOrder();
        $CouponOrder->setDelFlg(Constant::DISABLED);
        $CouponOrder->setDiscount($discount);
        $CouponOrder->setUserId($this->Customer->getId());
        $CouponOrder->setCouponId($Coupon->getId());
        $CouponOrder->setOrderChangeStatus(Constant::DISABLED);
        $CouponOrder->setOrderId($Order->getId());
        $CouponOrder->setPreOrderId($Order->getPreOrderId());
        $CouponOrder->setCouponCd($Coupon->getCouponCd());

        return $CouponOrder;
    }

    /**
     * getCoupon.
     *
     * @param int $couponType
     *
     * @return Coupon
     */
    private function getCoupon($couponType = 1)
    {
        $this->getTestData($couponType);

        /** @var Coupon $Coupon */
        $Coupon = $this->app['coupon.repository.coupon']->findOneBy(array('coupon_cd' => 'aaaaaaaa'));

        $Product = $this->createProduct();
        $CouponDetail = new CouponDetail();

        $CouponDetail->setCoupon($Coupon);
        $CouponDetail->setCouponType($Coupon->getCouponType());
        $CouponDetail->setUpdateDate($Coupon->getUpdateDate());
        $CouponDetail->setCreateDate($Coupon->getCreateDate());
        $CouponDetail->setDelFlg(Constant::ENABLED);

        $Categories = $Product->getProductCategories();

        /** @var \Eccube\Entity\ProductCategory $Category */
        $ProductCategory = $Categories[0];

        $CouponDetail->setCategory($ProductCategory->getCategory());

        $CouponDetail->setProduct($Product);

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
    private function getTestData($couponType = 1)
    {
        $Coupon = new Coupon();

        $date1 = new \DateTime();
        $date2 = new \DateTime();

        $Coupon->setCouponCd('aaaaaaaa');
        $Coupon->setCouponType($couponType);
        $Coupon->setCouponName('クーポン');
        $Coupon->setDiscountType(1);
        $Coupon->setCouponRelease(100);
        $Coupon->setCouponUseTime(100);
        $Coupon->setDiscountPrice(100);
        $Coupon->setDiscountRate(10);
        $Coupon->setCouponLowerLimit(100);
        $Coupon->setCouponMember(0);
        $Coupon->setEnableFlag(1);
        $Coupon->setDelFlg(0);
        $d1 = $date1->setDate(2016, 1, 1);
        $Coupon->setAvailableFromDate($d1);
        $d2 = $date2->setDate(2040, 12, 31);
        $Coupon->setAvailableToDate($d2);

        $em = $this->app['orm.em'];
        // クーポン情報を登録する
        $em->persist($Coupon);
        $em->flush($Coupon);

        return $Coupon;
    }
}
