<?php

namespace Plugin\Coupon\Tests\Web\Admin;

use Eccube\Common\Constant;
use Eccube\Tests\Web\Admin\AbstractAdminWebTestCase;
use Plugin\Coupon\Entity\CouponCoupon;
use Plugin\Coupon\Entity\CouponCouponDetail;

/**
 * Class CouponControllerTest
 *
 * @package Plugin\Coupon\Tests\Web\Admin
 */
class CouponControllerTest extends AbstractAdminWebTestCase
{

    protected $Customer;

    public function setUp()
    {
        parent::setUp();
        $this->Customer = $this->createCustomer();
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    public function testIndex()
    {

        $crawler = $this->client->request('GET', $this->app->url('admin_coupon_list'));

        $this->assertTrue($this->client->getResponse()->isSuccessful());

    }


    public function testIndexList()
    {
        // delete record before test
        $this->deleteAllRows(array('plg_coupon'));
        $this->getCoupon();

        $crawler = $this->client->request('GET', $this->app->url('admin_coupon_list'));

        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $this->expected = '1 件';
        $this->actual = $crawler->filter('.box-title strong')->text();

         $this->verify();

    }


    public function testEditNew()
    {

        $crawler = $this->client->request('GET', $this->app->url('admin_coupon_new'));

        $this->assertTrue($this->client->getResponse()->isSuccessful());

    }


    public function testEdit()
    {

        $Coupon = $this->getCoupon();

        $crawler = $this->client->request('GET', $this->app->url('admin_coupon_edit', array('idaa' => $Coupon->getId())));

        dump($crawler->html());

        $this->assertTrue($this->client->getResponse()->isSuccessful());

    }

























    private function getCoupon($couponType = 1, $discountType = 1)
    {
        $data = $this->getTestData($couponType, $discountType);

        $this->app['eccube.plugin.coupon.service.coupon']->createCoupon($data);

        /** @var \Plugin\Coupon\Entity\CouponCoupon $Coupon */
        $Coupon = $this->app['eccube.plugin.coupon.repository.coupon']->findOneBy(array('coupon_cd' => 'aaaaaaaa'));


        $Product = $this->app['eccube.repository.product']->find(1);

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


    private function getTestData($couponType = 1, $discountType = 1)
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

}