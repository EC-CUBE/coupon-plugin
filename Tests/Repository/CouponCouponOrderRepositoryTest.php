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
use Plugin\Coupon\Entity\CouponCoupon;
use Plugin\Coupon\Entity\CouponCouponDetail;
use Plugin\Coupon\Entity\CouponCouponOrder;

/**
 * Class CouponCouponOrderRepositoryTest
 *
 * @package Plugin\Coupon\Tests\Repository
 */
class CouponCouponOrderRepositoryTest extends EccubeTestCase
{

    private $Customer;

    public function testSave()
    {

        $Coupon = $this->getCoupon();

        $discount = 200;
        $preOrderId = sha1(Str::random(32));

        $CouponOrder = $this->getCouponOrder($Coupon, $discount, $preOrderId);

        $this->app['eccube.plugin.coupon.repository.coupon_order']->save($CouponOrder);

        $CouponOrder1 = $this->app['eccube.plugin.coupon.repository.coupon_order']->findOneBy(array('pre_order_id' => $preOrderId));

        $this->actual = $CouponOrder1->getDiscount();

        $this->expected = $discount;

        $this->verify();


    }


    public function testFindUseCouponByOrderId()
    {

        $Coupon = $this->getCoupon();

        $discount = 200;
        $preOrderId = sha1(Str::random(32));

        $CouponOrder = $this->getCouponOrder($Coupon, $discount, $preOrderId);

        $CouponOrder->setOrderDate(new \DateTime());

        $this->app['eccube.plugin.coupon.repository.coupon_order']->save($CouponOrder);

        $Order = $this->app['eccube.repository.order']->find($CouponOrder->getOrderId());

        $CouponOrder1 = $this->app['eccube.plugin.coupon.repository.coupon_order']->findUseCouponByOrderId($Order->getId());

        $this->actual = $CouponOrder1->getDiscount();

        $this->expected = $discount;

        $this->verify();


    }


    public function testFindUseCouponNonMember()
    {

        $Coupon = $this->getCoupon();

        $discount = 200;
        $preOrderId = sha1(Str::random(32));

        $CouponOrder = $this->getCouponOrder($Coupon, $discount, $preOrderId);

        $CouponOrder->setEmail($this->Customer->getEmail());
        $CouponOrder->setOrderDate(new \DateTime());

        $this->app['eccube.plugin.coupon.repository.coupon_order']->save($CouponOrder);

        $CouponOrder1 = $this->app['eccube.plugin.coupon.repository.coupon_order']->findUseCoupon($Coupon->getCouponCd(), $this->Customer->getEmail());

        $this->actual = $CouponOrder1[0]->getDiscount();

        $this->expected = $discount;

        $this->verify();


    }


    public function testCountCouponByCd()
    {

        $Coupon = $this->getCoupon();

        $discount = 200;
        $preOrderId = sha1(Str::random(32));

        $CouponOrder = $this->getCouponOrder($Coupon, $discount, $preOrderId);

        $CouponOrder->setOrderDate(new \DateTime());

        $this->app['eccube.plugin.coupon.repository.coupon_order']->save($CouponOrder);

        $count = $this->app['eccube.plugin.coupon.repository.coupon_order']->countCouponByCd($Coupon->getCouponCd());

        $this->actual = $count['1'];

        $this->expected = 1;

        $this->verify();

    }


    private function getCouponOrder(CouponCoupon $Coupon, $discount, $preOrderId)
    {

        $this->Customer = $this->createCustomer('aaa@example.com');

        $Order = $this->createOrder($this->Customer);

        $Order->setPreOrderId($preOrderId);

        $details = $Coupon->getCouponDetails();

        /** @var \Plugin\Coupon\Entity\CouponCouponDetail $CouponDetail */
        $CouponDetail = $details[0];

        $Product = $CouponDetail->getProduct();

        $ProductClasses = $Product->getProductClasses();
        $ProductClass = $ProductClasses[0];


        $orderDetails = $Order->getOrderDetails();
        foreach ($orderDetails as $OrderDetail) {
            $Order->removeOrderDetail($OrderDetail);
        }

        $OrderDetail = new OrderDetail();
        $TaxRule = $this->app['eccube.repository.tax_rule']->getByRule(); // デフォルト課税規則
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

        $CouponOrder = new CouponCouponOrder();
        $CouponOrder->setDelFlg(Constant::DISABLED);
        $CouponOrder->setDiscount($discount);
        $CouponOrder->setUserId($this->Customer->getId());
        $CouponOrder->setCouponId($Coupon->getId());
        $CouponOrder->setOrderId($Order->getId());
        $CouponOrder->setPreOrderId($Order->getPreOrderId());
        $CouponOrder->setCouponCd($Coupon->getCouponCd());


        return $CouponOrder;

    }




    private function getCoupon($couponType = 1)
    {
        $data = $this->getTestData($couponType);

        $this->app['eccube.plugin.coupon.service.coupon']->createCoupon($data);

        /** @var \Plugin\Coupon\Entity\CouponCoupon $Coupon */
        $Coupon = $this->app['eccube.plugin.coupon.repository.coupon']->findOneBy(array('coupon_cd' => 'aaaaaaaa'));

        $Product = $this->createProduct();
        $CouponDetail = new CouponCouponDetail();

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


    private function getTestData($couponType = 1)
    {

        $Coupon = new CouponCoupon();

        $date1 = new \DateTime();
        $date2 = new \DateTime();

        $Coupon->setCouponCd('aaaaaaaa');
        $Coupon->setCouponType($couponType);
        $Coupon->setCouponName('クーポン');
        $Coupon->setDiscountType(1);
        $Coupon->setCouponUseTime(1);
        $Coupon->setDiscountPrice(100);
        $Coupon->setDiscountRate(10);
        $Coupon->setEnableFlag(1);
        $d1 = $date1->setDate(2016, 1, 1);
        $Coupon->setAvailableFromDate($d1);
        $d2 = $date2->setDate(2016, 12, 31);
        $Coupon->setAvailableToDate($d2);

        return $Coupon;

    }


}