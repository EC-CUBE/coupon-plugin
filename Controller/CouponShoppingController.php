<?php
/**
 * Created by PhpStorm.
 * User: lqdung
 * Date: 4/27/2018
 * Time: 2:32 PM
 */

namespace Plugin\Coupon\Controller;


use Eccube\Controller\AbstractController;
use Eccube\Entity\Customer;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Order;
use Eccube\Entity\Shipping;
use Eccube\Repository\DeliveryTimeRepository;
use Eccube\Service\CartService;
use Eccube\Service\ShoppingService;
use Plugin\Coupon\Entity\Coupon;
use Plugin\Coupon\Form\Type\CouponUseType;
use Plugin\Coupon\Repository\CouponOrderRepository;
use Plugin\Coupon\Repository\CouponRepository;
use Plugin\Coupon\Service\CouponService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CouponShoppingController extends AbstractController
{
    /**
     * @var string 非会員用セッションキー
     */
    private $sessionKey = 'eccube.front.shopping.nonmember';

    /**
     * @var ShoppingService
     */
    private $shoppingService;

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
     * CouponShoppingController constructor.
     * @param ShoppingService $shoppingService
     * @param DeliveryTimeRepository $deliveryTimeRepository
     * @param CartService $cartService
     * @param CouponService $couponService
     * @param CouponRepository $couponRepository
     * @param CouponOrderRepository $couponOrderRepository
     */
    public function __construct(ShoppingService $shoppingService, DeliveryTimeRepository $deliveryTimeRepository, CartService $cartService, CouponService $couponService, CouponRepository $couponRepository, CouponOrderRepository $couponOrderRepository)
    {
        $this->shoppingService = $shoppingService;
        $this->deliveryTimeRepository = $deliveryTimeRepository;
        $this->cartService = $cartService;
        $this->couponService = $couponService;
        $this->couponRepository = $couponRepository;
        $this->couponOrderRepository = $couponOrderRepository;
    }


    /**
     * クーポン入力、登録画面.
     *
     * @param Request     $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     * @Route("/plugin/coupon/shopping/shopping_coupon", name="plugin_coupon_shopping")
     */
    public function shoppingCoupon(Request $request)
    {
        // カートチェック
        if (!$this->cartService->isLocked()) {
            // カートが存在しない、カートがロックされていない時はエラー
            return $this->redirectToRoute('cart');
        }
        /** @var Order $Order */
        $Order = $this->shoppingService->getOrder(OrderStatus::PROCESSING);

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
                    $form->get('coupon_cd')->addError(new FormError('front.plugin.coupon.shopping.notexists'));
                    $error = true;
                }

                if ($this->isGranted('ROLE_USER')) {
                    $Customer = $this->getUser();
                } else {
                    $Customer = $this->shoppingService->getNonMember($this->sessionKey);
                    if ($Coupon) {
                        if ($Coupon->getCouponMember()) {
                            $form->get('coupon_cd')->addError(new FormError('front.plugin.coupon.shopping.member'));
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
                        $form->get('coupon_cd')->addError(new FormError('front.plugin.coupon.shopping.notexists'));
                        $error = true;
                    }

                    if (!$checkLowerLimit) {
                        $message = $this->translator->trans('front.plugin.coupon.shopping.lowerlimit', array('lowerLimit' => number_format($lowerLimit)));
                        $form->get('coupon_cd')->addError(new FormError($message));
                        $error = true;
                    }

                    // クーポンが既に利用されているかチェック
                    $couponUsed = $service->checkCouponUsedOrNot($formCouponCd, $Customer);
                    if ($couponUsed) {
                        // 既に存在している
                        $form->get('coupon_cd')->addError(new FormError('front.plugin.coupon.shopping.sameuser'));
                        $error = true;
                    }

                    // クーポンの発行枚数チェック
                    $checkCouponUseTime = $this->couponRepository->checkCouponUseTime($formCouponCd);
                    if (!$checkCouponUseTime && $existCoupon) {
                        $form->get('coupon_cd')->addError(new FormError('front.plugin.coupon.shopping.couponusetime'));
                        $error = true;
                    }

                    // Todo: check discount vs total payment
                    // 合計金額より値引き額の方が高いかチェック
//                    if ($Order->getTotal() < $discount && $existCoupon) {
//                        $form->get('coupon_cd')->addError(new FormError('front.plugin.coupon.shopping.minus'));
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

        return $this->render('Coupon/Resource/template/default/shopping_coupon.twig', array(
            'form' => $form->createView(),
            'Order' => $Order,
        ));
    }

    /**
     *  save delivery.
     *
     * @param Request     $request
     *
     * @return Response
     * @Route("/plugin/coupon/save/delivery", name="plugin_coupon_save_delivery")
     */
    public function saveDelivery(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $date = explode(',', $request->get('coupon_delivery_date'));
            $time = explode(',', $request->get('coupon_delivery_time'));
            $message = $request->get('message');
            /* @var Order $Order */
            $Order = $this->shoppingService->getOrder(OrderStatus::PROCESSING);
            /* @var Shipping $Shipping */
            $Shippings = $Order->getShippings();
            $index = 0;
            foreach ($Shippings as $Shipping) {
                if ($time[$index]) {
                    $DeliveryTime = $this->deliveryTimeRepository->find($time[$index]);
                    $Shipping->setDeliveryTime($DeliveryTime);
                } else {
                    $Shipping->setDeliveryTime(null);
                }

                if ($date[$index]) {
                    $Shipping->setShippingDeliveryDate(new \DateTime($date[$index]));
                } else {
                    $Shipping->setShippingDeliveryDate(null);
                }

                ++$index;
                $this->entityManager->persist($Shipping);
                $this->entityManager->flush($Shipping);
            }
            $Order->setMessage($message);
            $this->entityManager->persist($Order);
            $this->entityManager->flush($Order);
        }

        return new Response();
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
//        $total = $Order->getTotal() - $discount;
//        $Order->setDiscount($Order->getDiscount() + $discount);
//        $Order->setTotal($total);
//        $Order->setPaymentTotal($total);
        // クーポン受注情報を保存する
        $this->couponService->saveCouponOrder($Order, $Coupon, $couponCd, $Customer, $discount);
        // 合計、値引きを再計算し、dtb_orderを更新する
//        $this->entityManager->persist($Order);
//        $this->entityManager->flush($Order);
    }
}