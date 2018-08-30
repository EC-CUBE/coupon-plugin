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

namespace Plugin\Coupon\Controller;

use Eccube\Controller\AbstractController;
use Eccube\Entity\Customer;
use Eccube\Entity\Order;
use Eccube\Repository\DeliveryTimeRepository;
use Eccube\Service\CartService;
use Plugin\Coupon\Entity\Coupon;
use Plugin\Coupon\Form\Type\CouponUseType;
use Plugin\Coupon\Repository\CouponOrderRepository;
use Plugin\Coupon\Repository\CouponRepository;
use Plugin\Coupon\Service\CouponService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Eccube\Repository\OrderRepository;
use Eccube\Service\OrderHelper;

class CouponShoppingController extends AbstractController
{
    /**
     * @var DeliveryTimeRepository
     */
    private $deliveryTimeRepository;

    /**
     * @var CartService
     */
    private $cartService;

    /**
     * @var CouponService
     */
    private $couponService;

    /**
     * @var CouponRepository
     */
    private $couponRepository;

    /**
     * @var CouponOrderRepository
     */
    private $couponOrderRepository;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var OrderHelper
     */
    protected $orderHelper;

    /**
     * CouponShoppingController constructor.
     *
     * @param DeliveryTimeRepository $deliveryTimeRepository
     * @param CartService $cartService
     * @param CouponService $couponService
     * @param CouponRepository $couponRepository
     * @param CouponOrderRepository $couponOrderRepository
     * @param OrderRepository $orderRepository
     * @param OrderHelper $orderHelper
     */
    public function __construct(
        DeliveryTimeRepository $deliveryTimeRepository,
        CartService $cartService, CouponService $couponService,
        CouponRepository $couponRepository,
        CouponOrderRepository $couponOrderRepository,
        OrderRepository $orderRepository,
        OrderHelper $orderHelper
    ) {
        $this->deliveryTimeRepository = $deliveryTimeRepository;
        $this->cartService = $cartService;
        $this->couponService = $couponService;
        $this->couponRepository = $couponRepository;
        $this->couponOrderRepository = $couponOrderRepository;
        $this->orderRepository = $orderRepository;
        $this->orderHelper = $orderHelper;
    }

    /**
     * クーポン入力、登録画面.
     *
     * @param Request     $request
     *
     * @return array
     * @Route("/plugin/coupon/shopping/shopping_coupon", name="plugin_coupon_shopping")
     * @Template("@Coupon/default/shopping_coupon.twig")
     */
    public function shoppingCoupon(Request $request)
    {
        /** @var Order $Order */
        $Order = $this->orderHelper->getPurchaseProcessingOrder($this->cartService->getPreOrderId());

        if (!$Order) {
            $this->addError('front.shopping.order.error');

            return $this->redirectToRoute('shopping_error');
        }
        $form = $this->formFactory->createBuilder(CouponUseType::class)->getForm();
        // クーポンコードを取得する
        $CouponOrder = $this->couponOrderRepository->getCouponOrder($Order->getPreOrderId());
        $couponCd = null;
        if ($CouponOrder) {
            $couponCd = $CouponOrder->getCouponCd();
        }

        $form->get('coupon_cd')->setData($couponCd);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // サービスの取得
            /* @var $service CouponService */
            $service = $this->couponService;
            $formCouponCd = $form->get('coupon_cd')->getData();
            $formCouponCancel = $form->get('coupon_use')->getData();
            // ---------------------------------
            // クーポンコード入力項目追加
            // ----------------------------------
            if ($formCouponCancel == 0) {
                if (!is_null($formCouponCd)) {
                    // 画面上のクーポンコードが入力されておらず、既にクーポンコードが登録されていればクーポンを無効にする
                    $this->couponService->removeCouponOrder($Order);
                }

                return $this->redirectToRoute('shopping');
            } else {
                // クーポンコードが入力されている
                $discount = 0;
                $error = false;
                // クーポン情報を取得
                /* @var $Coupon Coupon */
                $Coupon = $this->couponRepository->findActiveCoupon($formCouponCd);
                if (!$Coupon) {
                    $form->get('coupon_cd')->addError(new FormError(trans('coupon.front.shopping.notexists')));
                    $error = true;
                }

                if ($this->isGranted('ROLE_USER')) {
                    $Customer = $this->getUser();
                } else {
                    $Customer = $this->orderHelper->getNonMember();
                    if ($Coupon) {
                        if ($Coupon->getCouponMember()) {
                            $form->get('coupon_cd')->addError(new FormError(trans('coupon.front.shopping.member')));
                            $error = true;
                        }
                    }
                }
                if (!$error && $Coupon) {
                    $lowerLimit = $Coupon->getCouponLowerLimit();

                    // 既に登録済みのクーポンコードを一旦削除
//                    $this->couponService->removeCouponOrder($Order);
                    // 対象クーポンが存在しているかチェック
                    $couponProducts = $service->existsCouponProduct($Coupon, $Order);
                    $checkLowerLimit = $service->isLowerLimitCoupon($couponProducts, $lowerLimit);
                    // 値引き額を取得
                    $discount = $service->recalcOrder($Coupon, $couponProducts);
                    $existCoupon = true;
                    if (count($couponProducts) == 0) {
                        $existCoupon = false;
                    }

                    if (!$existCoupon) {
                        $form->get('coupon_cd')->addError(new FormError(trans('coupon.front.shopping.notexists')));
                        $error = true;
                    }

                    if (!$checkLowerLimit) {
                        $message = trans('coupon.front.shopping.lowerlimit', ['lowerLimit' => number_format($lowerLimit)]);
                        $form->get('coupon_cd')->addError(new FormError($message));
                        $error = true;
                    }

                    // クーポンが既に利用されているかチェック
                    $couponUsed = $service->checkCouponUsedOrNot($formCouponCd, $Customer);
                    if ($couponUsed) {
                        // 既に存在している
                        $form->get('coupon_cd')->addError(new FormError(trans('coupon.front.shopping.sameuser')));
                        $error = true;
                    }

                    // クーポンの発行枚数チェック
                    $checkCouponUseTime = $this->couponRepository->checkCouponUseTime($formCouponCd);
                    if (!$checkCouponUseTime && $existCoupon) {
                        $form->get('coupon_cd')->addError(new FormError(trans('coupon.front.shopping.couponusetime')));
                        $error = true;
                    }

                    // Todo: check discount vs total payment
                    // 合計金額より値引き額の方が高いかチェック
//                    if ($Order->getTotal() < $discount && $existCoupon) {
//                        $form->get('coupon_cd')->addError(new FormError('coupon.front.shopping.minus'));
//                        $error = true;
//                    }
                }

                // ----------------------------------
                // 値引き項目追加 / 合計金額上書き
                // ----------------------------------
                if (!$error && $Coupon) {
                    // クーポン情報を登録
                    $this->setCouponOrder($Order, $Coupon, $formCouponCd, $Customer, $discount);

                    return $this->redirectToRoute('shopping');
                } else {
                    // エラーが発生した場合、前回設定されているクーポンがあればその金額を再設定する
                    if ($couponCd && $Coupon) {
                        // クーポン情報を取得
                        $Coupon = $this->couponRepository->findActiveCoupon($couponCd);
                        if ($Coupon) {
                            $couponProducts = $service->existsCouponProduct($Coupon, $Order);
                            // 値引き額を取得
                            $discount = $service->recalcOrder($Coupon, $couponProducts);
                            // クーポン情報を登録
                            $this->setCouponOrder($Order, $Coupon, $couponCd, $Customer, $discount);
                        }
                    }
                }
            }
        }

        return [
            'form' => $form->createView(),
            'Order' => $Order,
        ];
    }

    /**
     * クーポン情報に登録.
     *
     * @param Order       $Order
     * @param Coupon      $Coupon
     * @param string      $couponCd
     * @param Customer    $Customer
     * @param int         $discount
     */
    private function setCouponOrder(Order $Order, Coupon $Coupon, $couponCd, Customer $Customer, $discount)
    {
        // クーポン受注情報を保存する
        $this->couponService->saveCouponOrder($Order, $Coupon, $couponCd, $Customer, $discount);
    }
}
