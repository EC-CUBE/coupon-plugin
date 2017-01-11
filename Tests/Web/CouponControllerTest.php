<?php
/*
 * This file is part of the Coupon plugin
 *
 * Copyright (C) 2016 LOCKON CO.,LTD. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\Coupon\Tests\Web;

use Eccube\Entity\Customer;
use Eccube\Common\Constant;
use Eccube\Tests\Web\AbstractWebTestCase;
use Plugin\Coupon\Entity\Coupon;
use Plugin\Coupon\Entity\CouponDetail;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class CouponControllerTest.
 */
class CouponControllerTest extends AbstractWebTestCase
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
        $this->initializeMailCatcher();
        $this->Customer = $this->createCustomer();
    }

    /**
     * tearDown.
     */
    public function tearDown()
    {
        $this->cleanUpMailCatcherMessages();
        parent::tearDown();
    }

    /**
     * test routing shopping coupon.
     */
    public function testRoutingShoppingCoupon()
    {
        $this->routingShopping();

        $crawler = $this->client->request('GET', $this->app->url('plugin_coupon_shopping'));

        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $this->expected = 'クーポンコードの入力';
        $this->actual = $crawler->filter('h1.page-heading')->text();

        $this->verify();
    }

    /**
     * testRoutingShoppingCouponNot.
     */
    public function testRoutingShoppingCouponNot()
    {
        $client = $this->client;
        $client->request('GET', $this->app->url('plugin_coupon_shopping'));
        $this->assertFalse($client->getResponse()->isSuccessful());
    }

    /**
     * testShoppingCouponPostError.
     */
    public function testShoppingCouponPostError()
    {
        $this->routingShopping();
        $crawler = $this->client->request('GET', $this->app->url('plugin_coupon_shopping'));
        $form = $this->getForm($crawler, 'aaaa');
        /** @var \Symfony\Component\DomCrawler\Crawler $crawler */
        $crawler = $this->client->submit($form);
        // 存在しないクーポンコードで検索したためエラーになるためリダイレクトはされない
        $this->assertFalse($this->client->getResponse()->isRedirection());
    }

    /**
     * testShoppingCoupon.
     */
    public function testShoppingCoupon()
    {
        $this->routingShopping();
        $crawler = $this->client->request('GET', $this->app->url('plugin_coupon_shopping'));
        $Coupon = $this->getCoupon();
        $form = $this->getForm($crawler, $Coupon->getCouponCd());

        /** @var \Symfony\Component\DomCrawler\Crawler $crawler */
        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isRedirection());

        $crawler = $this->client->request('GET', $this->app->url('shopping'));
        $this->expected = '利用しています。';
        $this->actual = $crawler->filter('strong.text-danger')->text();
        $this->assertContains($this->expected, $this->actual);

        $this->client->request('GET', $this->app->url('shopping'));
        $this->scenarioComplete($this->client, $this->app->path('shopping_confirm'));
        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('shopping_complete')));
    }

    /**
     * testRenderMypage.
     */
    public function testRenderMypage()
    {
        $this->routingShopping();
        $crawler = $this->client->request('GET', $this->app->url('plugin_coupon_shopping'));
        $Coupon = $this->getCoupon();
        $form = $this->getForm($crawler, $Coupon->getCouponCd());

        /** @var \Symfony\Component\DomCrawler\Crawler $crawler */
        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isRedirection());

        $crawler = $this->client->request('GET', $this->app->url('shopping'));
        $this->expected = '利用しています。';
        $this->actual = $crawler->filter('strong.text-danger')->text();
        $this->assertContains($this->expected, $this->actual);

        $this->client->request('GET', $this->app->url('shopping'));
        $this->scenarioComplete($this->client, $this->app->path('shopping_confirm'));
        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('shopping_complete')));

        $repository = $this->app['coupon.repository.coupon_order'];
        // クーポン受注情報を取得する
        $CouponOrder = $repository->findOneBy(array(
            'coupon_id' => $Coupon->getId(),
        ));

        $Order =  $this->app['eccube.repository.order']->find($CouponOrder->getOrderId());
        $crawler = $this->client->request('GET', $this->app->url('mypage_history', array('id' => $Order->getId())));
        $this->assertContains('ご利用クーポンコード', $crawler->html());
    }

    /**
     * testCouponLowerLimit.
     */
    public function testCouponLowerLimit()
    {
        $em = $this->app['orm.em'];
        $this->routingShopping();
        $crawler = $this->client->request('GET', $this->app->url('plugin_coupon_shopping'));
        $Coupon = $this->getCoupon();
        $Coupon->setCouponLowerLimit(600000);
        // クーポン情報を登録する
        $em->persist($Coupon);
        $em->flush($Coupon);
        $form = $this->getForm($crawler, $Coupon->getCouponCd());

        /** @var \Symfony\Component\DomCrawler\Crawler $crawler */
        $this->client->submit($form);
        $this->assertFalse($this->client->getResponse()->isRedirection());
    }

    /**
     * testShoppingCouponDiscountType1.
     */
    public function testShoppingCouponDiscountType1()
    {
        $this->routingShopping();

        $crawler = $this->client->request('GET', $this->app->url('plugin_coupon_shopping'));

        $Coupon = $this->getCoupon(1, 1);

        $form = $this->getForm($crawler, $Coupon->getCouponCd());

        /** @var \Symfony\Component\DomCrawler\Crawler $crawler */
        $crawler = $this->client->submit($form);

        $this->assertTrue($this->client->getResponse()->isRedirection());

        $crawler = $this->client->request('GET', $this->app->url('shopping'));

        $Order = $this->app['eccube.repository.order']->findOneBy(
            array(
                'Customer' => $this->Customer,
            )
        );

        $this->actual = $Coupon->getDiscountPrice();

        $this->expected = $Order->getDiscount();

        $this->verify();
    }

    /**
     * testShoppingCouponDiscountType2.
     */
    public function testShoppingCouponDiscountType2()
    {
        $this->routingShopping();

        $crawler = $this->client->request('GET', $this->app->url('plugin_coupon_shopping'));

        $Coupon = $this->getCoupon(1, 2);

        $form = $this->getForm($crawler, $Coupon->getCouponCd());

        /** @var \Symfony\Component\DomCrawler\Crawler $crawler */
        $crawler = $this->client->submit($form);

        $this->assertTrue($this->client->getResponse()->isRedirection());

        $crawler = $this->client->request('GET', $this->app->url('shopping'));

        $Order = $this->app['eccube.repository.order']->findOneBy(
            array(
                'Customer' => $this->Customer,
            )
        );

        $CouponOrder = $this->app['coupon.repository.coupon_order']->findOneBy(array('pre_order_id' => $Order->getPreOrderId()));

        $this->actual = $CouponOrder->getDiscount();

        $this->expected = $Order->getDiscount();

        $this->verify();
    }

    /**
     * routingShopping.
     */
    private function routingShopping()
    {
        // カート画面
        $this->client->request('POST', '/cart/add', array('product_class_id' => 1));
        $this->app['eccube.service.cart']->lock();

        $this->Customer = $this->logIn();

        $this->client->request('GET', $this->app->url('shopping'));
    }

    /**
     * get coupon form.
     *
     * @param Crawler $crawler
     * @param string  $couponCd
     *
     * @return \Symfony\Component\DomCrawler\Form
     */
    private function getForm(Crawler $crawler, $couponCd = '')
    {
        $form = $crawler->selectButton('登録する')->form();
        $form['front_plugin_coupon_shopping[_token]'] = 'dummy';
        $form['front_plugin_coupon_shopping[coupon_cd]'] = $couponCd;
        $form['front_plugin_coupon_shopping[coupon_use]'] = 1;

        return $form;
    }

    /**
     * @param int $couponType
     * @param int $discountType
     *
     * @return Coupon
     */
    private function getCoupon($couponType = 1, $discountType = 1)
    {
        $this->getTestData($couponType, $discountType);
        /** @var Coupon $Coupon */
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
     * scenarioConfirm
     * @param  object $client
     * @return Crawler mixed
     */
    private function scenarioConfirm($client)
    {
        $crawler = $client->request('GET', $this->app->path('shopping'));

        return $crawler;
    }

    /**
     * scenarioComplete
     * @param object $client
     * @param string $confirmUrl
     * @param array $shippings
     * @return Crawler $crawler
     */
    private function scenarioComplete($client, $confirmUrl, array $shippings = array())
    {
        $faker = $this->getFaker();
        if (count($shippings) < 1) {
            $shippings = array(
                array(
                    'delivery' => 1,
                    'deliveryTime' => 1,
                ),
            );
        }

        $crawler = $client->request(
            'POST',
            $confirmUrl,
            array('shopping' => array(
                    'shippings' => $shippings,
                    'payment' => 1,
                    'message' => $faker->text(),
                    '_token' => 'dummy',
                ),
            )
        );

        return $crawler;
    }

    /**
     * getTestData.
     *
     * @param int $couponType
     * @param int $discountType
     *
     * @return Coupon
     */
    private function getTestData($couponType = 3, $discountType = 1)
    {
        $Coupon = new Coupon();

        $date1 = new \DateTime();
        $date2 = new \DateTime();

        $Coupon->setCouponCd('aaaaaaaa');
        $Coupon->setCouponType($couponType);
        $Coupon->setCouponName('クーポン');
        $Coupon->setDiscountType($discountType);
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
