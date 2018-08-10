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

namespace Plugin\Coupon\Service\PurchaseFlow\Processor;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Eccube\Annotation\ShoppingFlow;
use Eccube\Entity\Order;
use Eccube\Entity\OrderItem;
use Eccube\Entity\ItemInterface;
use Eccube\Entity\ItemHolderInterface;
use Eccube\Entity\Master\OrderItemType;
use Eccube\Entity\Master\TaxDisplayType;
use Eccube\Entity\Master\TaxType;
use Eccube\Entity\Master\RoundingType;
use Eccube\Service\PurchaseFlow\InvalidItemException;
use Eccube\Service\PurchaseFlow\ItemValidator;
use Eccube\Service\PurchaseFlow\ItemHolderPreprocessor;
use Eccube\Service\PurchaseFlow\ItemHolderValidator;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseProcessor;
use Plugin\Coupon\Entity\CouponOrder;
use Plugin\Coupon\Service\CouponService;
use Plugin\Coupon\Repository\CouponRepository;
use Plugin\Coupon\Repository\CouponOrderRepository;

/**
 * クーポンを追加する.
 *
 * @ShoppingFlow
 */
class CouponProcessor extends ItemHolderValidator implements ItemHolderPreprocessor, PurchaseProcessor
{
    /**
     * @var EntityManager
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
     * CouponProcessor constructor.
     *
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        CouponService $couponService,
        CouponRepository $couponRepository,
        CouponOrderRepository $couponOrderRepository
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
    public function process(ItemHolderInterface $itemHolder, PurchaseContext $context)
  {
        if (!$this->supports($itemHolder)) {
            return;
        }
        $CouponOrder = $this->couponOrderRepository->getCouponOrder($itemHolder->getPreOrderId());

        // 既存のクーポンを削除し明細追加
        if ($CouponOrder) {
            $this->removeCouponDiscountItem($itemHolder, $CouponOrder);
            $this->addPointDiscountItem($itemHolder, $CouponOrder);
        }
        // // 利用ポイントがある場合は割引明細を追加
        // if ($itemHolder->getUsePoint() > 0) {
        //     $discount = $this->pointToPrice($itemHolder->getUsePoint());
        //     $this->addPointDiscountItem($itemHolder, $discount);
        // }

        // // 付与ポイントを計算
        // $addPoint = $this->calculateAddPoint($itemHolder);
        // $itemHolder->setAddPoint($addPoint);
    }

    /*
     * ItemHolderValidator
     */

    /**
     * クーポン利用可否判定.
     * {@inheritdoc}
     */
    protected function validate(ItemHolderInterface $itemHolder, PurchaseContext $context)
    {
        if (!$this->supports($itemHolder)) {
            return;
        }
        $CouponOrder = $this->couponOrderRepository->getCouponOrder($itemHolder->getPreOrderId());

        // クーポンとポイント併用不可
        $usePoint = $itemHolder->getUsePoint();
        if ($usePoint > 0 && $CouponOrder && $CouponOrder->isVisible()) {
            $this->couponService->removeCouponOrder($itemHolder);
            $this->throwInvalidItemException(trans('plugin_coupon.front.shopping.conflictpoint'));
        }


        // // 所有ポイント < 利用ポイント
        // $Customer = $itemHolder->getCustomer();
        // if ($Customer->getPoint() < $itemHolder->getUsePoint()) {
        //     // 利用ポイントが所有ポイントを上回っていた場合は所有ポイントで上書き
        //     $itemHolder->setUsePoint($Customer->getPoint());
        //     $this->throwInvalidItemException('利用ポイントが所有ポイントを上回っています.');
        // }

        // // 支払い金額 < 利用ポイント
        // if ($itemHolder->getTotal() < 0) {
        //     // 利用ポイントが支払い金額を上回っていた場合は支払い金額が0円以上となるようにポイントを調整
        //     $overPoint = floor($itemHolder->getTotal() / $this->BaseInfo->getPointConversionRate());
        //     $itemHolder->setUsePoint($itemHolder->getUsePoint() + $overPoint);
        //     $this->throwInvalidItemException('利用ポイントがお支払い金額を上回っています.');
        // }
    }

    /*
     * PurchaseProcessor
     */

    /**
     * クーポンを使用状態にする.
     * {@inheritdoc}
     */
    public function prepare(ItemHolderInterface $itemHolder, PurchaseContext $context)
    {
        if (!$this->supports($itemHolder)) {
            return;
        }

        $CouponOrder = $this->couponOrderRepository->getCouponOrder($itemHolder->getPreOrderId());
        $CouponOrder->setOrderDate(new \DateTime());
        $this->entityManager->flush($CouponOrder);

        $Coupon = $this->couponRepository->findActiveCoupon($CouponOrder->getCouponCd());
        if (!$Coupon) {
            return;
        }
        $Coupon->setCouponUseTime($Coupon->getCouponUseTime() - 1);
        $this->entityManager->flush($Coupon);
    }

    /**
     * {@inheritdoc}
     */
    public function commit(ItemHolderInterface $target, PurchaseContext $context)
    {
        // quiet.
    }

    /**
     * クーポンを取り消す.
     * {@inheritdoc
     */
    public function rollback(ItemHolderInterface $itemHolder, PurchaseContext $context)
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
    private function supports(ItemHolderInterface $itemHolder)
    {
        if (!$itemHolder instanceof Order) {
            return false;
        }

        return true;
    }

    /**
     * 既存のクーポン明細を削除する.
     *
     * @param ItemHolderInterface $itemHolder
     * @param CouponOrder $CouponOrder
     */
    private function removeCouponDiscountItem(ItemHolderInterface $itemHolder, CouponOrder $CouponOrder)
    {
        foreach ($itemHolder->getItems() as $item) {
            if ($CouponOrder->getOrderItemId() === $item->getId()) {
                $itemHolder->removeOrderItem($item);
                $this->entityManager->remove($item);
            }
        }
    }

    /**
     * 明細追加処理.
     *
     * @param ItemHolderInterface $itemHolder
     * @param CouponOrder $CouponOrder
     * @param integer $discount
     */
    private function addPointDiscountItem(ItemHolderInterface $itemHolder, CouponOrder $CouponOrder)
    {
        $DiscountType = $this->entityManager->find(OrderItemType::class, OrderItemType::DISCOUNT);
        $TaxInclude = $this->entityManager->find(TaxDisplayType::class, TaxDisplayType::INCLUDED); // FIXME ポイント種別によって変更する
        $Taxation = $this->entityManager->find(TaxType::class, TaxType::NON_TAXABLE);

        // TODO TaxProcessorが先行して実行されるため, 税額等の値は個別にセットする.
        $OrderItem = new OrderItem();
        $OrderItem->setProductName($CouponOrder->getCouponName())
            ->setPrice($CouponOrder->getDiscount() * -1)
            ->setQuantity(1)
            ->setTax(0)
            ->setTaxRate(0)
            ->setTaxRuleId(null)
            ->setRoundingType(null)
            ->setOrderItemType($DiscountType)
            ->setTaxDisplayType($TaxInclude)
            ->setTaxType($Taxation)
            ->setOrder($itemHolder);
        $itemHolder->addItem($OrderItem);

        $this->entityManager->persist($OrderItem);
        $this->entityManager->flush($OrderItem);

        $CouponOrder->setOrderItemId($OrderItem->getId());
    }
}
