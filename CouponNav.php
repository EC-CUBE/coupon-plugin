<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\Coupon;

use Eccube\Common\EccubeNav;

class CouponNav implements EccubeNav
{
    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public static function getNav()
    {
        return [
            'order' => [
                'children' => [
                    'plugin_coupon' => [
                        'id' => 'plugin_coupon',
                        'name' => 'coupon.nav.label',
                        'url' => 'plugin_coupon_list',
                    ],
                ],
            ],
        ];
    }
}
