<?php
/*
 * This file is part of the Coupon plugin
 *
 * Copyright (C) 2016 LOCKON CO.,LTD. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\Coupon\Controller;

use Eccube\Application;
use Eccube\Common\Constant;
use Eccube\Entity\Customer;
use Eccube\Entity\Order;
use Eccube\Entity\Shipping;
use Plugin\Coupon\Entity\Coupon;
use Plugin\Coupon\Service\CouponService;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Eccube\Controller\AbstractController;

/**
 * Class CouponController.
 */
class CouponController extends AbstractController
{
    /**
     * @var string 非会員用セッションキー
     */
    private $sessionKey = 'eccube.front.shopping.nonmember';

    /**
     * クーポン設定画面表示.
     *
     * @param Application $app
     * @param Request     $request
     *
     * @return Response
     */
    public function index(Application $app, Request $request)
    {
        // クーポン削除時のtokenで使用
        $searchForm = $app['form.factory']->createBuilder('admin_plugin_coupon_search')->getForm();
        $pagination = $app['coupon.repository.coupon']->findBy(
            array(),
            array('id' => 'DESC')
        );

        return $app->render('Coupon/Resource/template/admin/index.twig', array(
            'searchForm' => $searchForm->createView(),
            'pagination' => $pagination,
            'totalItemCount' => count($pagination),
        ));
    }

    /**
     * クーポンの新規作成/編集確定.
     *
     * @param Application $app
     * @param Request     $request
     * @param int         $id
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function edit(Application $app, Request $request, $id = null)
    {
        $Coupon = null;
        if (!$id) {
            // 新規登録
            $Coupon = new Coupon();
            $Coupon->setEnableFlag(Constant::ENABLED);
            $Coupon->setDelFlg(Constant::DISABLED);
        } else {
            // 更新
            $Coupon = $app['coupon.repository.coupon']->find($id);
            if (!$Coupon) {
                $app->addError('admin.plugin.coupon.notfound', 'admin');

                return $app->redirect($app->url('plugin_coupon_list'));
            }
        }

        $form = $app['form.factory']->createBuilder('admin_plugin_coupon', $Coupon)->getForm();
        // クーポンコードの発行
        if (!$id) {
            $form->get('coupon_cd')->setData($app['eccube.plugin.coupon.service.coupon']->generateCouponCd());
        }
        $details = array();
        $CouponDetails = $Coupon->getCouponDetails();
        foreach ($CouponDetails as $CouponDetail) {
            $details[] = clone $CouponDetail;
            $CouponDetail->getCategoryFullName();
        }
        $form->get('CouponDetails')->setData($details);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var \Plugin\Coupon\Entity\Coupon $Coupon */
            $Coupon = $form->getData();
            $oldReleaseNumber = $request->get('coupon_release_old');
            if (is_null($Coupon->getCouponUseTime())) {
                $Coupon->setCouponUseTime($Coupon->getCouponRelease());
            } else {
                if ($Coupon->getCouponRelease() != $oldReleaseNumber) {
                    $Coupon->setCouponUseTime($Coupon->getCouponRelease());
                }
            }

            $CouponDetails = $app['coupon.repository.coupon_detail']->findBy(array(
                'Coupon' => $Coupon,
            ));
            foreach ($CouponDetails as $CouponDetail) {
                $Coupon->removeCouponDetail($CouponDetail);
                $app['orm.em']->remove($CouponDetail);
                $app['orm.em']->flush($CouponDetail);
            }
            $CouponDetails = $form->get('CouponDetails')->getData();
            foreach ($CouponDetails as $CouponDetail) {
                $CouponDetail->setCoupon($Coupon);
                $CouponDetail->setCouponType($Coupon->getCouponType());
                $CouponDetail->setDelFlg(Constant::DISABLED);
                $Coupon->addCouponDetail($CouponDetail);
                $app['orm.em']->persist($CouponDetail);
            }
            $app['orm.em']->persist($Coupon);
            $app['orm.em']->flush($Coupon);
            // 成功時のメッセージを登録する
            $app->addSuccess('admin.plugin.coupon.regist.success', 'admin');

            return $app->redirect($app->url('plugin_coupon_list'));
        }

        return $this->renderRegistView($app, array(
            'form' => $form->createView(),
            'id' => $id,
        ));
    }

    /**
     * クーポンの有効/無効化.
     *
     * @param Application $app
     * @param Request     $request
     * @param int         $id
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function enable(Application $app, Request $request, $id)
    {
        $coupon = $app['coupon.repository.coupon']->find($id);
        if (!$coupon) {
            $app->addError('admin.plugin.coupon.notfound', 'admin');

            return $app->redirect($app->url('plugin_coupon_list'));
        }
        // =============
        // 更新処理
        // =============
        $status = $app['eccube.plugin.coupon.service.coupon']->enableCoupon($id);
        if ($status) {
            $app->addSuccess('admin.plugin.coupon.enable.success', 'admin');
            log_info('Change status a coupon with ', array('ID' => $id));
        } else {
            $app->addError('admin.plugin.coupon.notfound', 'admin');
        }

        return $app->redirect($app->url('plugin_coupon_list'));
    }

    /**
     * クーポンの削除.
     *
     * @param Application $app
     * @param Request     $request
     * @param int         $id
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function delete(Application $app, Request $request, $id)
    {
        $this->isTokenValid($app);
        $coupon = $app['coupon.repository.coupon']->find($id);
        if (!$coupon) {
            $app->addError('admin.plugin.coupon.notfound', 'admin');

            return $app->redirect($app->url('plugin_coupon_list'));
        } else {
            $service = $app['eccube.plugin.coupon.service.coupon'];
            // クーポン情報を削除する
            if ($service->deleteCoupon($id)) {
                $app->addSuccess('admin.plugin.coupon.delete.success', 'admin');
                log_info('Delete a coupon with ', array('ID' => $id));
            } else {
                $app->addError('admin.plugin.coupon.notfound', 'admin');
            }
        }

        return $app->redirect($app->url('plugin_coupon_list'));
    }

    /**
     * 編集画面用のrender.
     *
     * @param Application $app
     * @param array       $parameters
     *
     * @return Response
     */
    protected function renderRegistView(Application $app, $parameters = array())
    {
        // 商品検索フォーム
        $searchProductModalForm = $app['form.factory']->createBuilder('admin_search_product')->getForm();
        // カテゴリ検索フォーム
        $searchCategoryModalForm = $app['form.factory']->createBuilder('admin_plugin_coupon_search_category')->getForm();
        $viewParameters = array(
            'searchProductModalForm' => $searchProductModalForm->createView(),
            'searchCategoryModalForm' => $searchCategoryModalForm->createView(),
        );
        $viewParameters += $parameters;

        return $app->render('Coupon/Resource/template/admin/regist.twig', $viewParameters);
    }

    /**
     * クーポン入力、登録画面.
     *
     * @param Application $app
     * @param Request     $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function shoppingCoupon(Application $app, Request $request)
    {
        // カートチェック
        if (!$app['eccube.service.cart']->isLocked()) {
            // カートが存在しない、カートがロックされていない時はエラー
            return $app->redirect($app->url('cart'));
        }
        $Order = $app['eccube.service.shopping']->getOrder($app['config']['order_processing']);
        if (!$Order) {
            $app->addError('front.shopping.order.error');

            return $app->redirect($app->url('shopping_error'));
        }
        $form = $app['form.factory']->createBuilder('front_plugin_coupon_shopping')->getForm();
        // クーポンコードを取得する
        $CouponOrder = $app['eccube.plugin.coupon.service.coupon']->getCouponOrder($Order->getPreOrderId());
        $couponCd = null;
        if ($CouponOrder) {
            $couponCd = $CouponOrder->getCouponCd();
        }

        $form->get('coupon_cd')->setData($couponCd);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // サービスの取得
            /* @var $service CouponService */
            $service = $app['eccube.plugin.coupon.service.coupon'];
            $formCouponCd = $form->get('coupon_cd')->getData();
            $formCouponCancel = $form->get('coupon_use')->getData();
            // ---------------------------------
            // クーポンコード入力項目追加
            // ----------------------------------
            if ($formCouponCancel == 0) {
                if (!is_null($formCouponCd)) {
                    // 画面上のクーポンコードが入力されておらず、既にクーポンコードが登録されていればクーポンを無効にする
                    $this->removeCouponOrder($Order, $app);
                }

                return $app->redirect($app->url('shopping'));
            }
            else {
                // クーポンコードが入力されている
                $discount = 0;
                $error = false;
                // クーポン情報を取得
                /* @var $Coupon Coupon */
                $Coupon = $app['coupon.repository.coupon']->findActiveCoupon($formCouponCd);
                if ($app->isGranted('ROLE_USER')) {
                    $Customer = $app->user();
                } else {
                    $Customer = $app['eccube.service.shopping']->getNonMember($this->sessionKey);
                    if ($Coupon) {
                        if ($Coupon->getCouponMember()) {
                            $form->get('coupon_cd')->addError(new FormError('front.plugin.coupon.shopping.member'));
                            $error = true;
                        }
                    }
                }
                if ($Coupon && !$error) {
                    $lowerLimit = $Coupon->getCouponLowerLimit();
                    // 既に登録済みのクーポンコードを一旦削除
                    $this->removeCouponOrder($Order, $app);
                    // 対象クーポンが存在しているかチェック
                    $couponProducts = $service->existsCouponProduct($Coupon, $Order);
                    $checkLowerLimit = $service->isLowerLimitCoupon($couponProducts, $lowerLimit);
                    // 値引き額を取得
                    $discount = $service->recalcOrder($Order, $Coupon, $couponProducts);
                    if (sizeof($couponProducts) == 0) {
                        $existCoupon = false;
                    } else {
                        $existCoupon = true;
                    }

                    if (!$existCoupon) {
                        $form->get('coupon_cd')->addError(new FormError('front.plugin.coupon.shopping.notexists'));
                        $error = true;
                    }

                    if (!$checkLowerLimit && $existCoupon) {
                        $message = $app->trans('front.plugin.coupon.shopping.lowerlimit', array('lowerLimit' => number_format($lowerLimit)));
                        $form->get('coupon_cd')->addError(new FormError($message));
                        $error = true;
                    }

                    // クーポンが既に利用されているかチェック
                    $couponUsedOrNot = $service->checkCouponUsedOrNot($formCouponCd, $Customer);
                    if ($couponUsedOrNot && $existCoupon) {
                        // 既に存在している
                        $form->get('coupon_cd')->addError(new FormError('front.plugin.coupon.shopping.sameuser'));
                        $error = true;
                    }

                    // クーポンの発行枚数チェック
                    $checkCouponUseTime = $service->checkCouponUseTime($formCouponCd, $app);
                    if (!$checkCouponUseTime && $existCoupon) {
                        $form->get('coupon_cd')->addError(new FormError('front.plugin.coupon.shopping.couponusetime'));
                        $error = true;
                    }

                    // 合計金額より値引き額の方が高いかチェック
                    if ($Order->getTotal() < $discount && $existCoupon) {
                        $form->get('coupon_cd')->addError(new FormError('front.plugin.coupon.shopping.minus'));
                        $error = true;
                    }
                } elseif (!$Coupon) {
                    $form->get('coupon_cd')->addError(new FormError('front.plugin.coupon.shopping.notexists'));
                }
                // ----------------------------------
                // 値引き項目追加 / 合計金額上書き
                // ----------------------------------
                if (!$error && $Coupon) {
                    // クーポン情報を登録
                    $this->setCouponOrder($Order, $Coupon, $formCouponCd, $Customer, $discount, $app);

                    return $app->redirect($app->url('shopping'));
                } else {
                    // エラーが発生した場合、前回設定されているクーポンがあればその金額を再設定する
                    if ($couponCd && $Coupon) {
                        // クーポン情報を取得
                        $Coupon = $app['coupon.repository.coupon']->findActiveCoupon($couponCd);
                        if ($Coupon) {
                            $couponProducts = $service->existsCouponProduct($Coupon, $Order);
                            // 値引き額を取得
                            $discount = $service->recalcOrder($Order, $Coupon, $couponProducts);
                            // クーポン情報を登録
                            $this->setCouponOrder($Order, $Coupon, $couponCd, $Customer, $discount, $app);
                        }
                    }
                }
            }
        }

        return $app->render('Coupon/Resource/template/default/shopping_coupon.twig', array(
            'form' => $form->createView(),
            'Order' => $Order,
        ));
    }

    /**
     *  save delivery.
     *
     * @param Application $app
     * @param Request     $request
     *
     * @return Response
     */
    public function saveDelivery(Application $app, Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $date = explode(',', $request->get('coupon_delivery_date'));
            $time = explode(',', $request->get('coupon_delivery_time'));
            $message = $request->get('message');
            /* @var Order $Order */
            $Order = $app['eccube.service.shopping']->getOrder($app['config']['order_processing']);
            /* @var Shipping $Shipping */
            $Shippings = $Order->getShippings();
            $index = 0;
            foreach ($Shippings as $Shipping) {
                if ($time[$index]) {
                    $DeliveryTime = $app['eccube.repository.delivery_time']->find($time[$index]);
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
                $app['orm.em']->persist($Shipping);
                $app['orm.em']->flush($Shipping);
            }
            $Order->setMessage($message);
            $app['orm.em']->persist($Order);
            $app['orm.em']->flush($Order);
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
     * @param Application $app
     */
    private function setCouponOrder(Order $Order, Coupon $Coupon, $couponCd, Customer $Customer, $discount, Application $app)
    {
        $total = $Order->getTotal() - $discount;
        $Order->setDiscount($Order->getDiscount() + $discount);
        $Order->setTotal($total);
        $Order->setPaymentTotal($total);
        // クーポン受注情報を保存する
        $app['eccube.plugin.coupon.service.coupon']->saveCouponOrder($Order, $Coupon, $couponCd, $Customer, $discount);
        // 合計、値引きを再計算し、dtb_orderを更新する
        $app['orm.em']->persist($Order);
        $app['orm.em']->flush($Order);
    }

    /**
     * クーポンコードが未入力または、クーポンコードを登録後に再度別のクーポンコードが設定された場合、
     * 既存のクーポンを情報削除.
     *
     * @param Order       $Order
     * @param Application $app
     */
    private function removeCouponOrder(Order $Order, Application $app)
    {
        // クーポンが未入力でクーポン情報が存在すればクーポン情報を削除
        $CouponOrder = $app['eccube.plugin.coupon.service.coupon']->getCouponOrder($Order->getPreOrderId());
        if ($CouponOrder) {
            $app['orm.em']->remove($CouponOrder);
            $app['orm.em']->flush($CouponOrder);
            $Order->setDiscount($Order->getDiscount() - $CouponOrder->getDiscount());
            $Order->setTotal($Order->getTotal() + $CouponOrder->getDiscount());
            $Order->setPaymentTotal($Order->getPaymentTotal() + $CouponOrder->getDiscount());
            $app['orm.em']->persist($Order);
            $app['orm.em']->flush($Order);
        }
    }
}
