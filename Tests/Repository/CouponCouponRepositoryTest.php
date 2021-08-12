<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\Coupon4\Tests\Repository;

use Eccube\Tests\EccubeTestCase;
use Plugin\Coupon4\Entity\Coupon;
use Plugin\Coupon4\Entity\CouponDetail;
use Plugin\Coupon4\Repository\CouponRepository;

/**
 * Class CouponCouponRepositoryTest.
 */
class CouponCouponRepositoryTest extends EccubeTestCase
{
    /**
     * @var CouponRepository
     */
    private $couponRepository;

    /**
     * Set up
     */
    public function setUp()
    {
        parent::setUp();
        $this->couponRepository = $this->entityManager->getRepository(Coupon::class);
    }

    /**
     * testFindActiveCoupon.
     */
    public function testFindActiveCoupon()
    {
        $Coupon = $this->getCoupon();
        $couponCd = 'aaaaaaaa';
        $this->couponRepository->findActiveCoupon($couponCd);
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
        $coupons = $this->couponRepository->findActiveCouponAll();
        $this->actual = count($coupons);
        $this->assertGreaterThan(0, $this->actual);
    }

    /**
     * testEnableCoupon.
     */
    public function testEnableCoupon()
    {
        /** @var Coupon $Coupon */
        $Coupon = $this->getCoupon();

        $this->actual = $this->couponRepository->enableCoupon($Coupon);
        $this->expected = true;
        $this->verify();
    }

    /**
     * testDeleteCoupon.
     */
    public function testDeleteCoupon()
    {
        /** @var Coupon $Coupon */
        $Coupon = $this->getCoupon();

        $this->actual = $this->couponRepository->deleteCoupon($Coupon);
        $this->expected = true;
        $this->verify();
    }

    public function testCheckCouponUseTime()
    {
        $Coupon = $this->getCoupon();
        $Coupon->setCouponUseTime(0);

        $this->actual = $this->couponRepository->checkCouponUseTime($Coupon->getCouponCd());
        $this->expected = false;
        $this->verify();
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
        $Coupon = $this->couponRepository->findOneBy(['coupon_cd' => 'aaaaaaaa']);

        $Product = $this->createProduct();
        $CouponDetail = new CouponDetail();
        $CouponDetail->setCoupon($Coupon);
        $CouponDetail->setCouponType($Coupon->getCouponType());
        $CouponDetail->setUpdateDate($Coupon->getUpdateDate());
        $CouponDetail->setCreateDate($Coupon->getCreateDate());
        $CouponDetail->setVisible(true);
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
        $Coupon->setVisible(true);
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
