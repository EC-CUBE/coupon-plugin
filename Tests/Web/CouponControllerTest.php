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

namespace Plugin\Coupon\Tests\Web;

use Eccube\Entity\Customer;
use Eccube\Entity\Order;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\OrderRepository;
use Eccube\Repository\ProductRepository;
use Eccube\Service\CartService;
use Eccube\Tests\Web\AbstractShoppingControllerTestCase;
use Plugin\Coupon\Entity\Coupon;
use Plugin\Coupon\Entity\CouponDetail;
use Plugin\Coupon\Repository\CouponOrderRepository;
use Plugin\Coupon\Repository\CouponRepository;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class CouponControllerTest.
 */
class CouponControllerTest extends AbstractShoppingControllerTestCase
{
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
        $this->couponRepository = $this->container->get(CouponRepository::class);
        $this->productRepository = $this->container->get(ProductRepository::class);
        $this->cartService = $this->container->get(CartService::class);
        $this->Customer = $this->createCustomer();
        $this->baseInfoRepository = $this->container->get(BaseInfoRepository::class);
        $this->couponOrderRepository = $this->container->get(CouponOrderRepository::class);
        $this->orderRepository = $this->container->get(OrderRepository::class);
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
        $this->markTestIncomplete('Need to add a coupon_shopping template manually to the shopping page.');

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
        $Order = $this->container->get(OrderRepository::class)->findOneBy(
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
        $this->markTestIncomplete('Need to add a coupon_shopping template manually to the mypage page.');

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
        $crawler = $this->client->request('GET', $this->generateUrl('mypage_history', ['id' => $Order->getId()]));
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
        $Coupon->setCouponLowerLimit(600000);
        // クーポン情報を登録する
        $this->entityManager->persist($Coupon);
        $this->entityManager->flush($Coupon);
        $form = $this->getForm($crawler, $Coupon->getCouponCd());

        /* @var \Symfony\Component\DomCrawler\Crawler $crawler */
        $this->client->submit($form);
        $this->assertFalse($this->client->getResponse()->isRedirection());
    }

    /**
     * testShoppingCouponDiscountType1.
     */
    public function testShoppingCouponDiscountTypePrice()
    {
        $this->markTestIncomplete('Need to add a coupon_shopping template manually to the shopping page.');

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
        $this->markTestIncomplete('Need to add a coupon_shopping template manually to the shopping page.');

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

    /**
     * getCoupon.
     *
     * @param int $couponType
     *
     * @return Coupon
     */
    private function getCoupon($couponType = Coupon::ALL, $discountType = Coupon::DISCOUNT_PRICE)
    {
        /** @var Coupon $Coupon */
        $Coupon = $this->getTestData($couponType, $discountType);

        $Product = $this->createProduct();

        $CouponDetail = new CouponDetail();
        $CouponDetail->setCoupon($Coupon);
        $CouponDetail->setCouponType($Coupon->getCouponType());
        $CouponDetail->setUpdateDate($Coupon->getUpdateDate());
        $CouponDetail->setCreateDate($Coupon->getCreateDate());
        $CouponDetail->setVisible(true);

        switch ($couponType) {
            case Coupon::PRODUCT:
                $CouponDetail->setProduct($Product);
                break;
            case Coupon::CATEGORY:
                $Categories = $Product->getProductCategories();
                /** @var \Eccube\Entity\ProductCategory $Category */
                $ProductCategory = $Categories[0];
                $CouponDetail->setCategory($ProductCategory->getCategory());
                break;
            default:
                break;
        }
        $Coupon->addCouponDetail($CouponDetail);
        $this->entityManager->persist($CouponDetail);
        $this->entityManager->flush($CouponDetail);

        return $Coupon;
    }

    /**
     * getTestData.
     *
     * @param int $couponType
     *
     * @return Coupon
     */
    private function getTestData($couponType = Coupon::ALL, $discountType = Coupon::DISCOUNT_PRICE)
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
        $Coupon->setVisible(true);
        $d1 = $date1->setDate(2016, 1, 1);
        $Coupon->setAvailableFromDate($d1);
        $d2 = $date2->setDate(2040, 12, 31);
        $Coupon->setAvailableToDate($d2);

        // クーポン情報を登録する
        $this->entityManager->persist($Coupon);
        $this->entityManager->flush($Coupon);

        return $Coupon;
    }
}
