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