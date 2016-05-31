<?php
/**
 * Created by PhpStorm.
 * User: lqdung
 * Date: 5/24/2016
 * Time: 2:58 PM
 */

namespace Plugin\Coupon\Tests\Web\Admin;

class CouponSearchModelControllerTest extends CouponCommon
{
    public function testSearchProduct()
    {
        $this->client->request('POST',
            $this->app->url('admin_coupon_search_product'),
            array(
                'id' => 1,
                'category_id' => 1,
            ), array(),
            array('HTTP_X-Requested-With' => 'XMLHttpRequest')
        );

        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    public function testSearchProduct_ExistProduct()
    {
        $Coupon = $this->createCouponDetail();
        $CouponDetail = $Coupon->getCouponDetails();
        $this->client->request('POST',
            $this->app->url('admin_coupon_search_product'),
            array(
                'exist_product_id' => $CouponDetail[0]->getProduct()->getId(),
            ), array(),
            array('HTTP_X-Requested-With' => 'XMLHttpRequest')
        );

        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    public function testSearchCategory()
    {
        $this->client->request('POST',
            $this->app->url('admin_coupon_search_category'),
            array(
                'category_id' => 1,
            ), array(),
            array('HTTP_X-Requested-With' => 'XMLHttpRequest')
        );

        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    public function testSearchCategory_ExistCategory()
    {
        $this->client->request('POST',
            $this->app->url('admin_coupon_search_category'),
            array(
                'category_id' => 1,
                'exist_category_id' => 1,
            ), array(),
            array('HTTP_X-Requested-With' => 'XMLHttpRequest')
        );

        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }
}