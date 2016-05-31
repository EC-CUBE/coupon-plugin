<?php
/**
 * Created by PhpStorm.
 * User: lqdung
 * Date: 5/25/2016
 * Time: 8:18 AM
 */

namespace Plugin\Coupon\Tests\Web\Admin;

class CouponEventTest extends CouponCommon
{
    public function testOnRenderAdminOrderEditAfter()
    {
        $Customer = $this->createCustomer();
        $Order = $this->createOrder($Customer);
        $Coupon = $this->createCouponDetail();
        $this->createCouponOrder($Coupon, $Order);

        $this->client->request('GET',
            $this->app->url('admin_order_edit', array('id' => $Order->getId()))
        );

        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }
}