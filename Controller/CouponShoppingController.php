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

namespace Plugin\Coupon44\Controller;

use Eccube\Controller\AbstractController;
use Eccube\Entity\Customer;
use Eccube\Entity\Order;
use Eccube\Service\CartService;
use Eccube\Service\OrderHelper;
use Plugin\Coupon44\Entity\Coupon;
use Plugin\Coupon44\Form\Type\CouponUseType;
use Plugin\Coupon44\Repository\CouponOrderRepository;
use Plugin\Coupon44\Repository\CouponRepository;
use Plugin\Coupon44\Service\CouponService;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class CouponShoppingController extends AbstractController
{
    /**
     * CouponShoppingController constructor.
     *
     * @param CartService $cartService
     * @param CouponService $couponService
     * @param CouponRepository $couponRepository
     * @param CouponOrderRepository $couponOrderRepository
     */
    public function __construct(
        private readonly CartService $cartService,
        private readonly OrderHelper $orderHelper,
        private readonly CouponService $couponService,
        private readonly CouponRepository $couponRepository,
        private readonly CouponOrderRepository $couponOrderRepository,
    ) {
    }

    /**
     * クーポン入力、登録画面.
     *
     * @param Request     $request
     *
     * @return array<string, mixed>|RedirectResponse
     *
     * @see https://github.com/EC-CUBE/coupon-plugin/issues/128
     */
    #[Route(path: '/plugin/coupon/shopping/shopping_coupon', name: 'plugin_coupon_shopping')]
    // dtb_page.file_name (PluginManager::createPage で登録) と一致させるため, あえて名前空間
    // (@Coupon44) を使わない。名前空間なしの場合はテーマ側 (app/template/default/...) が
    // プラグイン本体 (app/Plugin) より優先して解決されるため, 管理画面のページ管理で編集した
    // 内容が反映される。@Coupon44 にするとプラグイン本体に固定解決され, ページ管理での編集が
    // 無視されてしまうので変更しないこと。
    #[Template('Coupon44/Resource/template/default/shopping_coupon.twig')]
    public function shoppingCoupon(Request $request)
    {
        $preOrderId = $this->cartService->getPreOrderId();
        /** @var Order|null $Order */
        $Order = $this->orderHelper->getPurchaseProcessingOrder($preOrderId);

        if (!$Order) {
            $this->addError('front.shopping.order_error');

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
            /** @var CouponService $service */
            $service = $this->couponService;
            $formCouponCd = $form->get('coupon_cd')->getData();
            $formCouponCancel = $form->get('coupon_use')->getData();
            // ---------------------------------
            // クーポンコード入力項目追加
            // ----------------------------------
            if ($formCouponCancel == 0) {
                // クーポンを利用しない
                $this->couponService->removeCouponOrder($Order);

                return $this->redirectToRoute('shopping');
            }
            // クーポンを利用する
            $discount = 0;
            $error = false;
            // クーポン情報を取得
            /* @var $Coupon Coupon */
            $Coupon = $this->couponRepository->findActiveCoupon($formCouponCd);
            if (!$Coupon) {
                $form->get('coupon_cd')->addError(new FormError(trans('plugin_coupon.front.shopping.notexists')));
                $error = true;
            }

            if ($this->isGranted('ROLE_USER')) {
                $Customer = $this->getUser();
            } else {
                $Customer = $this->orderHelper->getNonMember();
                if ($Coupon) {
                    if ($Coupon->getCouponMember()) {
                        $form->get('coupon_cd')->addError(new FormError(trans('plugin_coupon.front.shopping.member')));
                        $error = true;
                    }
                }
            }

            /** @var Customer $Customer */
            $couponUsedOrNot = $this->couponService->checkCouponUsedOrNot($formCouponCd, $Customer);
            if ($Coupon && $couponUsedOrNot) {
                // 既に存在している
                $form->get('coupon_cd')->addError(new FormError(trans('plugin_coupon.front.shopping.sameuser')));
                $error = true;
            }

            // ----------------------------------
            // 値引き項目追加 / 合計金額上書き
            // ----------------------------------
            if (!$error && $Coupon) {
                $couponProducts = $service->existsCouponProduct($Coupon, $Order);
                $discount = $service->recalcOrder($Coupon, $couponProducts);

                // クーポン情報を登録
                $service->saveCouponOrder($Order, $Coupon, $formCouponCd, $Customer, $discount);

                return $this->redirectToRoute('shopping');
            }
            // エラーが発生した場合、前回設定されているクーポンがあればその金額を再設定する
            if ($couponCd && $Coupon) {
                // クーポン情報を取得
                $Coupon = $this->couponRepository->findActiveCoupon($couponCd);
                if ($Coupon) {
                    $couponProducts = $service->existsCouponProduct($Coupon, $Order);
                    // 値引き額を取得
                    $discount = $service->recalcOrder($Coupon, $couponProducts);
                    // クーポン情報を登録
                    $service->saveCouponOrder($Order, $Coupon, $couponCd, $Customer, $discount);
                }
            }
        }

        return [
            'form' => $form->createView(),
            'Order' => $Order,
        ];
    }
}
