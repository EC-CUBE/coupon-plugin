<?php
/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) 2000-2015 LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

namespace Plugin\Coupon\Controller;

use Eccube\Application;
use Eccube\Entity\Customer;
use Eccube\Entity\Order;
use Plugin\Coupon\Entity\CouponCoupon;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception as HttpException;
use Symfony\Component\Validator\Constraints as Assert;

class CouponController
{

    /**
     * @var string 非会員用セッションキー
     */
    private $sessionKey = 'eccube.front.shopping.nonmember';

    /**
     * クーポン設定画面表示.
     *
     * @param Application $app
     * @param Request $request
     * @return Response
     */
    public function index(Application $app, Request $request)
    {
        // クーポン削除時のtokenで使用
        $searchForm = $app['form.factory']->createBuilder('admin_coupon_search')->getForm();

        $pagination = $app['eccube.plugin.coupon.repository.coupon']->findBy(
            array(),
            array('id' => 'DESC')
        );

        return $app->render('Coupon/View/admin/index.twig', array(
            'searchForm' => $searchForm->createView(),
            'pagination' => $pagination,
            'totalItemCount' => count($pagination)
        ));
    }

    /**
     * クーポンの新規作成
     *
     * @param Application $app
     * @param Request $request
     * @param $id
     * @return Response
     */
    public function create(Application $app, Request $request, $id)
    {

        $form = $app['form.factory']->createBuilder('admin_coupon')->getForm();

        // サービスの取得
        $service = $app['eccube.plugin.coupon.service.coupon'];

        // クーポンコードの発行
        $form->get('coupon_cd')->setData($service->generateCouponCd());

        return $this->renderRegistView($app, array(
            'form' => $form->createView(),
            'id' => null,
        ));
    }

    /**
     * 編集
     *
     * @param Application $app
     * @param Request $request
     * @param $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function edit(Application $app, Request $request, $id)
    {
        $coupon = $app['eccube.plugin.coupon.repository.coupon']->find($id);

        if (!$coupon) {
            $app->addError('admin.coupon.notfound', 'admin');

            return $app->redirect($app->url('admin_coupon_list'));
        }

        $form = $app['form.factory']->createBuilder('admin_coupon', $coupon)->getForm();

        return $this->renderRegistView($app, array(
            'form' => $form->createView(),
            'coupon' => $coupon,
            'id' => $coupon->getId(),
        ));
    }

    /**
     * クーポンの新規作成/編集確定
     *
     * @param Application $app
     * @param Request $request
     * @param $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function commit(Application $app, Request $request, $id)
    {

        $coupon = null;
        if ($id) {
            $coupon = $app['eccube.plugin.coupon.repository.coupon']->find($id);

            if (!$coupon) {
                $app->addError('admin.coupon.notfound', 'admin');

                return $app->redirect($app->url('admin_coupon_list'));
            }
        }

        $form = $app['form.factory']->createBuilder('admin_coupon', $coupon)->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $data = $form->getData();

            // サービスの取得
            // @var \Plugin\Coupon\Service\CouponService
            $service = $app['eccube.plugin.coupon.service.coupon'];

            if (!$coupon) {
                // =============
                // 登録処理
                // =============
                $status = $service->createCoupon($data);
            } else {
                // =============
                // 更新処理
                // =============
                $status = $service->updateCoupon($data);
                if (!$status) {
                    $app->addError('admin.coupon.notfound', 'admin');

                    return $app->redirect($app->url('admin_coupon_list'));
                }

            }

            // 成功時のメッセージを登録する
            $app->addSuccess('admin.plugin.coupon.regist.success', 'admin');

            return $app->redirect($app->url('admin_coupon_list'));
        }

        return $this->renderRegistView($app, array(
            'form' => $form->createView(),
            'id' => $id,
        ));

    }

    /**
     * クーポンの有効/無効化
     *
     * @param Application $app
     * @param Request $request
     * @param $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function enable(Application $app, Request $request, $id)
    {

        $coupon = $app['eccube.plugin.coupon.repository.coupon']->find($id);

        if (!$coupon) {
            $app->addError('admin.coupon.notfound', 'admin');

            return $app->redirect($app->url('admin_coupon_list'));
        }

        // =============
        // 更新処理
        // =============
        $status = $app['eccube.plugin.coupon.service.coupon']->enableCoupon($id);
        if ($status) {
            $app->addSuccess('admin.plugin.coupon.enable.success', 'admin');
        } else {
            $app->addError('admin.coupon.notfound', 'admin');
        }

        return $app->redirect($app->url('admin_coupon_list'));

    }

    /**
     * クーポンの削除
     *
     * @param Application $app
     * @param Request $request
     * @param $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function delete(Application $app, Request $request, $id)
    {

        $coupon = $app['eccube.plugin.coupon.repository.coupon']->find($id);

        if (!$coupon) {
            $app->addError('admin.coupon.notfound', 'admin');

            return $app->redirect($app->url('admin_coupon_list'));
        }

        // クーポン削除時のtokenで使用
        $form = $app['form.factory']->createBuilder('admin_coupon_search')->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $service = $app['eccube.plugin.coupon.service.coupon'];

            // クーポン情報を削除する
            if ($service->deleteCoupon($id)) {
                $app->addSuccess('admin.plugin.coupon.delete.success', 'admin');
            } else {
                $app->addError('admin.coupon.notfound', 'admin');
            }
        } else {
            $app->addError('admin.plugin.coupon.delete.error', 'admin');
        }

        return $app->redirect($app->url('admin_coupon_list'));

    }

    /**
     * 編集画面用のrender
     *
     * @param Application $app
     * @param array $parameters
     * @return Response
     */
    protected function renderRegistView(Application $app, $parameters = array())
    {
        // 商品検索フォーム
        $searchProductModalForm = $app['form.factory']->createBuilder('admin_search_product')->getForm();
        // カテゴリ検索フォーム
        $searchCategoryModalForm = $app['form.factory']->createBuilder('admin_coupon_search_category')->getForm();
        $viewParameters = array(
            'searchProductModalForm' => $searchProductModalForm->createView(),
            'searchCategoryModalForm' => $searchCategoryModalForm->createView(),
        );
        $viewParameters += $parameters;

        return $app->render('Coupon/View/admin/regist.twig', $viewParameters);
    }


    /**
     * クーポン入力、登録画面
     *
     * @param Application $app
     * @param Request $request
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

        $form = $app['form.factory']->createBuilder('shopping_coupon')->getForm();

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
            $service = $app['eccube.plugin.coupon.service.coupon'];

            $formCouponCd = $form->get('coupon_cd')->getData();

            // ----------------------------------
            // クーポンコード入力項目追加
            // ----------------------------------

            if ($formCouponCd == $couponCd) {
                // 画面上のクーポンコードと既に登録済みのクーポンコードが同一の場合、何もしない
                return $app->redirect($app->url('shopping'));
            }

            if (empty($formCouponCd) && $couponCd) {
                // 画面上のクーポンコードが入力されておらず、既にクーポンコードが登録されていればクーポンを無効にする

                $this->removeCouponOrder($Order, $app);

                return $app->redirect($app->url('shopping'));

            } else {
                // クーポンコードが入力されている

                $discount = 0;
                $error = false;

                if ($app->isGranted('ROLE_USER')) {
                    $Customer = $app->user();
                } else {
                    $Customer = $app['eccube.service.shopping']->getNonMember($this->sessionKey);
                }

                // クーポン情報を取得
                $Coupon = $app['eccube.plugin.coupon.repository.coupon']->findActiveCoupon($formCouponCd);

                if ($Coupon) {

                    // 既に登録済みのクーポンコードを一旦削除
                    $this->removeCouponOrder($Order, $app);

                    // 値引き額を取得
                    $discount = $service->recalcOrder($Order, $Coupon);

                    // 対象クーポンが存在しているかチェック
                    $existCoupon = $service->existsCouponProduct($Coupon, $Order);
                    if (!$existCoupon) {
                        $form->get("coupon_cd")->addError(new FormError('front.plugin.coupon.shopping.notexists'));
                        $error = true;
                    }

                    // クーポンが既に利用されているかチェック
                    $couponUsedOrNot = $this->checkCouponUsedOrNot($formCouponCd, $Customer, $app);
                    if ($couponUsedOrNot && $existCoupon) {
                        // 既に存在している
                        $form->get("coupon_cd")->addError(new FormError('front.plugin.coupon.shopping.sameuser'));
                        $error = true;
                    }

                    // クーポンの利用回数チェック
                    $checkCouponUseTime = $this->checkCouponUseTime($formCouponCd, $app);
                    if (!$checkCouponUseTime && $existCoupon) {
                        $form->get("coupon_cd")->addError(new FormError('front.plugin.coupon.shopping.couponusetime'));
                        $error = true;
                    }

                    // 合計金額より値引き額の方が高いかチェック
                    if ($Order->getTotal() <= $discount && $existCoupon) {
                        $form->get("coupon_cd")->addError(new FormError('front.plugin.coupon.shopping.minus'));
                        $error = true;
                    }
                } else {
                    //              $this->removeCouponOrder($Order, $app);
                    $form->get("coupon_cd")->addError(new FormError('front.plugin.coupon.shopping.notexists'));
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
                        $Coupon = $app['eccube.plugin.coupon.repository.coupon']->findActiveCoupon($couponCd);

                        if ($Coupon) {

                            // 値引き額を取得
                            $discount = $service->recalcOrder($Order, $Coupon);

                            // クーポン情報を登録
                            $this->setCouponOrder($Order, $Coupon, $couponCd, $Customer, $discount, $app);

                        }

                    }

                }


            }
        }

        return $app->render('Coupon/View/shopping_coupon.twig', array(
            'form' => $form->createView(),
            'Order' => $Order,
        ));


    }

    /**
     *  ユーザはクーポン1回のみ利用できる
     *
     * @param $couponCd
     * @param Customer $Customer
     * @param Application $app
     * @return bool
     */
    private function checkCouponUsedOrNot($couponCd, Customer $Customer, Application $app)
    {
        $repository = $app['eccube.plugin.coupon.repository.coupon_order'];

        if ($app->isGranted('ROLE_USER')) {
            $result = $repository->findUseCouponMember($couponCd, $Customer->getId());
        } else {
            $result = $repository->findUseCouponNonMember($couponCd, $Customer->getEmail());
        }

        if (!$result) {
            return false;
        }

        return true;
    }

    /**
     *  クーポンの利用回数のチェック
     *
     * @param $couponCd
     * @param Application $app
     * @return bool
     */
    private function checkCouponUseTime($couponCd, Application $app)
    {
        $Coupon = $app['eccube.plugin.coupon.repository.coupon']->findOneBy(array('coupon_cd' => $couponCd));

        if ($Coupon) {
            $count = $app['eccube.plugin.coupon.repository.coupon_order']->countCouponByCd($couponCd);
            if ($Coupon->getCouponUseTime() <= $count['1'] || $Coupon->getCouponUseTime() <= 0) {
                return false;
            }
        }

        return true;
    }


    /**
     * クーポン情報に登録
     *
     * @param Order $Order
     * @param CouponCoupon $Coupon
     * @param $couponCd
     * @param Customer $Customer
     * @param $discount
     * @param Application $app
     */
    private function setCouponOrder(Order $Order, CouponCoupon $Coupon, $couponCd, Customer $Customer, $discount, Application $app)
    {

        $total = $Order->getTotal() - $discount;
        $Order->setDiscount($Order->getDiscount() + $discount);
        $Order->setTotal($total);
        $Order->setPaymentTotal($total);
        // クーポン受注情報を保存する
        $app['eccube.plugin.coupon.service.coupon']->saveCouponOrder($Order, $Coupon, $couponCd, $Customer, $discount);

        // 合計、値引きを再計算し、dtb_orderデータに登録する
        $app['orm.em']->flush($Order);

    }


    /**
     * クーポンコードが未入力または、クーポンコードを登録後に再度別のクーポンコードが設定された場合、
     * 既存のクーポンを情報削除
     *
     * @param Order $Order
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
            $app['orm.em']->flush($Order);
        }
    }


}
