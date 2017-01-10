<?php
/*
 * This file is part of the Coupon plugin
 *
 * Copyright (C) 2016 LOCKON CO.,LTD. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\Coupon\Tests\Web\Admin;

use Eccube\Entity\Customer;
use Eccube\Common\Constant;
use Eccube\Tests\Web\Admin\AbstractAdminWebTestCase;
use Plugin\Coupon\Entity\Coupon;
use Plugin\Coupon\Entity\CouponDetail;

/**
 * Class CouponControllerTest.
 */
class CouponControllerTest extends AbstractAdminWebTestCase
{
    /**
     * @var Customer
     */
    protected $Customer;

    /**
     * setUp.
     */
    public function setUp()
    {
        parent::setUp();
        $this->Customer = $this->createCustomer();
    }

    /**
     * tearDown.
     */
    public function tearDown()
    {
        parent::tearDown();
    }

    /**
     * testIndex.
     */
    public function testIndex()
    {
        $this->client->request('GET', $this->app->url('plugin_coupon_list'));
        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    /**
     * testIndexList.
     */
    public function testIndexList()
    {
        $this->getCoupon();
        $crawler = $this->client->request('GET', $this->app->url('plugin_coupon_list'));
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->expected = '1 件';
        $this->actual = $crawler->filter('.box-title strong')->text();
        $this->verify();
    }

    /**
     * testEditNew.
     */
    public function testEditNew()
    {
        $this->client->request('GET', $this->app->url('plugin_coupon_new'));
        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    /**
     * testEdit.
     */
    public function testEdit()
    {
        $Coupon = $this->getCoupon();
        $this->client->request('GET', $this->app->url('plugin_coupon_edit', array('idaa' => $Coupon->getId())));
        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    /**
     * getCoupon.
     *
     * @param int $couponType
     * @param int $discountType
     *
     * @return Coupon
     */
    private function getCoupon($couponType = 1, $discountType = 1)
    {
        $this->getTestData($couponType, $discountType);
        /** @var \Plugin\Coupon\Entity\CouponCoupon $Coupon */
        $Coupon = $this->app['coupon.repository.coupon']->findOneBy(array('coupon_cd' => 'aaaaaaaa'));
        $Product = $this->app['eccube.repository.product']->find(1);
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
     * @param int $discountType
     *
     * @return Coupon
     */
    private function getTestData($couponType = 1, $discountType = 1)
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
