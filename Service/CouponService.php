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

namespace Plugin\Coupon4\Service;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Common\Constant;
use Eccube\Entity\Customer;
use Eccube\Entity\ItemHolderInterface;
use Eccube\Entity\Master\RoundingType;
use Eccube\Entity\Order;
use Eccube\Entity\OrderItem;
use Eccube\Entity\ProductClass;
use Eccube\Entity\TaxRule;
use Eccube\Repository\CategoryRepository;
use Eccube\Repository\Master\OrderItemTypeRepository;
use Eccube\Repository\Master\TaxDisplayTypeRepository;
use Eccube\Repository\Master\TaxTypeRepository;
use Eccube\Repository\OrderItemRepository;
use Eccube\Repository\ProductClassRepository;
use Eccube\Repository\TaxRuleRepository;
use Eccube\Service\TaxRuleService;
use Plugin\Coupon4\Entity\Coupon;
use Plugin\Coupon4\Entity\CouponOrder;
use Eccube\Entity\Category;
use Plugin\Coupon4\Repository\CouponOrderRepository;
use Plugin\Coupon4\Repository\CouponRepository;
use Plugin\Coupon4\Service\PurchaseFlow\Processor\CouponProcessor;
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
     * @var ProductClassRepository
     */
    private $productClassRepository;

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
     * @param ProductClassRepository $productClassRepository
     *
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        CouponRepository $couponRepository,
        CouponOrderRepository $couponOrderRepository,
        CategoryRepository $categoryRepository,
        TaxRuleService $taxRuleService,
        TaxRuleRepository $taxRuleRepository,
        TaxTypeRepository $taxTypeRepository,
        TaxDisplayTypeRepository $taxDisplayTypeRepository,
        OrderItemTypeRepository $orderItemTypeRepository,
        ContainerInterface $container,
        EntityManagerInterface $entityManager,
        OrderItemRepository $orderItemRepository,
        ProductClassRepository $productClassRepository
    ) {
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
        $this->productClassRepository = $productClassRepository;
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
     * @param ItemHolderInterface  $Order
     *
     * @return array
     */
    public function existsCouponProduct(Coupon $Coupon, ItemHolderInterface $Order)
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
                    $couponProducts = $this->getCouponProducts($detail, $couponProducts);
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
     * @param string   $couponCd
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
            // クーポン未登録の場合
            $CouponOrder = new CouponOrder();
            $CouponOrder->setOrderId($Order->getId());
            $CouponOrder->setPreOrderId($Order->getPreOrderId());
            $CouponOrder->setVisible(true);
        }

        // 更新対象データ
        if (is_null($Coupon) || (is_null($couponCd) || strlen($couponCd) == 0)) {
            // クーポンがない または クーポンコードが空の場合
            $CouponOrder->setCouponCd($couponCd);
            $CouponOrder->setCouponId(null);

            $this->setOrderCompleteMailMessage($Order, null, null);
        } else {
            $CouponOrder->setCouponId($Coupon->getId());
            $CouponOrder->setCouponCd($Coupon->getCouponCd());

            $this->setOrderCompleteMailMessage($Order, $Coupon->getCouponCd(), $Coupon->getCouponName());
        }

        $this->entityManager->flush($Order);
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
     * 税率が 0 以下の場合は、TaxRule から取得し直して再計算する
     *
     * @param Coupon $Coupon
     * @param array  $couponProducts ProductClass::id をキーにした単価, 数量, 税率の連想配列
     * @return float|int|string
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
                // 値引き前の金額で割引率を算出する
                $total = 0;
                // include tax
                foreach ($couponProducts as $productClassId => $value) {
                    // 税率が取得できない場合は TaxRule から取得し直す
                    if ($value['tax_rate'] < 1 || $value['rounding_type_id'] === null) {
                        /** @var ProductClass $ProductClass */
                        $ProductClass = $this->productClassRepository->find($productClassId);
                        $TaxRule = $this->taxRuleRepository->getByRule($ProductClass->getProduct(), $ProductClass);
                        $value['tax_rate'] = $TaxRule->getTaxRate();
                        $value['rounding_type_id'] = $TaxRule->getRoundingType()->getId();
                    }
                    $total += ($value['price'] + $this->taxRuleService->calcTax($value['price'], $value['tax_rate'], $value['rounding_type_id'])) * $value['quantity'];
                }
                /** @var TaxRule $DefaultTaxRule */
                $DefaultTaxRule = $this->taxRuleRepository->getByRule();
                // 丸め規則はデフォルトの課税規則に従う
                $discount = $this->taxRuleService->calcTax(
                    $total,
                    $Coupon->getDiscountRate(),
                    $DefaultTaxRule->getRoundingType()->getId(),
                    $DefaultTaxRule->getTaxAdjust()
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
            $subTotal += ($value['price'] + $this->taxRuleService->calcTax($value['price'], $value['tax_rate'], $value['rounding_type_id'])) * $value['quantity'];
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
     * @param ItemHolderInterface       $Order
     */
    public function removeCouponOrder(ItemHolderInterface $Order)
    {
        // クーポンが未入力でクーポン情報が存在すればクーポン情報を削除
        /** @var CouponOrder $CouponOrder */
        $CouponOrder = $this->couponOrderRepository->getCouponOrder($Order->getPreOrderId());
        if ($CouponOrder) {
            $OrderItems = $this->orderItemRepository->findBy(['processor_name' => CouponProcessor::class, 'Order' => $Order]);
            foreach ($OrderItems as $OrderItem) {
                $Order->removeOrderItem($OrderItem);
                $this->entityManager->remove($OrderItem);
                $this->entityManager->flush($OrderItem);
            }

            $this->entityManager->remove($CouponOrder);
            $this->entityManager->flush($CouponOrder);

            $this->setOrderCompleteMailMessage($Order, null, null);
            $this->entityManager->flush($Order);
        }
    }

    /**
     * クーポン情報があれば、購入完了メールにメッセージを追加する.
     *
     * 無ければ、メッセージを削除する
     *
     * @param Order $Order
     * @param string $couponCd
     * @param string $couponName
     */
    public function setOrderCompleteMailMessage(Order $Order, $couponCd = null, $couponName = null)
    {
        $snippet = '***********************************************'.PHP_EOL;
        $snippet .= '　クーポン情報                                 '.PHP_EOL;
        $snippet .= '***********************************************'.PHP_EOL;
        $snippet .= PHP_EOL;
        $snippet .= 'クーポンコード: ';

        $message = $Order->getCompleteMailMessage();
        if ($message) {
            $message = preg_replace('/'.preg_quote($snippet).'.*$/m', '', $message);
            $Order->setCompleteMailMessage($message ? trim($message) : null);
            $snippet = PHP_EOL.$snippet; // 行頭に改行コードを追加
        }

        if ($couponCd && $couponName) {
            $snippet .= $couponCd.' '.$couponName.PHP_EOL;
            $Order->appendCompleteMailMessage($snippet);
        }
    }

    /**
     * 商品がクーポン適用の対象か調査する.
     *
     * @param Coupon $Coupon
     * @param ItemHolderInterface  $Order
     *
     * @return array
     */
    private function containsProduct(Coupon $Coupon, ItemHolderInterface $Order)
    {
        // クーポンの対象商品IDを配列にする
        $targetProductIds = [];
        $couponProducts = [];
        foreach ($Coupon->getCouponDetails() as $detail) {
            $targetProductIds[] = $detail->getProduct()->getId();
        }

        // 一致する商品IDがあればtrueを返す
        /* @var $detail OrderItem */
        foreach ($Order->getProductOrderItems() as $detail) {
            if (in_array($detail->getProduct()->getId(), $targetProductIds)) {
                $couponProducts = $this->getCouponProducts($detail, $couponProducts);
            }
        }

        return $couponProducts;
    }

    /**
     * カテゴリがクーポン適用の対象か調査する.
     * 下位のカテゴリから上位のカテゴリに向けて検索する.
     *
     * @param Coupon $Coupon
     * @param ItemHolderInterface  $Order
     *
     * @return array
     */
    private function containsCategory(Coupon $Coupon, ItemHolderInterface $Order)
    {
        // クーポンの対象カテゴリIDを配列にする
        $targetCategoryIds = [];
        $couponProducts = [];
        foreach ($Coupon->getCouponDetails() as $detail) {
            $targetCategoryIds[] = $detail->getCategory()->getId();
        }
        // 受注データからカテゴリIDを取得する
        /* @var $orderDetail OrderItem */
        foreach ($Order->getProductOrderItems() as $orderDetail) {
            foreach ($orderDetail->getProduct()->getProductCategories() as $productCategory) {
                if ($this->existsDepthCategory($targetCategoryIds, $productCategory->getCategory())) {
                    $couponProducts = $this->getCouponProducts($orderDetail, $couponProducts);
                    break;
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
     * @param OrderItem $orderItem
     * @param array $couponProducts
     *
     * @return mixed
     */
    private function getCouponProducts(OrderItem $orderItem, array $couponProducts = [])
    {
        if (array_key_exists($orderItem->getProductClass()->getId(), $couponProducts)) {
            $couponProducts[$orderItem->getProductClass()->getId()]['quantity'] += $orderItem->getQuantity();
        } else {
            $couponProducts[$orderItem->getProductClass()->getId()] = [
                'price' => $orderItem->getPrice(),
                'quantity' => $orderItem->getQuantity(),
                // tax_rate, rounding_type_idは複数配送の個数変更時に取得できない. recalcOrderで取得し直している
                // https://github.com/EC-CUBE/coupon-plugin/pull/106/commits/d47f60745b283023cd7a990c609e6399701ddce1
                'tax_rate' => $orderItem->getTaxRate(),
                'rounding_type_id' => $orderItem->getRoundingType() ? $orderItem->getRoundingType()->getId() : null,
            ];
        }

        return $couponProducts;
    }
}
