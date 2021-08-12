<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\Coupon4\Tests\Web\Admin;

use Eccube\Entity\Customer;
use Eccube\Entity\Product;
use Eccube\Repository\ProductRepository;
use Eccube\Tests\Web\Admin\AbstractAdminWebTestCase;
use Plugin\Coupon4\Entity\Coupon;
use Plugin\Coupon4\Repository\CouponRepository;
use Plugin\Coupon4\Tests\Fixtures\CreateCouponTrait;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class CouponControllerTest.
 */
class CouponControllerTest extends AbstractAdminWebTestCase
{
    use CreateCouponTrait;

    /**
     * @var Customer
     */
    protected $Customer;

    /**
     * @var CouponRepository
     */
    private $couponRepository;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * setUp.
     */
    public function setUp()
    {
        parent::setUp();
        $this->Customer = $this->createCustomer();
        $this->couponRepository = $this->entityManager->getRepository(Coupon::class);
        $this->productRepository = $this->entityManager->getRepository(Product::class);
        $this->deleteAllRows(['plg_coupon_order', 'plg_coupon_detail', 'plg_coupon']);
    }

    /**
     * testIndex.
     */
    public function testIndex()
    {
        $this->client->request('GET', $this->generateUrl('plugin_coupon_list'));
        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    /**
     * testIndexList.
     */
    public function testIndexList()
    {
        $Coupon = $this->getCoupon();
        $crawler = $this->client->request('GET', $this->generateUrl('plugin_coupon_list'));
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->expected = '1 件';
        $this->actual = $crawler->filter('.normal strong')->text();
        $this->verify();
    }

    /**
     * testEditNew.
     */
    public function testEditNew()
    {
        $crawler = $this->client->request('GET', $this->generateUrl('plugin_coupon_new'));
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $form = $this->getForm($crawler);

        /* @var \Symfony\Component\DomCrawler\Crawler $crawler */
        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isRedirect($this->generateUrl('plugin_coupon_list')));
    }

    /**
     * testEdit.
     */
    public function testEdit()
    {
        $Coupon = $this->getCoupon();
        $crawler = $this->client->request('GET', $this->generateUrl('plugin_coupon_edit', ['id' => $Coupon->getId()]));
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $form = $this->getForm($crawler);
        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isRedirect($this->generateUrl('plugin_coupon_list')));
    }

    public function testEditWithNotFound()
    {
        $Coupon = $this->getCoupon();
        $crawler = $this->client->request('GET', $this->generateUrl('plugin_coupon_edit', ['id' => 999999]));
        $crawler = $this->client->followRedirect();
        $this->assertContains('クーポンが存在しません。', $crawler->html());
    }

    /**
     * testEnable.
     */
    public function testEnable()
    {
        $Coupon = $this->getTestData();
        $this->client->request('PUT', $this->generateUrl('plugin_coupon_enable', ['id' => $Coupon->getId()]));
        $this->assertTrue($this->client->getResponse()->isRedirect($this->generateUrl('plugin_coupon_list')));
    }

    public function testEnableWithNotFound()
    {
        $Coupon = $this->getTestData();
        $this->client->request('PUT', $this->generateUrl('plugin_coupon_enable', ['id' => 999999]));
        $this->assertTrue($this->client->getResponse()->isNotFound());
    }

    /**
     * testEnable.
     */
    public function testDelete()
    {
        $Coupon = $this->getTestData();
        $this->client->request('DELETE', $this->generateUrl('plugin_coupon_delete', ['id' => $Coupon->getId()]));
        $this->assertTrue($this->client->getResponse()->isRedirect($this->generateUrl('plugin_coupon_list')));
    }

    public function testDeleteWithNotFound()
    {
        $Coupon = $this->getTestData();
        $this->client->request('DELETE', $this->generateUrl('plugin_coupon_delete', ['id' => 999999]));
        $this->assertTrue($this->client->getResponse()->isNotFound());
    }

    /**
     * get coupon form.
     *
     * @param Crawler $crawler
     *
     * @return \Symfony\Component\DomCrawler\Form
     */
    private function getForm(Crawler $crawler)
    {
        $current = new \DateTime();
        $form = $crawler->selectButton('登録する')->form();
        $form['coupon[_token]'] = 'dummy';
        $form['coupon[coupon_cd]'] = 'aaaaaaa';
        $form['coupon[coupon_name]'] = 'aaaaaa';
        $form['coupon[coupon_type]'] = 3;
        $form['coupon[coupon_member]'] = 1;
        $form['coupon[discount_type]'] = 1;
        $form['coupon[discount_price]'] = 100;
        $form['coupon[coupon_lower_limit]'] = 100;
        $form['coupon[available_from_date]'] = $current->modify('-15 days')->format('Y-m-d');
        $form['coupon[available_to_date]'] = $current->modify('+15 days')->format('Y-m-d');
        $form['coupon[coupon_release]'] = 100;

        return $form;
    }
}
