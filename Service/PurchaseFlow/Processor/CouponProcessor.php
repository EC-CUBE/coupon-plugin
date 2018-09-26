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

namespace Plugin\Coupon4\Service\PurchaseFlow\Processor;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Eccube\Annotation\ShoppingFlow;
use Eccube\Entity\Order;
use Eccube\Entity\OrderItem;
use Eccube\Entity\ItemHolderInterface;
use Eccube\Entity\Master\OrderItemType;
use Eccube\Entity\Master\TaxDisplayType;
use Eccube\Entity\Master\TaxType;
use Eccube\Repository\TaxRuleRepository;
use Eccube\Service\PurchaseFlow\ItemHolderPreprocessor;
use Eccube\Service\PurchaseFlow\ItemHolderValidator;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseProcessor;
use Eccube\Service\TaxRuleService;
use Plugin\Coupon4\Entity\Coupon;
use Plugin\Coupon4\Entity\CouponOrder;
use Plugin\Coupon4\Service\CouponService;
use Plugin\Coupon4\Repository\CouponRepository;
use Plugin\Coupon4\Repository\CouponOrderRepository;

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
     * @var TaxRuleService
     */
    protected $taxRuleService;

    /**
     * @var TaxRuleRepository
     */
    protected $taxRuleRepository;

    /**
     * CouponProcessor constructor.
     *
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        CouponService $couponService,
        CouponRepository $couponRepository,
        CouponOrderRepository $couponOrderRepository,
        TaxRuleService $taxRuleService,
        TaxRuleRepository $taxRuleRepository
    ) {
        $this->entityManager = $entityManager;
        $this->couponService = $couponService;
        $this->couponRepository = $couponRepository;
        $this->couponOrderRepository = $couponOrderRepository;
        $this->taxRuleService = $taxRuleService;
        $this->taxRuleRepository = $taxRuleRepository;
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
            $this->addCouponDiscountItem($itemHolder, $CouponOrder);
        }
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
     * 値引額指定の場合は、税込小計から設定した金額を引く。税区分は不課税(ポイントと同じ)
     * 税込1080円の商品に、1000円のクーポンを使用すると、80円の支払いになるイメージ
     *
     * 値引率指定の場合は、対象の明細ごとに税込の値引額を算出し、税込の明細として差し引く(税区分は課税)
     * 税込1080円の商品に、10%OFFのクーポンを使用すると、108円の値引きになり、972円の支払いになるイメージ
     *
     * @param ItemHolderInterface $itemHolder
     * @param CouponOrder $CouponOrder
     * @param integer $discount
     */
    private function addCouponDiscountItem(ItemHolderInterface $itemHolder, CouponOrder $CouponOrder)
    {
        $Coupon = $this->couponRepository->find($CouponOrder->getCouponId());

        $taxDisplayType = TaxDisplayType::EXCLUDED; // 税抜
        $taxType = TaxType::NON_TAXABLE; // 不課税
        $tax = 0;
        $taxRate = 0;
        $taxRuleId = null;
        $roundingType = null;
        if ($Coupon->getDiscountType() == Coupon::DISCOUNT_RATE) {
            $taxDisplayType = TaxDisplayType::INCLUDED; // 税込
            $taxType = TaxType::TAXATION; // 課税
            $TaxRule = $this->taxRuleRepository->getByRule(); // XXX 軽減税率に対応していない
            $taxRuleId = $TaxRule->getId();
            $taxRate = $TaxRule->getTaxRate();
            $tax = $CouponOrder->getDiscount() - ($CouponOrder->getDiscount() / 1 + ($taxRate / 100));
            $roundingType = $TaxRule->getRoundingType();
        }
        $DiscountType = $this->entityManager->find(OrderItemType::class, OrderItemType::DISCOUNT);
        $TaxInclude = $this->entityManager->find(TaxDisplayType::class, $taxDisplayType); // FIXME ポイント種別によって変更する
        $Taxation = $this->entityManager->find(TaxType::class, $taxType);

        // TODO TaxProcessorが先行して実行されるため, 税額等の値は個別にセットする.
        $OrderItem = new OrderItem();
        $OrderItem->setProductName($CouponOrder->getCouponName())
            ->setPrice($CouponOrder->getDiscount() * -1)
            ->setQuantity(1)
            ->setTax($tax)
            ->setTaxRate($taxRate)
            ->setTaxRuleId($taxRuleId)
            ->setRoundingType($roundingType)
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
