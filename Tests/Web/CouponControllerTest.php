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

namespace Plugin\Coupon4\Tests\Web;

use Eccube\Entity\BaseInfo;
use Eccube\Entity\Customer;
use Eccube\Entity\Order;
use Eccube\Entity\Product;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\OrderRepository;
use Eccube\Repository\ProductRepository;
use Eccube\Service\CartService;
use Eccube\Tests\Web\AbstractShoppingControllerTestCase;
use Plugin\Coupon4\Entity\Coupon;
use Plugin\Coupon4\Entity\CouponOrder;
use Plugin\Coupon4\Tests\Fixtures\CreateCouponTrait;
use Plugin\Coupon4\Repository\CouponOrderRepository;
use Plugin\Coupon4\Repository\CouponRepository;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class CouponControllerTest.
 */
class CouponControllerTest extends AbstractShoppingControllerTestCase
{
    use CreateCouponTrait;

    /**
     * @var CartService
     */
    private $cartService;

    /**
     * @var CouponRepository
     */
    private $couponRepository;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var Customer
     */
    private $Customer;

    /**
     * @var BaseInfoRepository
     */
    private $baseInfoRepository;

    /**
     * @var CouponOrderRepository
     */
    private $couponOrderRepository;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * setUp.
     */
    public function setUp()
    {
        parent::setUp();
        $this->couponRepository = $this->entityManager->getRepository(Coupon::class);
        $this->productRepository = $this->entityManager->getRepository(Product::class);
        $this->cartService = self::$container->get(CartService::class);
        $this->Customer = $this->createCustomer();
        $this->baseInfoRepository = $this->entityManager->getRepository(BaseInfo::class);
        $this->couponOrderRepository = $this->entityManager->getRepository(CouponOrder::class);
        $this->orderRepository = $this->entityManager->getRepository(Order::class);
    }

    /**
     * test routing shopping coupon.
     */
    public function testRoutingShoppingCoupon()
    {
        $this->routingShopping();

        $crawler = $this->client->request('GET', $this->generateUrl('plugin_coupon_shopping'));

        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $this->expected = 'クーポンコードの入力';
        $this->actual = $crawler->filter('.ec-pageHeader h1')->text();

        $this->verify();
    }

    /**
     * testShoppingCouponPostError.
     */
    public function testShoppingCouponPostError()
    {
        $this->routingShopping();
        $crawler = $this->client->request('GET', $this->generateUrl('plugin_coupon_shopping'));
        $form = $this->getForm($crawler, 'aaaa');
        $this->client->submit($form);
        // 存在しないクーポンコードで検索したためエラーになるためリダイレクトはされない
        $this->assertFalse($this->client->getResponse()->isRedirection());
    }

    /**
     * testShoppingCoupon.
     */
    public function testShoppingCoupon()
    {
        $this->routingShopping();
        $crawler = $this->client->request('GET', $this->generateUrl('plugin_coupon_shopping'));
        $Coupon = $this->getCoupon();
        $form = $this->getForm($crawler, $Coupon->getCouponCd());
        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isRedirection());

        $crawler = $this->client->followRedirect();
        $this->expected = '利用しています。';
        $this->actual = $crawler->filter('strong.text-danger')->text();
        $this->assertContains($this->expected, $this->actual);

        $form = $crawler->selectButton('確認する')->form();
        $crawler = $this->client->submit($form);

        $this->client->enableProfiler();
        // 完了画面
        $formConfirm = $crawler->selectButton('注文する')->form();
        $this->client->submit($formConfirm);
        $this->assertTrue($this->client->getResponse()->isRedirect($this->generateUrl('shopping_complete')));

        $BaseInfo = $this->baseInfoRepository->get();
        $mailCollector = $this->getMailCollector(false);
        $Messages = $mailCollector->getMessages();

        /** @var \Swift_Message $Message */
        $Message = $Messages[0];

        $this->expected = '['.$BaseInfo->getShopName().'] ご注文ありがとうございます';
        $this->actual = $Message->getSubject();
        $this->verify();

        // assert mail content
        $this->assertContains($Coupon->getCouponCd(), $Message->getBody());

        // 生成された受注のチェック
        /** @var Order $Order */
        $Order = $this->entityManager->getRepository(Order::class)->findOneBy(
            [
                'Customer' => $this->Customer,
            ]
        );

        $this->expected = round(0 - $Coupon->getDiscountPrice(), 2);
        $this->actual = $Order->getItems()->getDiscounts()->first()->getPrice();
        $this->verify();
    }

    /**
     * testRenderMypage.
     */
    public function testRenderMypage()
    {
        $this->routingShopping();
        $crawler = $this->client->request('GET', $this->generateUrl('plugin_coupon_shopping'));
        $Coupon = $this->getCoupon();

        $form = $this->getForm($crawler, $Coupon->getCouponCd());
        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isRedirection());

        $crawler = $this->client->followRedirect();
        $this->expected = '利用しています。';
        $this->actual = $crawler->filter('strong.text-danger')->text();
        $this->assertContains($this->expected, $this->actual);

        $form = $crawler->selectButton('確認する')->form();
        $crawler = $this->client->submit($form);

        // 完了画面
        $formConfirm = $crawler->selectButton('注文する')->form();
        $this->client->submit($formConfirm);
        $this->assertTrue($this->client->getResponse()->isRedirect($this->generateUrl('shopping_complete')));

        // クーポン受注情報を取得する
        $CouponOrder = $this->couponOrderRepository->findOneBy([
            'coupon_id' => $Coupon->getId(),
        ]);

        $Order = $this->orderRepository->find($CouponOrder->getOrderId());
        $crawler = $this->client->request('GET', $this->generateUrl('mypage_history', ['order_no' => $Order->getOrderNo()]));
        $this->assertContains('ご利用クーポンコード', $crawler->html());
    }

    /**
     * testCouponLowerLimit.
     */
    public function testCouponLowerLimit()
    {
        $this->routingShopping();
        $crawler = $this->client->request('GET', $this->generateUrl('plugin_coupon_shopping'));
        $Coupon = $this->getCoupon();
        $Coupon->setCouponLowerLimit(9999999999);
        // クーポン情報を登録する
        $this->entityManager->persist($Coupon);
        $this->entityManager->flush($Coupon);
        $form = $this->getForm($crawler, $Coupon->getCouponCd());

        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isRedirection());

        /* @var \Symfony\Component\DomCrawler\Crawler $crawler */
        $crawler = $this->client->followRedirect();
        $this->assertContains('9,999,999,999円以上', $crawler->html());
    }

    /**
     * testShoppingCouponDiscountType1.
     */
    public function testShoppingCouponDiscountTypePrice()
    {
        $this->routingShopping();
        $crawler = $this->client->request('GET', $this->generateUrl('plugin_coupon_shopping'));
        $Coupon = $this->getCoupon(Coupon::ALL, Coupon::DISCOUNT_PRICE);

        $form = $this->getForm($crawler, $Coupon->getCouponCd());
        $this->client->submit($form);

        // shopping index
        $crawler = $this->client->followRedirect();
        $this->expected = '利用しています。';
        $this->actual = $crawler->filter('strong.text-danger')->text();
        $this->assertContains($this->expected, $this->actual);
        $form = $crawler->selectButton('確認する')->form();
        $crawler = $this->client->submit($form);

        // confirm
        $formConfirm = $crawler->selectButton('注文する')->form();
        $this->client->submit($formConfirm);
        $this->assertTrue($this->client->getResponse()->isRedirect($this->generateUrl('shopping_complete')));

        /** @var Order $Order */
        $Order = $this->orderRepository->findOneBy(
            [
                'Customer' => $this->Customer,
            ]
        );

        $this->actual = $Coupon->getDiscountPrice();
        $this->expected = 0 - $Order->getItems()->getDiscounts()->first()->getPrice();
        $this->verify();
    }

    /**
     * testShoppingCouponDiscountType2.
     */
    public function testShoppingCouponDiscountTypeRate()
    {
        $this->routingShopping();

        $crawler = $this->client->request('GET', $this->generateUrl('plugin_coupon_shopping'));

        $Coupon = $this->getCoupon(Coupon::ALL, Coupon::DISCOUNT_RATE);

        $form = $this->getForm($crawler, $Coupon->getCouponCd());
        $this->client->submit($form);

        // shopping index
        $crawler = $this->client->followRedirect();
        $this->expected = '利用しています。';
        $this->actual = $crawler->filter('strong.text-danger')->text();
        $this->assertContains($this->expected, $this->actual);
        $form = $crawler->selectButton('確認する')->form();
        $crawler = $this->client->submit($form);

        // confirm
        $formConfirm = $crawler->selectButton('注文する')->form();
        $this->client->submit($formConfirm);
        $this->assertTrue($this->client->getResponse()->isRedirect($this->generateUrl('shopping_complete')));

        /** @var Order $Order */
        $Order = $this->orderRepository->findOneBy(
            [
                'Customer' => $this->Customer,
            ]
        );

        $CouponOrder = $this->couponOrderRepository->findOneBy(['pre_order_id' => $Order->getPreOrderId()]);

        $this->actual = $CouponOrder->getDiscount();
        $this->expected = 0 - $Order->getItems()->getDiscounts()->first()->getPrice();
        $this->verify();
    }

    /**
     * 非会員情報入力→注文手続画面→購入確認画面→完了画面
     */
    public function testCompleteWithNonmember()
    {
        $this->scenarioCartIn();

        $formData = $this->createNonmemberFormData();
        $this->scenarioInput($formData);
        $this->client->followRedirect();

        $crawler = $this->scenarioConfirm();
        $this->expected = 'ご注文手続き';
        $this->actual = $crawler->filter('.ec-pageHeader h1')->text();
        $this->verify();

        $crawler = $this->scenarioComplete(null, $this->generateUrl('shopping_confirm'),
                                           [
                                               [
                                                   'Delivery' => 1,
                                                   'DeliveryTime' => '',
                                               ],
                                           ]);
        // $this->expected = 'ご注文内容のご確認';
        // $this->actual = $crawler->filter('.ec-pageHeader h1')->text();
        // $this->verify();

        $crawler = $this->client->request('GET', $this->generateUrl('plugin_coupon_shopping'));
        $Coupon = $this->getCoupon();
        $form = $this->getForm($crawler, $Coupon->getCouponCd());
        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isRedirection());

        $crawler = $this->client->followRedirect();
        $this->expected = '利用しています。';
        $this->actual = $crawler->filter('strong.text-danger')->text();
        $this->assertContains($this->expected, $this->actual);

        $this->scenarioCheckout();
        $this->assertTrue($this->client->getResponse()->isRedirect($this->generateUrl('shopping_complete')));

        $mailCollector = $this->getMailCollector(false);
        $Messages = $mailCollector->getMessages();
        $Message = $Messages[0];

        $this->expected = 'ご注文ありがとうございます';
        $this->actual = $Message->getSubject();
        $this->assertContains($this->expected, $this->actual);

        preg_match('/ご注文番号：([0-9]+)/u', $Message->getBody(), $matched);
        list(, $order_id) =  $matched;
        /** @var Order $Order */
        $Order = $this->orderRepository->find($order_id);

        $this->actual = $Coupon->getDiscountPrice();
        $this->expected = 0 - $Order->getItems()->getDiscounts()->first()->getPrice();
        $this->verify();
    }

    /**
     * 重複利用チェック(会員)
     */
    public function testDuplicateCouponWithCustomer()
    {
        $this->routingShopping();
        $crawler = $this->client->request('GET', $this->generateUrl('plugin_coupon_shopping'));
        $Coupon = $this->getCoupon();
        $form = $this->getForm($crawler, $Coupon->getCouponCd());
        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isRedirection());

        $crawler = $this->client->followRedirect();
        $this->expected = '利用しています。';
        $this->actual = $crawler->filter('strong.text-danger')->text();
        $this->assertContains($this->expected, $this->actual);

        $form = $crawler->selectButton('確認する')->form();
        $crawler = $this->client->submit($form);

        // 完了画面
        $formConfirm = $crawler->selectButton('注文する')->form();
        $this->client->submit($formConfirm);
        $this->assertTrue($this->client->getResponse()->isRedirect($this->generateUrl('shopping_complete')));

        $this->routingShopping();
        $crawler = $this->client->request('GET', $this->generateUrl('plugin_coupon_shopping'));
        $form = $this->getForm($crawler, $Coupon->getCouponCd());
        $crawler = $this->client->submit($form);

        $this->expected = 'このクーポンは既にご利用いただいています。';
        $this->actual = $crawler->html();
        $this->assertContains($this->expected, $this->actual);
    }

    /**
     * 重複利用チェック(非会員)
     */
    public function testDuplicateCouponWithNonmember()
    {
        $this->scenarioCartIn();

        $formData = $this->createNonmemberFormData();
        $this->scenarioInput($formData);
        $this->client->followRedirect();

        $crawler = $this->scenarioConfirm();
        $this->expected = 'ご注文手続き';
        $this->actual = $crawler->filter('.ec-pageHeader h1')->text();
        $this->verify();

        $crawler = $this->scenarioComplete(null, $this->generateUrl('shopping_confirm'),
                                           [
                                               [
                                                   'Delivery' => 1,
                                                   'DeliveryTime' => '',
                                               ],
                                           ]);

        $crawler = $this->client->request('GET', $this->generateUrl('plugin_coupon_shopping'));
        $Coupon = $this->getCoupon();
        $form = $this->getForm($crawler, $Coupon->getCouponCd());
        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isRedirection());

        $crawler = $this->client->followRedirect();
        $this->expected = '利用しています。';
        $this->actual = $crawler->filter('strong.text-danger')->text();
        $this->assertContains($this->expected, $this->actual);

        $this->scenarioCheckout();
        $this->assertTrue($this->client->getResponse()->isRedirect($this->generateUrl('shopping_complete')));

        // 同じクーポンで再購入
        $this->scenarioCartIn();

        $this->scenarioInput($formData);
        $this->client->followRedirect();

        $crawler = $this->scenarioConfirm();
        $this->expected = 'ご注文手続き';
        $this->actual = $crawler->filter('.ec-pageHeader h1')->text();
        $this->verify();

        $crawler = $this->scenarioComplete(null, $this->generateUrl('shopping_confirm'),
                                           [
                                               [
                                                   'Delivery' => 1,
                                                   'DeliveryTime' => '',
                                               ],
                                           ]);

        $crawler = $this->client->request('GET', $this->generateUrl('plugin_coupon_shopping'));

        $form = $this->getForm($crawler, $Coupon->getCouponCd());
        $crawler = $this->client->submit($form);

        $this->expected = 'このクーポンは既にご利用いただいています。';
        $this->actual = $crawler->html();
        $this->assertContains($this->expected, $this->actual);
    }

    /**
     * routingShopping.
     */
    private function routingShopping()
    {
        // カート画面
        $this->scenarioCartIn($this->Customer);

        // 手続き画面
        $crawler = $this->scenarioConfirm($this->Customer);

        return $crawler;
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
        $form['coupon_use[_token]'] = 'dummy';
        $form['coupon_use[coupon_cd]'] = $couponCd;
        $form['coupon_use[coupon_use]'] = 1;

        return $form;
    }

    private function createNonmemberFormData()
    {
        $faker = $this->getFaker();
        $email = $faker->safeEmail;
        $form = parent::createShippingFormData();
        $form['email'] = [
            'first' => $email,
            'second' => $email,
        ];

        return $form;
    }
}
