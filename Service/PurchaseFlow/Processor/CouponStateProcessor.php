<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * https://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\Coupon44\Service\PurchaseFlow\Processor;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Attribute\OrderFlow;
use Eccube\Entity\ItemHolderInterface;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Order;
use Eccube\Service\PurchaseFlow\ItemHolderPreprocessor;
use Eccube\Service\PurchaseFlow\ItemHolderValidator;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseProcessor;
use Plugin\Coupon44\Repository\CouponOrderRepository;
use Plugin\Coupon44\Repository\CouponRepository;
use Plugin\Coupon44\Service\CouponService;

/**
 * クーポンの状態を制御する.
 *
 * TODO Event::onOrderEditComplete を移植する
 */
#[OrderFlow]
class CouponStateProcessor extends ItemHolderValidator implements ItemHolderPreprocessor, PurchaseProcessor
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

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
     * CouponStateProcessor constructor.
     *
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        CouponService $couponService,
        CouponRepository $couponRepository,
        CouponOrderRepository $couponOrderRepository,
    ) {
        $this->entityManager = $entityManager;
        $this->couponService = $couponService;
        $this->couponRepository = $couponRepository;
        $this->couponOrderRepository = $couponOrderRepository;
    }

    /*
     * ItemHolderPreprocessor
     */

    /**
     * クーポン利用の場合は明細を追加する.
     * {@inheritdoc}
     */
    public function process(ItemHolderInterface $itemHolder, PurchaseContext $context): void
    {
        if (!$this->supports($itemHolder)) {
            return;
        }
        assert($itemHolder instanceof Order);
        $CouponOrder = $this->couponOrderRepository->getCouponOrder($itemHolder->getPreOrderId());
        if (!$CouponOrder) {
            return;
        }

        $orderStatusId = $itemHolder->getOrderStatus()->getId();
        $isChanged = $CouponOrder->getOrderChangeStatus(); // 変更されたかどうか？
        if ($orderStatusId == OrderStatus::CANCEL
            || $orderStatusId == OrderStatus::PROCESSING
            || $orderStatusId == OrderStatus::RETURNED) { // TODO Order が取得できない場合も考慮する？
            if (!$isChanged) {
                $Coupon = $this->couponRepository->find($CouponOrder->getCouponId());
                if ($Coupon) {
                    $CouponOrder->setOrderDate(null);
                    $CouponOrder->setOrderChangeStatus(true);
                    $this->couponOrderRepository->save($CouponOrder);
                    $couponUseTime = $Coupon->getCouponUseTime() + 1;
                    $couponRelease = $Coupon->getCouponRelease();
                    if ($couponUseTime <= $couponRelease) {
                        $Coupon->setCouponUseTime($couponUseTime);
                    } else {
                        $Coupon->setCouponUseTime($couponRelease);
                    }
                    $this->entityManager->persist($Coupon);
                    $this->entityManager->flush();
                }
            }
        }
        if ($orderStatusId != OrderStatus::CANCEL
            && $orderStatusId != OrderStatus::PROCESSING
            && $orderStatusId != OrderStatus::RETURNED) {
            if ($isChanged) {
                $Coupon = $this->couponRepository->find($CouponOrder->getCouponId());
                if ($Coupon) {
                    $CouponOrder->setOrderDate(new \DateTime());
                    $CouponOrder->setOrderChangeStatus(false);
                    $this->couponOrderRepository->save($CouponOrder);
                    $Coupon->setCouponUseTime($Coupon->getCouponUseTime() - 1);
                    $this->entityManager->persist($Coupon);
                    $this->entityManager->flush();
                }
            }
        }
    }

    /*
     * ItemHolderValidator
     */

    /**
     * クーポン利用可否判定.
     * {@inheritdoc}
     */
    protected function validate(ItemHolderInterface $itemHolder, PurchaseContext $context): void
    {
        if (!$this->supports($itemHolder)) {
            return;
        }
    }

    /*
     * PurchaseProcessor
     */

    /**
     * {@inheritdoc}
     */
    public function prepare(ItemHolderInterface $itemHolder, PurchaseContext $context): void
    {
        if (!$this->supports($itemHolder)) {
            return;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function commit(ItemHolderInterface $target, PurchaseContext $context): void
    {
        // quiet.
    }

    /**
     * クーポンを取り消す.
     * {@inheritdoc}
     */
    public function rollback(ItemHolderInterface $itemHolder, PurchaseContext $context): void
    {
        // 利用したポイントをユーザに戻す.
        if (!$this->supports($itemHolder)) {
            return;
        }

        $this->couponService->removeCouponOrder($itemHolder);
    }

    /**
     * Processorが実行出来るかどうかを返す.
     *
     * 以下を満たす場合に実行できる.
     *
     * - ポイント設定が有効であること.
     * - $itemHolderがOrderエンティティであること.
     * - 会員のOrderであること.
     *
     * @param ItemHolderInterface $itemHolder
     *
     * @return bool
     */
    private function supports(ItemHolderInterface $itemHolder): bool
    {
        if (!$itemHolder instanceof Order) {
            return false;
        }

        switch ($itemHolder->getOrderStatus()->getId()) {
            case OrderStatus::NEW:
            case OrderStatus::PAID:
            case OrderStatus::IN_PROGRESS:
            case OrderStatus::DELIVERED:
            case OrderStatus::CANCEL:
            case OrderStatus::RETURNED:
                break;
            default:
                return false;
        }
        $CouponOrder = $this->couponOrderRepository->getCouponOrder($itemHolder->getPreOrderId());
        if (!$CouponOrder) {
            return false;
        }

        return true;
    }
}
