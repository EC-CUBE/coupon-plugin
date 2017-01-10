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
use Plugin\Coupon\Entity\Coupon;
use Plugin\Coupon\Entity\CouponDetail;

/**
 * Class CouponCouponRepositoryTest.
 */
class CouponCouponRepositoryTest extends EccubeTestCase
{
    /**
     * testFindActiveCoupon.
     */
    public function testFindActiveCoupon()
    {
        $Coupon = $this->getCoupon();
        $couponCd = 'aaaaaaaa';
        $Coupon1 = $this->app['coupon.repository.coupon']->findActiveCoupon($couponCd);
        $this->actual = $Coupon->getCouponCd();
        $this->expected = $couponCd;
        $this->verify();
    }

    /**
     * testFindActiveCouponAll.
     */
    public function testFindActiveCouponAll()
    {
        $this->getCoupon();
        $coupons = $this->app['coupon.repository.coupon']->findActiveCouponAll();
        $this->actual = count($coupons);
        $this->assertGreaterThan(0, $this->actual);
    }

    /**
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
        $CouponDetail->setDelFlg(Constant::DISABLED);
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
