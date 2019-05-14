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

namespace Plugin\Coupon4\Tests\Web\Admin;

use Eccube\Entity\Customer;
use Eccube\Repository\ProductRepository;
use Eccube\Tests\Web\Admin\AbstractAdminWebTestCase;
use Plugin\Coupon4\Entity\Coupon;
use Plugin\Coupon4\Entity\CouponDetail;
use Plugin\Coupon4\Entity\CouponOrder;
use Plugin\Coupon4\Repository\CouponRepository;
use Plugin\Coupon4\Service\CouponService;
use Plugin\Coupon4\Tests\Fixtures\CreateCouponTrait;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class CouponControllerTest.
 */
class OrderControllerTest extends AbstractAdminWebTestCase
{
    use CreateCouponTrait;

    /** @var CouponService */
    protected $couponService;

    public function setUp()
    {
        parent::setUp();
        $this->couponService = $this->container->get(CouponService::class);
    }

    public function testOrderEdit()
    {
        $Coupon = $this->getCoupon();
        $Customer = $this->createCustomer();
        $Order = $this->createOrder($Customer);

        $discount = $this->couponService->recalcOrder($Coupon, $Order->getProductOrderItems());
        
        $CouponOrder = new CouponOrder();
        $CouponOrder->setCouponId($Coupon->getId())
            ->setCouponCd($Coupon->getCouponCd())
            ->setCouponName($Coupon->getCouponName())
            ->setUserId($Customer->getId())
            ->setPreOrderId($Order->getPreOrderId())
            ->setOrderDate($Order->getOrderDate())
            ->setDiscount($discount)
            ->setOrderId($Order->getId())
            ->setVisible(true)
            ->setOrderChangeStatus(false);

        $this->entityManager->persist($CouponOrder);
        $this->entityManager->flush($CouponOrder);

        $crawler = $this->client->request('GET', $this->generateUrl('admin_order_edit', ['id' => $Order->getId()]));

        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertContains($Coupon->getCouponCd(), $crawler->html());
    }

    public function testOrderEditWithNotCoupon()
    {
        $Coupon = $this->getCoupon();
        $Customer = $this->createCustomer();
        $Order = $this->createOrder($Customer);

        $crawler = $this->client->request('GET', $this->generateUrl('admin_order_edit', ['id' => $Order->getId()]));

        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertNotContains($Coupon->getCouponCd(), $crawler->html());
    }
}
