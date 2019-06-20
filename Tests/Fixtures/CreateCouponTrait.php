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

namespace Plugin\Coupon4\Tests\Fixtures;

use Plugin\Coupon4\Entity\Coupon;
use Plugin\Coupon4\Entity\CouponDetail;

trait CreateCouponTrait
{
    /**
     * getCoupon.
     *
     * @param int $couponType
     *
     * @return Coupon
     */
    public function getCoupon($couponType = Coupon::ALL, $discountType = Coupon::DISCOUNT_PRICE)
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
        $this->entityManager->persist($CouponDetail);
        $this->entityManager->flush($CouponDetail);

        return $Coupon;
    }

    /**
     * getTestData.
     *
     * @param int $couponType
     *
     * @return Coupon
     */
    public function getTestData($couponType = Coupon::ALL, $discountType = Coupon::DISCOUNT_PRICE)
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
        $Coupon->setCouponMember(false);
        $Coupon->setEnableFlag(true);
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
