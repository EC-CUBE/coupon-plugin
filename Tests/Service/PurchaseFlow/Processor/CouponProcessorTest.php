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

namespace Plugin\Coupon4\Tests\Service\PurchaseFlow\Processor;

use Eccube\Entity\Cart;
use Eccube\Entity\Customer;
use Eccube\Entity\ItemHolderInterface;
use Eccube\Entity\Order;
use Eccube\Entity\OrderItem;
use Eccube\Repository\TaxRuleRepository;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\TaxRuleService;
use Eccube\Tests\EccubeTestCase;
use Plugin\Coupon4\Entity\CouponOrder;
use Plugin\Coupon4\Repository\CouponOrderRepository;
use Plugin\Coupon4\Repository\CouponRepository;
use Plugin\Coupon4\Service\CouponService;
use Plugin\Coupon4\Service\PurchaseFlow\Processor\CouponProcessor;
use Plugin\Coupon4\Tests\Fixtures\CreateCouponTrait;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * CouponProcessorTest
 */
class CouponProcessorTest extends EccubeTestCase
{
    use CreateCouponTrait;

    /**
     * @var CouponProcessor
     */
    protected $processor;

    /**
     * @var CouponService
     */
    protected $couponService;

    /**
     * @var CouponOrderRepository
     */
    protected $couponOrderRepository;

    /**
     * @var CouponRepository
     */
    protected $couponRepository;

    /**
     * @var TaxRuleService
     */
    protected $taxRuleService;

    /**
     * @var TaxRuleRepository
     */
    protected $taxRuleRepository;

    /**
     * @var Customer
     */
    protected $Customer;

    /**
     * @var Order
     */
    protected $Order;

    /**
     * @var PurchaseContext
     */
    protected $context;

    public function setUp()
    {
        parent::setUp();
        $this->couponService = $this->container->get(CouponService::class);
        $this->couponRepository = $this->container->get(CouponRepository::class);
        $this->couponOrderRepository = $this->container->get(CouponOrderRepository::class);
        $this->taxRuleService = $this->container->get(TaxRuleService::class);
        $this->taxRuleRepository = $this->container->get(TaxRuleRepository::class);

        $this->processor = new CouponProcessor(
            $this->entityManager,
            $this->couponService,
            $this->couponRepository,
            $this->couponOrderRepository,
            $this->taxRuleService,
            $this->taxRuleRepository
        );

        $this->Customer = $this->createCustomer();
        $this->Order = $this->createOrder($this->Customer);
        $this->context = new PurchaseContext($this->Order, $this->Customer);
    }

    public function testGetInstance()
    {
        $this->assertInstanceOf(CouponProcessor::class, $this->processor);
    }

    public function testSupportWithCart()
    {
        $this->assertFalse($this->wrapperOfSupports($this->processor, new Cart));
    }

    public function testSupportWithOrder()
    {
        $this->assertTrue($this->wrapperOfSupports($this->processor, $this->Order));
    }

    public function testAddCouponDiscountItem()
    {
        $Coupon = $this->getCoupon();
        $this->container->get('security.token_storage')->setToken(
            new UsernamePasswordToken(
                $this->Customer, null, 'customer', $this->Customer->getRoles()
            )
        );
        $this->couponService->saveCouponOrder($this->Order, $Coupon, $Coupon->getCouponCd(), $this->Customer, 1000);
        /** @var CouponOrder $CouponOrder */
        $CouponOrder = $this->couponOrderRepository->findOneBy(['order_id' => $this->Order->getId()]);

        $this->wrapperOfAddCouponDiscountItem($this->processor, $this->Order, $CouponOrder);

        $OrderItems = $this->Order->getItems()->filter(function (OrderItem $OrderItem) {
            return $OrderItem->getProcessorName() === CouponProcessor::class;
        });

        $this->assertCount(1, $OrderItems->toArray());

        /** @var OrderItem */
        $OrderItem = $OrderItems->first();
        $this->assertEquals(-1000, $OrderItem->getPrice());
        $this->assertEquals($Coupon->getCouponName(), $OrderItem->getProductName());
    }

    private function wrapperOfSupports(CouponProcessor $instance, ItemHolderInterface $itemHolder)
    {
        $refMethod = new \ReflectionMethod(CouponProcessor::class, 'supports');
        $refMethod->setAccessible(true);
        return $refMethod->invoke($instance, $itemHolder);
    }

    private function wrapperOfAddCouponDiscountItem(CouponProcessor $instance, ItemHolderInterface $itemHolder, CouponOrder $CouponOrder)
    {
        $refMethod = new \ReflectionMethod(CouponProcessor::class, 'addCouponDiscountItem');
        $refMethod->setAccessible(true);
        return $refMethod->invoke($instance, $itemHolder, $CouponOrder);
    }
}
