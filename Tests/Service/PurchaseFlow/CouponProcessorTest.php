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

namespace Plugin\Coupon4\Tests\Service;

use Eccube\Entity\Master\TaxType;
use Eccube\Entity\Order;
use Eccube\Repository\TaxRuleRepository;
use Eccube\Request\Context;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\TaxRuleService;
use Eccube\Tests\EccubeTestCase;
use phpDocumentor\Reflection\Types\Parent_;
use Plugin\Coupon4\Entity\CouponOrder;
use Plugin\Coupon4\Repository\CouponOrderRepository;
use Plugin\Coupon4\Repository\CouponRepository;
use Plugin\Coupon4\Service\CouponService;
use Plugin\Coupon4\Service\PurchaseFlow\Processor\CouponProcessor;

/**
 * Class CouponServiceTest.
 */
class CouponProcessorTest extends EccubeTestCase
{
    /**
     * @var CouponProcessor
     */
    protected $processor;

    public function setup()
    {
        parent::setUp();

        $couponService = $this->container->get(CouponService::class);
        $couponRepository = $this->container->get(CouponRepository::class);
        $couponOrderRepository = $this->container->get(CouponOrderRepository::class);
        $taxRuleService = $this->container->get(TaxRuleService::class);
        $taxRuleRepository = $this->container->get(TaxRuleRepository::class);
        $this->processor = new CouponProcessor($this->entityManager, $couponService, $couponRepository,
            $couponOrderRepository, $taxRuleService, $taxRuleRepository);
    }

    public function testProcess()
    {
        $CouponOrder = new CouponOrder();
        $CouponOrder->setCouponId(1);
        $CouponOrder->setOrderId(1);
        $CouponOrder->setVisible(true);
        $CouponOrder->setOrderChangeStatus(false);
        $CouponOrder->setPreOrderId('pre_order_id');
        $CouponOrder->setCouponName('クーポン名');
        $CouponOrder->setDiscount(100);
        $this->entityManager->persist($CouponOrder);
        $this->entityManager->flush($CouponOrder);

        $Order = new Order();
        $Order->setPreOrderId($CouponOrder->getPreOrderId());
        $this->processor->process($Order, new PurchaseContext());

        self::assertCount(1, $Order->getOrderItems());

        $DiscountItem = $Order->getOrderItems()[0];
        self::assertTrue($DiscountItem->isDiscount());

        // 不課税で明細が追加される
        self::assertSame(TaxType::NON_TAXABLE, $DiscountItem->getTaxType()->getId());
    }
}
