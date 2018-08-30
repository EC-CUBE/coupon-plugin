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

namespace Plugin\Coupon\Tests\Web\Admin;

use Eccube\Entity\Customer;
use Eccube\Repository\ProductRepository;
use Eccube\Tests\Web\Admin\AbstractAdminWebTestCase;
use Plugin\Coupon\Entity\Coupon;
use Plugin\Coupon\Entity\CouponDetail;
use Plugin\Coupon\Repository\CouponRepository;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class CouponControllerTest.
 */
class CouponControllerTest extends AbstractAdminWebTestCase
{
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
        $this->couponRepository = $this->container->get(CouponRepository::class);
        $this->productRepository = $this->container->get(ProductRepository::class);
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
        $this->getCoupon();
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

    /**
     * testEnable.
     */
    public function testEnable()
    {
        $Coupon = $this->getTestData();
        $this->client->request('GET', $this->generateUrl('plugin_coupon_enable', ['id' => $Coupon->getId()]));
        $this->assertTrue($this->client->getResponse()->isRedirect($this->generateUrl('plugin_coupon_list')));
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

    /**
     * search with none condition.
     */
    public function testAjaxSearchProductEmpty()
    {
        $this->createProduct('Product A');
        $crawler = $this->client->request(
            'POST',
            $this->generateUrl('plugin_coupon_search_product', ['id' => '', 'category_id' => '', '_token' => 'dummy']),
            [],
            [],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );
        $productList = $crawler->html();
        $this->assertContains('Product A', $productList);
    }

    /**
     * search with none condition.
     */
    public function testAjaxSearchCategoryEmpty()
    {
        $crawler = $this->client->request(
            'POST',
            $this->generateUrl('plugin_coupon_search_category', ['category_id' => '', '_token' => 'dummy']),
            [],
            [],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );
        $categoryList = $crawler->html();
        $this->assertContains('新入荷', $categoryList);
    }

    /**
     * get coupon form.
     *
     * @param Crawler $crawler
     * @param string  $couponCd
     *
     * @return \Symfony\Component\DomCrawler\Form
     */
    private function getForm(Crawler $crawler)
    {
        $current = new \DateTime();
        $form = $crawler->selectButton('登録')->form();
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

    /**
     * getCoupon.
     *
     * @param int $couponType
     *
     * @return Coupon
     */
    private function getCoupon($couponType = Coupon::PRODUCT, $discountType = Coupon::DISCOUNT_PRICE)
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

        return $Coupon;
    }

    /**
     * getTestData.
     *
     * @param int $couponType
     *
     * @return Coupon
     */
    private function getTestData($couponType = Coupon::PRODUCT, $discountType = Coupon::DISCOUNT_PRICE)
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
