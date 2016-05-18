<?php

namespace Eccube\Tests\Service;

use Eccube\Tests\EccubeTestCase;
use Plugin\Coupon\Entity\CouponCouponDetail;

/**
 * Class CouponServiceTest
 */
class CouponServiceTest extends EccubeTestCase
{

    public function testCreateCoupon()
    {

        $data = $this->getTestData();

        $this->expected = true;

        $this->actual = $this->app['eccube.plugin.coupon.service.coupon']->createCoupon($data);

        $this->verify();

    }

    public function testUpdateCoupon()
    {
        $data = $this->getTestData();

        $this->app['eccube.plugin.coupon.service.coupon']->createCoupon($data);

        /** @var \Plugin\Coupon\Entity\CouponCoupon $Coupon */
        $Coupon = $this->app['eccube.plugin.coupon.repository.coupon']->findOneBy(array('coupon_cd' => 'aaaaaaaa'));

        $Coupon->setCouponName('クーポン2');

        $this->actual = $this->app['eccube.plugin.coupon.service.coupon']->updateCoupon($Coupon);

        $this->expected = true;

        $this->verify();
    }


    public function getTestData()
    {

        $CouponDetail = new CouponCouponDetail();

        $Product = $this->createProduct();

        $CouponDetail->setProduct($Product);

        $data = array(
            'coupon_cd' => 'aaaaaaaa',
            'coupon_type' => '1',
            'coupon_name' => 'クーポン',
            'discount_type' => '1',
            'coupon_use_time' => '1',
            'discount_price' => '1',
            'discount_rate' => '1',
            'enalbe_flag' => '1',
            'available_from_date' => new \DateTime(),
            'available_to_date' => new \DateTime(),
            'CouponDetails' => array($CouponDetail),
        );

        return $data;

//        $Coupon = new CouponCoupon();
//
//        $Coupon->setCouponCd('aaaaaaaa');
//        $Coupon->setCouponType(1);
//        $Coupon->setCouponName('クーポン');
//        $Coupon->setDiscountType(1);
//        $Coupon->setCouponUseTime(1);
//        $Coupon->setDiscountPrice(100);
//        $Coupon->setEnableFlag(1);
//        $Coupon->setAvailableFromDate(new \DateTime());
//        $Coupon->setAvailableToDate(new \DateTime());
//
//
//        return $Coupon;

    }

}