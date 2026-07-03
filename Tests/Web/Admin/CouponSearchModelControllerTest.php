<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * https://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\Coupon44\Tests\Web\Admin;

use Eccube\Tests\Web\Admin\Order\AbstractEditControllerTestCase;
use Plugin\Coupon44\Tests\Fixtures\CreateCouponTrait;

/**
 * Class CouponSearchModelControllerTest.
 */
class CouponSearchModelControllerTest extends AbstractEditControllerTestCase
{
    use CreateCouponTrait;

    /**
     * setUp.
     */
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testSearchProduct(): void
    {
        $Product = $this->createProduct();
        $crawler = $this->client->request('POST', $this->generateUrl('plugin_coupon_search_product'),
            [
                'id' => $Product->getName(),
                'category_id' => '',
                'exist_product_id' => '',
            ],
            [],
            [
                'HTTP_X-Requested-With' => 'XMLHttpRequest',
            ]);
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertStringContainsString($Product->getName(), $crawler->html());
    }
}
