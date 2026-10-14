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
use PHPUnit\Framework\Attributes\DataProvider;
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

    /**
     * 整数の ID でない値は 400 を返す (DB の型エラーで 500 にしない).
     */
    #[DataProvider('invalidIdProvider')]
    public function testSearchProductWithInvalidCategoryId(mixed $categoryId): void
    {
        $this->client->request('POST', $this->generateUrl('plugin_coupon_search_product'),
            [
                'id' => '',
                'category_id' => $categoryId,
                'exist_product_id' => '',
            ],
            [],
            [
                'HTTP_X-Requested-With' => 'XMLHttpRequest',
            ]);
        self::assertSame(400, $this->client->getResponse()->getStatusCode());
    }

    #[DataProvider('invalidIdProvider')]
    public function testSearchProductWithInvalidExistProductId(mixed $existProductId): void
    {
        $this->client->request('POST', $this->generateUrl('plugin_coupon_search_product'),
            [
                'id' => '',
                'category_id' => '',
                'exist_product_id' => is_string($existProductId) ? '1,'.$existProductId : $existProductId,
            ],
            [],
            [
                'HTTP_X-Requested-With' => 'XMLHttpRequest',
            ]);
        self::assertSame(400, $this->client->getResponse()->getStatusCode());
    }

    /**
     * 除外する商品 ID の空の要素は読み飛ばし, 指定した商品は結果から除く.
     */
    public function testSearchProductWithExistProductId(): void
    {
        $Product = $this->createProduct();
        $crawler = $this->client->request('POST', $this->generateUrl('plugin_coupon_search_product'),
            [
                'id' => $Product->getName(),
                'category_id' => '',
                'exist_product_id' => ','.$Product->getId().',',
            ],
            [],
            [
                'HTTP_X-Requested-With' => 'XMLHttpRequest',
            ]);
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertStringNotContainsString($Product->getName(), $crawler->html());
    }

    #[DataProvider('invalidIdProvider')]
    public function testSearchCategoryWithInvalidCategoryId(mixed $categoryId): void
    {
        $this->client->request('POST', $this->generateUrl('plugin_coupon_search_category'),
            [
                'category_id' => $categoryId,
                'exist_category_id' => '',
            ],
            [],
            [
                'HTTP_X-Requested-With' => 'XMLHttpRequest',
            ]);
        self::assertSame(400, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @return array<string, array<mixed>>
     */
    public static function invalidIdProvider(): array
    {
        return [
            '文字列' => ['abc'],
            '整数の範囲外' => ['99999999999'],
            '負の数' => ['-1'],
            '配列' => [['1']],
        ];
    }
}
