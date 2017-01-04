<?php
/**
 * This file is part of EC-CUBE
 *
 * Copyright(c) 2000-2015 LOCKON CO.,LTD. All Rights Reserved.
 * http://www.lockon.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\Coupon\Tests\Web\Admin;


use Eccube\Common\Constant;
use Eccube\Tests\Web\Admin\AbstractAdminWebTestCase;
use Plugin\Coupon\Entity\CouponCoupon;
use Plugin\Coupon\Entity\CouponCouponDetail;
use Plugin\Coupon\Entity\CouponCouponOrder;
use Symfony\Component\DomCrawler\Crawler;

class CouponCommon extends AbstractAdminWebTestCase
{
    protected function createCouponDetail($couponType = 1, $discountType = 1)
    {
        $Coupon = $this->createCoupon($couponType, $discountType);

        $this->app['eccube.plugin.coupon.service.coupon']->createCoupon($Coupon);

        /** @var \Plugin\Coupon\Entity\CouponCoupon $Coupon */
        $Coupon = $this->app['eccube.plugin.coupon.repository.coupon']->findOneBy(array('coupon_cd' => 'aaaaaaaa'));

        $Product = $this->app['eccube.repository.product']->find(1);

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

        $this->app['eccube.plugin.coupon.service.coupon']->createCoupon($Coupon);

        return $Coupon;
    }

    protected function createCoupon($couponType = 1, $discountType = 1)
    {
        $Coupon = new CouponCoupon();

        $date1 = new \DateTime();
        $date2 = new \DateTime();

        $Coupon->setCouponCd('aaaaaaaa');
        $Coupon->setCouponType($couponType);
        $Coupon->setCouponName('クーポン');
        $Coupon->setDiscountType($discountType);
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

    protected function getForm(Crawler $crawler, $couponCd = '')
    {
        $form = $crawler->selectButton('登録')->form();
        $form['admin_coupon[coupon_cd]'] = $couponCd;
        $form['admin_coupon[_token]'] = 'dummy';

        return $form;
    }

    protected function createCouponOrder(\Plugin\Coupon\Entity\CouponCoupon $Coupon, \Eccube\Entity\Order $Order)
    {
        $CouponOrder = new CouponCouponOrder();
        $current_date = new \DateTime();
        $discount = $Coupon->getDiscountPrice();
        if ($Coupon->getDiscountType() == 2) {
            $discount = $Coupon->getDiscountRate() * $Order->getTotal() / 100;
        }
        $CouponOrder->setCouponCd($Coupon->getCouponCd())
            ->setOrderId($Order->getId())
            ->setPreOrderId($Order->getPreOrderId())
            ->setOrderDate($Order->getOrderDate())
            ->setDelFlg(Constant::DISABLED)
            ->setCreateDate($current_date->format('Y-m-d'))
            ->setUpdateDate($current_date->format('Y-m-d'))
            ->setUserId($Order->getCustomer()->getId())
            ->setEmail($Order->getCustomer()->getEmail())
            ->setDiscount($discount);
        $this->app['eccube.plugin.coupon.repository.coupon_order']->save($CouponOrder);

        $Order->setDiscount($discount)
            ->setTotal($Order->getTotal() - $discount)
            ->setPaymentTotal($Order->getPaymentTotal() - $discount);

        $this->app['orm.em']->persist($Order);
        $this->app['orm.em']->flush();

        return $CouponOrder;
    }
}
