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
use Eccube\Tests\EccubeTestCase;
use Plugin\Coupon\Entity\CouponCoupon;
use Plugin\Coupon\Entity\CouponCouponDetail;

/**
 * Class CouponCouponRepositoryTest.
 */
class CouponCouponRepositoryTest extends EccubeTestCase
{
    public function testFindActiveCoupon()
    {
        $Coupon = $this->getCoupon();

        $couponCd = 'aaaaaaaa';

        $Coupon1 = $this->app['eccube.plugin.coupon.repository.coupon']->findActiveCoupon($couponCd);

        $this->actual = $Coupon1->getCouponCd();

        $this->expected = $couponCd;

        $this->verify();
    }

    public function testFindActiveCouponAll()
    {
        $this->getCoupon();

        $coupons = $this->app['eccube.plugin.coupon.repository.coupon']->findActiveCouponAll();

        $this->actual = count($coupons);

        $this->assertGreaterThan(0, $this->actual);
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
        $CouponDetail->setDelFlg(Constant::DISABLED);

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
