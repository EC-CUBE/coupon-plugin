<?php

namespace Plugin\Coupon\Tests\Web;

use Eccube\Common\Constant;
use Eccube\Tests\Web\AbstractWebTestCase;
use Plugin\Coupon\Entity\CouponCoupon;
use Plugin\Coupon\Entity\CouponCouponDetail;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class CouponControllerTest
 *
 * @package Plugin\Coupon\Tests\Web
 */
class CouponControllerTest extends AbstractWebTestCase
{

    protected $Customer;

    public function setUp()
    {
        parent::setUp();
        $this->Customer = $this->createCustomer();
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    public function testRoutingShoppingCoupon()
    {

        $this->routingShopping();

        $crawler = $this->client->request('GET', $this->app->url('plugin_shopping_coupon'));

        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $this->expected = 'クーポンコードの入力';
        $this->actual = $crawler->filter('h1.page-heading')->text();

        $this->verify();


    }

    public function testRoutingShoppingCouponNot()
    {
        $client = $this->client;
        $client->request('GET', $this->app->url('plugin_shopping_coupon'));
        $this->assertFalse($client->getResponse()->isSuccessful());
    }

    public function testShoppingCouponPost()
    {

        $this->routingShopping();

        $crawler = $this->client->request('GET', $this->app->url('plugin_shopping_coupon'));

        $form = $this->getForm($crawler);

        /** @var \Symfony\Component\DomCrawler\Crawler $crawler */
        $crawler = $this->client->submit($form);

        $this->assertTrue($this->client->getResponse()->isRedirection());

    }

    public function testShoppingCouponPostError()
    {

        $this->routingShopping();

        $crawler = $this->client->request('GET', $this->app->url('plugin_shopping_coupon'));

        $form = $this->getForm($crawler, 'aaaa');

        /** @var \Symfony\Component\DomCrawler\Crawler $crawler */
        $crawler = $this->client->submit($form);

        // 存在しないクーポンコードで検索したためエラーになるためリダイレクトはされない
        $this->assertFalse($this->client->getResponse()->isRedirection());
    }


    public function testShoppingCoupon()
    {

        $this->routingShopping();

        $crawler = $this->client->request('GET', $this->app->url('plugin_shopping_coupon'));

        $Coupon = $this->getCoupon();

        $form = $this->getForm($crawler, $Coupon->getCouponCd());

        /** @var \Symfony\Component\DomCrawler\Crawler $crawler */
        $crawler = $this->client->submit($form);

        $this->assertTrue($this->client->getResponse()->isRedirection());

        $crawler = $this->client->request('GET', $this->app->url('shopping'));

        $this->expected = 'クーポンを利用しています。';
        $this->actual = $crawler->filter('strong.text-danger')->text();

        $this->verify();

    }


    public function testShoppingCouponDiscountType1()
    {

        $this->routingShopping();

        $crawler = $this->client->request('GET', $this->app->url('plugin_shopping_coupon'));

        $Coupon = $this->getCoupon(1, 1);

        $form = $this->getForm($crawler, $Coupon->getCouponCd());

        /** @var \Symfony\Component\DomCrawler\Crawler $crawler */
        $crawler = $this->client->submit($form);

        $this->assertTrue($this->client->getResponse()->isRedirection());

        $crawler = $this->client->request('GET', $this->app->url('shopping'));

        $Order = $this->app['eccube.repository.order']->findOneBy(
            array(
                'Customer' => $this->Customer
            )
        );

        $this->actual = $Coupon->getDiscountPrice();

        $this->expected = $Order->getDiscount();

        $this->verify();

    }

    public function testShoppingCouponDiscountType2()
    {

        $this->routingShopping();

        $crawler = $this->client->request('GET', $this->app->url('plugin_shopping_coupon'));

        $Coupon = $this->getCoupon(1, 2);

        $form = $this->getForm($crawler, $Coupon->getCouponCd());

        /** @var \Symfony\Component\DomCrawler\Crawler $crawler */
        $crawler = $this->client->submit($form);

        $this->assertTrue($this->client->getResponse()->isRedirection());

        $crawler = $this->client->request('GET', $this->app->url('shopping'));

        $Order = $this->app['eccube.repository.order']->findOneBy(
            array(
                'Customer' => $this->Customer
            )
        );

        $CouponOrder = $this->app['eccube.plugin.coupon.repository.coupon_order']->findOneBy(array('pre_order_id' =>$Order->getPreOrderId()));

        $this->actual = $CouponOrder->getDiscount();

        $this->expected = $Order->getDiscount();

        $this->verify();

    }


    private function routingShopping()
    {
        // カート画面
        $this->client->request('POST', '/cart/add', array('product_class_id' => 1));
        $this->app['eccube.service.cart']->lock();

        $this->Customer = $this->logIn();

        $this->client->request('GET', $this->app->url('shopping'));

    }


    private function getForm(Crawler $crawler, $couponCd = '')
    {

        $form = $crawler->selectButton('登録する')->form();

        $form['shopping_coupon[coupon_cd]'] = $couponCd;
        $form['shopping_coupon[_token]'] = 'dummy';

        return $form;

    }






    private function getCoupon($couponType = 1, $discountType = 1)
    {
        $data = $this->getTestData($couponType, $discountType);

        $this->app['eccube.plugin.coupon.service.coupon']->createCoupon($data);

        /** @var \Plugin\Coupon\Entity\CouponCoupon $Coupon */
        $Coupon = $this->app['eccube.plugin.coupon.repository.coupon']->findOneBy(array('coupon_cd' => 'aaaaaaaa'));


        $Product = $this->app['eccube.repository.product']->find(1);

        $CouponDetail = new CouponCouponDetail();

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


    private function getTestData($couponType = 1, $discountType = 1)
    {

        $Coupon = new CouponCoupon();

        $date1 = new \DateTime();
        $date2 = new \DateTime();

        $Coupon->setCouponCd('aaaaaaaa');
        $Coupon->setCouponType($couponType);
        $Coupon->setCouponName('クーポン');
        $Coupon->setDiscountType($discountType);
        $Coupon->setCouponUseTime(1);
        $Coupon->setDiscountPrice(100);
        $Coupon->setDiscountRate(10);
        $Coupon->setEnableFlag(1);
        $d1 = $date1->setDate(2016, 1, 1);
        $Coupon->setAvailableFromDate($d1);
        $d2 = $date2->setDate(2016, 12, 31);
        $Coupon->setAvailableToDate($d2);

        return $Coupon;

    }

}