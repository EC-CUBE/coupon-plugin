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

namespace Plugin\Coupon\Service;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Common\Constant;
use Eccube\Entity\Customer;
use Eccube\Entity\Master\OrderItemType;
use Eccube\Entity\Master\TaxDisplayType;
use Eccube\Entity\Master\TaxType;
use Eccube\Entity\Order;
use Eccube\Entity\OrderItem;
use Eccube\Entity\TaxRule;
use Eccube\Repository\CategoryRepository;
use Eccube\Repository\Master\OrderItemTypeRepository;
use Eccube\Repository\Master\TaxDisplayTypeRepository;
use Eccube\Repository\Master\TaxTypeRepository;
use Eccube\Repository\OrderItemRepository;
use Eccube\Repository\TaxRuleRepository;
use Eccube\Service\TaxRuleService;
use Plugin\Coupon\Entity\Coupon;
use Plugin\Coupon\Entity\CouponOrder;
use Eccube\Entity\Category;
use Plugin\Coupon\Repository\CouponOrderRepository;
use Plugin\Coupon\Repository\CouponRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Class CouponService.
 */
class CouponService
{
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var CouponRepository
     */
    private $couponRepository;

    /**
     * @var CouponOrderRepository
     */
    private $couponOrderRepository;

    /**
     * @var CategoryRepository
     */
    private $categoryRepository;

    /**
     * @var TaxRuleService
     */
    private $taxRuleService;

    /**
     * @var TaxRuleRepository
     */
    private $taxRuleRepository;

    /**
     * @var TaxTypeRepository
     */
    private $taxTypeRepository;

    /**
     * @var TaxDisplayTypeRepository
     */
    private $taxDisplayTypeRepository;

    /**
     * @var OrderItemTypeRepository
     */
    private $orderItemTypeRepository;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var OrderItemRepository
     */
    private $orderItemRepository;

    /**
     * CouponService constructor.
     *
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param CouponRepository $couponRepository
     * @param CouponOrderRepository $couponOrderRepository
     * @param CategoryRepository $categoryRepository
     * @param TaxRuleService $taxRuleService
     * @param TaxRuleRepository $taxRuleRepository
     * @param TaxTypeRepository $taxTypeRepository
     * @param TaxDisplayTypeRepository $taxDisplayTypeRepository
     * @param OrderItemTypeRepository $orderItemTypeRepository
     * @param ContainerInterface $container
     * @param EntityManagerInterface $entityManager
     * @param OrderItemRepository $orderItemRepository
     */
    public function __construct(AuthorizationCheckerInterface $authorizationChecker, CouponRepository $couponRepository, CouponOrderRepository $couponOrderRepository, CategoryRepository $categoryRepository, TaxRuleService $taxRuleService, TaxRuleRepository $taxRuleRepository, TaxTypeRepository $taxTypeRepository, TaxDisplayTypeRepository $taxDisplayTypeRepository, OrderItemTypeRepository $orderItemTypeRepository, ContainerInterface $container, EntityManagerInterface $entityManager, OrderItemRepository $orderItemRepository)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->couponRepository = $couponRepository;
        $this->couponOrderRepository = $couponOrderRepository;
        $this->categoryRepository = $categoryRepository;
        $this->taxRuleService = $taxRuleService;
        $this->taxRuleRepository = $taxRuleRepository;
        $this->taxTypeRepository = $taxTypeRepository;
        $this->taxDisplayTypeRepository = $taxDisplayTypeRepository;
        $this->orderItemTypeRepository = $orderItemTypeRepository;
        $this->container = $container;
        $this->entityManager = $entityManager;
        $this->orderItemRepository = $orderItemRepository;
    }

    /**
     * クーポンコードを生成する.
     *
     * @param int $length
     *
     * @return string
     */
    public function generateCouponCd($length = 12)
    {
        $couponCd = substr(base_convert(md5(uniqid()), 16, 36), 0, $length);

        return $couponCd;
    }

    /**
     * 注文にクーポン対象商品が含まれているか確認する.
     *
     * @param Coupon $Coupon
     * @param Order  $Order
     *
     * @return array
     */
    public function existsCouponProduct(Coupon $Coupon, Order $Order)
    {
        $couponProducts = [];
        if (!is_null($Coupon)) {
            // 対象商品の存在確認
            if ($Coupon->getCouponType() == Coupon::PRODUCT) {
                // 商品の場合
                $couponProducts = $this->containsProduct($Coupon, $Order);
            } elseif ($Coupon->getCouponType() == Coupon::CATEGORY) {
                // カテゴリの場合
                $couponProducts = $this->containsCategory($Coupon, $Order);
            } elseif ($Coupon->getCouponType() == Coupon::ALL) {
                // all product
                // 一致する商品IDがあればtrueを返す
                /** @var OrderItem $detail */
                foreach ($Order->getItems()->getProductClasses() as $detail) {
                    $couponProducts = $this->getCouponProducts($detail);
                }
            }
        }

        return $couponProducts;
    }

    /**
     * クーポン受注情報を保存する.
     *
     * @param Order    $Order
     * @param Coupon   $Coupon
     * @param int      $couponCd
     * @param Customer $Customer
     * @param int      $discount
     */
    public function saveCouponOrder(Order $Order, Coupon $Coupon, $couponCd, Customer $Customer, $discount)
    {
        if (is_null($Order)) {
            return;
        }

        $repository = $this->couponOrderRepository;
        // クーポン受注情報を取得する
        /** @var CouponOrder $CouponOrder */
        $CouponOrder = $repository->findOneBy([
            'pre_order_id' => $Order->getPreOrderId(),
        ]);

        if (is_null($CouponOrder)) {
            $currency = $this->container->getParameter('currency');
            $TaxType = $this->taxTypeRepository->find(TaxType::NON_TAXABLE);
            $TaxDisplayType = $this->taxDisplayTypeRepository->find(TaxDisplayType::INCLUDED);
            $OrderItemType = $this->orderItemTypeRepository->find(OrderItemType::DISCOUNT);
            $OrderItem = new OrderItem();
            $OrderItem->setOrder($Order)
                ->setTaxType($TaxType)
                ->setTaxDisplayType($TaxDisplayType)
                ->setOrderItemType($OrderItemType)
                ->setProductName(trans('orderitem.text.data.discount'))
                // todo: currently uses negative numbers
                ->setPrice(0 - $discount)
                ->setQuantity(1)
                ->setTaxRate(0)
                ->setTaxRule(TaxRule::DEFAULT_TAX_RULE_ID)
                ->setCurrencyCode($currency);

            $this->entityManager->persist($OrderItem);
            $this->entityManager->flush($OrderItem);

            // 未登録の場合
            $CouponOrder = new CouponOrder();
            $CouponOrder->setOrderId($Order->getId());
            $CouponOrder->setPreOrderId($Order->getPreOrderId());
            $CouponOrder->setVisible(true);
            $CouponOrder->setOrderItemId($OrderItem->getId());
        } else {
            $orderItemId = $CouponOrder->getOrderItemId();
            /** @var OrderItem $OrderItem */
            $OrderItem = $this->orderItemRepository->find($orderItemId);

            if ($OrderItem) {
                // set negative numbers
                $OrderItem->setPrice(0 - $discount);
            }
            $this->entityManager->persist($OrderItem);
            $this->entityManager->flush($OrderItem);
        }

        // 更新対象データ
        if (is_null($Coupon) || (is_null($couponCd) || strlen($couponCd) == 0)) {
            // クーポンがない または クーポンコードが空の場合
            $CouponOrder->setCouponCd($couponCd);
            $CouponOrder->setCouponId(null);
        } else {
            $CouponOrder->setCouponId($Coupon->getId());
            $CouponOrder->setCouponCd($Coupon->getCouponCd());
        }

        $CouponOrder->setCouponName($Coupon->getCouponName());
        $CouponOrder->setOrderChangeStatus(Constant::DISABLED);
        // ログイン済みの場合は, user_id取得
        if ($this->authorizationChecker->isGranted('ROLE_USER')) {
            $CouponOrder->setUserId($Customer->getId());
        } else {
            $CouponOrder->setEmail($Customer->getEmail());
        }

        // 割引金額をセット
        $CouponOrder->setDiscount($discount);
        $repository->save($CouponOrder);
    }

    /**
     * 合計、値引きを再計算する.
     *
     * @param Coupon $Coupon
     * @param array  $couponProducts
     *
     * @return float|int|string
     *
     * @throws \Doctrine\ORM\NoResultException
     */
    public function recalcOrder(Coupon $Coupon, $couponProducts)
    {
        $discount = 0;
        // クーポンコードが存在する場合カートに入っている商品の値引き額を設定する
        if ($Coupon) {
            // 対象商品の存在確認.
            // 割引対象商品が存在する場合は値引き額を取得する
            // 割引対象商品がある場合は値引き額を計算する
            if ($Coupon->getDiscountType() == Coupon::DISCOUNT_PRICE) {
                $discount = $Coupon->getDiscountPrice();
            } else {
                /** @var TaxRule $TaxRule */
                $TaxRule = $this->taxRuleRepository->getByRule();
                // 値引き前の金額で割引率を算出する
                $total = 0;
                // include tax
                foreach ($couponProducts as $key => $value) {
                    $total += ($value['price'] + $this->taxRuleService->calcTax($value['price'], $value['tax_rate'], $value['tax_rule'])) * $value['quantity'];
                }

                // 小数点以下は四捨五入
                $discount = $this->taxRuleService->calcTax(
                    $total,
                    $Coupon->getDiscountRate(),
                    $TaxRule->getRoundingType()->getId(),
                    $TaxRule->getTaxAdjust()
                );
            }
        }

        return $discount;
    }

    /**
     * check coupon lower limit.
     *
     * @param array $productCoupon
     * @param int   $lowerLimitMoney
     *
     * @return bool
     */
    public function isLowerLimitCoupon($productCoupon, $lowerLimitMoney)
    {
        $subTotal = 0;
        // price inc tax
        foreach ($productCoupon as $key => $value) {
            $subTotal += ($value['price'] + $this->taxRuleService->calcTax($value['price'], $value['tax_rate'], $value['tax_rule'])) * $value['quantity'];
        }

        if ($subTotal < $lowerLimitMoney && $subTotal != 0) {
            return false;
        }

        return true;
    }

    /**
     *  ユーザはクーポン1回のみ利用できる.
     *
     * @param string   $couponCd
     * @param Customer $Customer
     *
     * @return bool
     */
    public function checkCouponUsedOrNot($couponCd, Customer $Customer)
    {
        $repository = $this->couponOrderRepository;

        if ($this->authorizationChecker->isGranted('ROLE_USER')) {
            $result = $repository->findUseCoupon($couponCd, $Customer->getId());
        } else {
            $result = $repository->findUseCoupon($couponCd, $Customer->getEmail());
        }

        if (!$result) {
            return false;
        }

        return true;
    }

    /**
     * クーポンコードが未入力または、クーポンコードを登録後に再度別のクーポンコードが設定された場合、
     * 既存のクーポンを情報削除.
     *
     * @param Order       $Order
     */
    public function removeCouponOrder(Order $Order)
    {
        // クーポンが未入力でクーポン情報が存在すればクーポン情報を削除
        /** @var CouponOrder $CouponOrder */
        $CouponOrder = $this->couponOrderRepository->getCouponOrder($Order->getPreOrderId());
        if ($CouponOrder) {
            $OrderItem = $this->orderItemRepository->find($CouponOrder->getOrderItemId());
            if ($OrderItem) {
                $this->entityManager->remove($OrderItem);
                $this->entityManager->flush($OrderItem);
            }
            $this->entityManager->remove($CouponOrder);
            $this->entityManager->flush($CouponOrder);
        }
    }

    /**
     * 商品がクーポン適用の対象か調査する.
     *
     * @param Coupon $Coupon
     * @param Order  $Order
     *
     * @return array
     */
    private function containsProduct(Coupon $Coupon, Order $Order)
    {
        // クーポンの対象商品IDを配列にする
        $targetProductIds = [];
        $couponProducts = [];
        foreach ($Coupon->getCouponDetails() as $detail) {
            $targetProductIds[] = $detail->getProduct()->getId();
        }

        // 一致する商品IDがあればtrueを返す
        /* @var $detail OrderItem */
        foreach ($Order->getItems()->getProductClasses() as $detail) {
            if (in_array($detail->getProduct()->getId(), $targetProductIds)) {
                $couponProducts = $this->getCouponProducts($detail);
            }
        }

        return $couponProducts;
    }

    /**
     * カテゴリがクーポン適用の対象か調査する.
     * 下位のカテゴリから上位のカテゴリに向けて検索する.
     *
     * @param Coupon $Coupon
     * @param Order  $Order
     *
     * @return array
     */
    private function containsCategory(Coupon $Coupon, Order $Order)
    {
        // クーポンの対象カテゴリIDを配列にする
        $targetCategoryIds = [];
        $couponProducts = [];
        foreach ($Coupon->getCouponDetails() as $detail) {
            $targetCategoryIds[] = $detail->getCategory()->getId();
        }

        // 受注データからカテゴリIDを取得する
        /* @var $orderDetail OrderItem */
        foreach ($Order->getItems()->getProductClasses() as $orderDetail) {
            foreach ($orderDetail->getProduct()->getProductCategories() as $productCategory) {
                if ($this->existsDepthCategory($targetCategoryIds, $productCategory->getCategory())) {
                    $couponProducts = $this->getCouponProducts($orderDetail);
                }
            }
        }

        return $couponProducts;
    }

    /**
     * クーポン対象のカテゴリが存在するか確認にする.
     *
     * @param array      $targetCategoryIds
     * @param Category $Category
     *
     * @return bool
     */
    private function existsDepthCategory(&$targetCategoryIds, Category $Category)
    {
        // Categoryがnullならfalse
        if (is_null($Category)) {
            return false;
        }

        // 対象カテゴリか確認
        if (in_array($Category->getId(), $targetCategoryIds)) {
            return true;
        }

        // Categoryをテーブルから取得
        if (is_null($Category->getParent())) {
            return false;
        }

        // 親カテゴリをテーブルから取得
        /** @var Category $ParentCategory */
        $ParentCategory = $this->categoryRepository->find($Category->getParent());
        if ($ParentCategory) {
            return false;
        }

        return $this->existsDepthCategory($targetCategoryIds, $ParentCategory);
    }

    /**
     * @param $orderItem
     *
     * @return mixed
     */
    private function getCouponProducts(OrderItem $orderItem)
    {
        $couponProducts[$orderItem->getProductClass()->getId()]['price'] = $orderItem->getPriceIncTax();
        $couponProducts[$orderItem->getProductClass()->getId()]['quantity'] = $orderItem->getQuantity();
        $couponProducts[$orderItem->getProductClass()->getId()]['tax_rate'] = $orderItem->getTaxRate();
        $couponProducts[$orderItem->getProductClass()->getId()]['tax_rule'] = $orderItem->getTaxRule();

        return $couponProducts;
    }
}
