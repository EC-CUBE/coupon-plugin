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

use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Order;
use Eccube\Repository\OrderRepository;
use Eccube\Service\OrderStateMachine;
use Eccube\Tests\Web\Admin\Order\AbstractEditControllerTestCase;
use Plugin\Coupon4\Entity\CouponOrder;
use Plugin\Coupon4\Service\CouponService;
use Plugin\Coupon4\Tests\Fixtures\CreateCouponTrait;

/**
 * Class CouponControllerTest.
 */
class OrderControllerTest extends AbstractEditControllerTestCase
{
    use CreateCouponTrait;

    /** @var CouponService */
    protected $couponService;

    /** @var OrderStateMachine */
    protected $stateMachine;

    /** @var OrderRepository */
    protected $orderRepository;

    public function setUp()
    {
        parent::setUp();
        $this->couponService = self::$container->get(CouponService::class);
        $this->stateMachine = self::$container->get(OrderStateMachine::class);
        $this->orderRepository = $this->entityManager->getRepository(Order::class);
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

    public function testOrderEditWithDisableCoupon()
    {
        $Coupon = $this->getCoupon();
        $Coupon->setVisible(false);
        $this->entityManager->flush($Coupon);

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
        $this->assertContains($Coupon->getCouponCd(), $crawler->html(), 'クーポンが無効でも表示は変わらない');
    }

    public function testOrderEditWithDisableOrderCoupon()
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
            ->setVisible(false)
            ->setOrderChangeStatus(false);

        $this->entityManager->persist($CouponOrder);
        $this->entityManager->flush($CouponOrder);

        $crawler = $this->client->request('GET', $this->generateUrl('admin_order_edit', ['id' => $Order->getId()]));

        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertContains($Coupon->getCouponCd(), $crawler->html(), '受注クーポンが無効でも表示は変わらない');
    }

    public function testOrderEditWithDeleteCoupon()
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
            ->setVisible(false)
            ->setOrderChangeStatus(false);

        $this->entityManager->persist($CouponOrder);
        $this->entityManager->flush($CouponOrder);

        $this->entityManager->remove($Coupon);
        $this->entityManager->flush($Coupon);

        $crawler = $this->client->request('GET', $this->generateUrl('admin_order_edit', ['id' => $Order->getId()]));

        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertContains($Coupon->getCouponCd(), $crawler->html(), 'クーポンが削除されても表示は変わらない');
    }

    /**
     * キャンセルの場合のクーポン表示
     */
    public function testOrderEditWithCouponCancel()
    {
        $Coupon = $this->getCoupon();
        $Customer = $this->createCustomer();
        $Order = $this->createOrder($Customer);
        $Order->setOrderStatus($this->entityManager->find(OrderStatus::class, OrderStatus::NEW));
        $this->entityManager->flush($Order);

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

        $Product = $this->createProduct();

        $formData = $this->createFormData($Customer, $Product);

        $crawler = $this->client->request(
            'POST',
            $this->generateUrl('admin_order_edit', ['id' => $Order->getId()]),
            [
                'order' => $formData,
                'mode' => 'register',
            ]
        );
        $this->assertTrue($this->client->getResponse()->isRedirect($this->generateUrl('admin_order_edit', ['id' => $Order->getId()])));

        $EditedOrder = $this->orderRepository->find($Order->getId());
        $Order->setOrderStatus($this->entityManager->find(OrderStatus::class, OrderStatus::CANCEL));

        $formDataForEdit = $this->createFormDataForEdit($EditedOrder);

        // 管理画面で受注編集する
        $this->client->request(
            'POST', $this->generateUrl('admin_order_edit', ['id' => $Order->getId()]), [
            'order' => $formDataForEdit,
            'mode' => 'register',
            ]
        );
        $this->assertTrue($this->client->getResponse()->isRedirect($this->generateUrl('admin_order_edit', ['id' => $Order->getId()])));

        /** @var Order $EditedOrderafterEdit */
        $EditedOrderafterEdit = $this->orderRepository->find($Order->getId());

        $this->expected = OrderStatus::CANCEL;
        $this->actual = $EditedOrderafterEdit->getOrderStatus()->getId();
        $this->verify();

        $crawler = $this->client->followRedirect();
        $this->assertContains('クーポンが適用されていません', $crawler->html(), 'クーポンが適用されていないメッセージ表示');
    }

    /**
     * 返品の場合のクーポン表示
     */
    public function testOrderEditWithCouponReturn()
    {
        $Coupon = $this->getCoupon();
        $Customer = $this->createCustomer();
        $Order = $this->createOrder($Customer);
        $Order->setOrderStatus($this->entityManager->find(OrderStatus::class, OrderStatus::DELIVERED));
        $this->entityManager->flush($Order);

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
            ->setVisible(false)
            ->setOrderChangeStatus(false);

        $this->entityManager->persist($CouponOrder);
        $this->entityManager->flush($CouponOrder);

        $Product = $this->createProduct();

        $formData = $this->createFormData($Customer, $Product);
        $formData['OrderStatus'] = OrderStatus::DELIVERED;

        $crawler = $this->client->request(
            'POST',
            $this->generateUrl('admin_order_edit', ['id' => $Order->getId()]),
            [
                'order' => $formData,
                'mode' => 'register',
            ]
        );
        $this->assertTrue($this->client->getResponse()->isRedirect($this->generateUrl('admin_order_edit', ['id' => $Order->getId()])));

        $EditedOrder = $this->orderRepository->find($Order->getId());
        $Order->setOrderStatus($this->entityManager->find(OrderStatus::class, OrderStatus::RETURNED));

        $formDataForEdit = $this->createFormDataForEdit($EditedOrder);

        // 管理画面で受注編集する
        $this->client->request(
            'POST', $this->generateUrl('admin_order_edit', ['id' => $Order->getId()]), [
            'order' => $formDataForEdit,
            'mode' => 'register',
            ]
        );
        $this->assertTrue($this->client->getResponse()->isRedirect($this->generateUrl('admin_order_edit', ['id' => $Order->getId()])));

        /** @var Order $EditedOrderafterEdit */
        $EditedOrderafterEdit = $this->orderRepository->find($Order->getId());

        $this->expected = OrderStatus::RETURNED;
        $this->actual = $EditedOrderafterEdit->getOrderStatus()->getId();
        $this->verify();

        $crawler = $this->client->followRedirect();
        $this->assertContains('クーポンが適用されていません', $crawler->html(), 'クーポンが適用されていないメッセージ表示');
    }
}
