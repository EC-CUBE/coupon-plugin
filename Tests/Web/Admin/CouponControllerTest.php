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

use DoctrineProxy\__CG__\Eccube\Entity\Customer;
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
     * setUp
     */
    public function setUp()
    {
        parent::setUp();
        $this->Customer = $this->createCustomer();
    }

    /**
     * tearDown
     */
    public function tearDown()
    {
        parent::tearDown();
    }

    /**
     * testIndex
     */
    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->app->url('admin_coupon_list'));

        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    /**
     * testIndexList
     */
    public function testIndexList()
    {
        $this->getCoupon();

        $crawler = $this->client->request('GET', $this->app->url('admin_coupon_list'));

        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $this->expected = '1 件';
        $this->actual = $crawler->filter('.box-title strong')->text();

        $this->verify();
    }

    /**
     * testEditNew
     */
    public function testEditNew()
    {
        $crawler = $this->client->request('GET', $this->app->url('admin_coupon_new'));

        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    /**
     * testEdit
     */
    public function testEdit()
    {
        $Coupon = $this->getCoupon();

        $crawler = $this->client->request('GET', $this->app->url('admin_coupon_edit', array('idaa' => $Coupon->getId())));

        dump($crawler->html());

        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    /**
     * getCoupon
     * @param int $couponType
     * @param int $discountType
     * @return Coupon
     */
    private function getCoupon($couponType = 1, $discountType = 1)
    {
        $data = $this->getTestData($couponType, $discountType);

        $this->app['eccube.plugin.coupon.service.coupon']->createCoupon($data);

        /** @var \Plugin\Coupon\Entity\CouponCoupon $Coupon */
        $Coupon = $this->app['eccube.plugin.coupon.repository.coupon']->findOneBy(array('coupon_cd' => 'aaaaaaaa'));

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
     * getTestData
     * @param int $couponType
     * @param int $discountType
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
}
