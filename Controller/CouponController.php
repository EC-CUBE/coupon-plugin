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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception as HttpException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Plugin\Coupon\Entity\CouponCoupon;
use Symfony\Component\Validator\Constraints as Assert;

class CouponController
{

    private $main_title;

    private $sub_title;

    public function __construct()
    {}

    /**
    * 納品書の設定画面表示.
    * @param Application $app
    * @param Request $request
    * @param unknown $id
    * @throws NotFoundHttpException
    * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
    */
    public function index(Application $app, Request $request)
    {
        // リクエストGETで良ければ削除
        // // POSTでない場合は終了する
        // if ('POST' !== $request->getMethod()) {
        // throw new BadRequestHttpException();
        // }
        $searchForm = $app['form.factory']->createBuilder('admin_coupon_search')->getForm();
        $pagination = null;

        $pagination = $app['eccube.plugin.coupon.repository.coupon']->findAll();

        return $app->render('Coupon/View/admin/index.twig', array(
            'searchForm' => $searchForm->createView(),
            'pagination' => $pagination,
            'totalItemCount' => count($pagination)
        ));
    }

    /**
    * クーポンの新規作成
    * @param Application $app
    * @param Request $request
    * @param unknown $id
    * @throws NotFoundHttpException
    * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
    */
    public function create(Application $app, Request $request, $id) {

        $form = $app['form.factory']->createBuilder('admin_coupon')->getForm();

        // サービスの取得
        $service = $app['eccube.plugin.coupon.service.coupon'];

        // クーポンコードの発行
        $form->get('coupon_cd')->setData($service->generateCouponCd());

        return $this->renderRegistView($app, array('form' => $form->createView()));
    }

    /**
     * 編集
     * @param Application $app
     * @param Request $request
     * @param unknown $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function edit(Application $app, Request $request, $id) {
        $searchForm = $app['form.factory']->createBuilder('admin_coupon_search')->getForm();
        $searchForm->handleRequest($request);

        if (!'POST' === $request->getMethod()) {
            throw new HttpException();
        }
        if(is_null($id) || strlen($id) == 0) {
            $app->addError("admin.coupon.coupon_id.notexists", "admin");
            return $app->redirect($app->url('admin_coupon_list'));
        }

        // IDからクーポン情報を取得する
        $coupon = $app['eccube.plugin.coupon.repository.coupon']->find($id);
        if (is_null($coupon)) {
            $app->addError('admin.coupon.notfound', 'admin');
            return $app->redirect($app->url('admin_coupon_list'));
        }

        // formの作成
        $form = $app['form.factory']
            ->createBuilder('admin_coupon')
            ->getForm();

        $form->get('id')->setData($coupon->getId());
        $form->get('coupon_cd')->setData($coupon->getCouponCd());
        $form->get('coupon_name')->setData($coupon->getCouponName());
        $form->get('coupon_type')->setData($coupon->getCouponType());
        $form->get('discount_type')->setData($coupon->getDiscountType());
        $form->get('discount_price')->setData($coupon->getDiscountPrice());
        $form->get('discount_rate')->setData($coupon->getDiscountRate());

        $form->get('available_from_date')->setData($coupon->getAvailableFromDate());
        $form->get('available_to_date')->setData($coupon->getAvailableToDate());

        $form->get('CouponDetails')->setData($coupon->getCouponDetails());

        return $this->renderRegistView($app, array('form' => $form->createView()));
    }

    /**
    * クーポンの新規作成/編集確定
    * @param Application $app
    * @param Request $request
    * @param unknown $id
    * @throws NotFoundHttpException
    * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
    */
    public function commit(Application $app, Request $request, $id) {
        if (!'POST' === $request->getMethod()) {
            throw new HttpException();
        }

        $builder = $app['form.factory']->createBuilder('admin_coupon', null);
        $form = $builder->getForm();
        $form->handleRequest($request);
        $data = $form->getData();

        $discountType = $data['discount_type'];

        $form = $builder->getForm();

        // ------------------------------------------------
        // validationを付与するため項目を削除、追加する
        // ------------------------------------------------
        if($discountType == 1) {
            // 値引き額
            $form->remove('discount_price');
            $form->add('discount_price', 'money', array(
                'label' => '値引き額',
                'required' => true,
                'currency' => 'JPY',
                'precision' => 0,
                'constraints' => array(
                    new Assert\NotBlank(),
                )
            ));
        } else if($discountType == 2) {
            // 値引率
            $form->remove('discount_rate');
            $form->add('discount_rate', 'integer', array(
                'label' => '値引率',
                'required' => true,
                'constraints' => array(
                    new Assert\NotBlank(),
                    new Assert\Range(array(
                        'min' => 0,
                        'max' => 100,
                    ))
                )
            ));
        }

        $form->handleRequest($request);

        if (!$form->isValid()) {
            return $this->renderRegistView($app, array('form' => $form->createView()));
        }

        $data = $form->getData();

        // サービスの取得
        // @var \Plugin\Coupon\Service\CouponService
        $service = $app['eccube.plugin.coupon.service.coupon'];

        if(is_null($data['id'])) {
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

    /**
    * クーポンの有効/無効化
    * @param Application $app
    * @param Request $request
    * @param unknown $id
    * @throws NotFoundHttpException
    * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
    */
    public function enable(Application $app, Request $request, $id) {
        if (!'POST' === $request->getMethod()) {
            throw new HttpException();
        }
        if(is_null($id) || strlen($id) == 0) {
            $app->addError("admin.coupon.coupon_id.notexists", "admin");
            return $app->redirect($app->url('admin_coupon_list'));
        }

        // formの作成
        $form = $app['form.factory']->createBuilder('admin_coupon_search')->getForm();

        // =============
        // 更新処理
        // =============
        $service = $app['eccube.plugin.coupon.service.coupon'];
        $status = $service->enableCoupon($id);
        if ($status) {
            $app->addSuccess('admin.plugin.coupon.enable.success', 'admin');
        } else{
            $app->addError('admin.coupon.notfound', 'admin');
        }

        return $app->redirect($app->url('admin_coupon_list'));

    }

    /**
    * クーポンの削除
    * @param Application $app
    * @param Request $request
    * @param unknown $id
    * @throws NotFoundHttpException
    * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
    */
    public function delete(Application $app, Request $request, $id) {
        if (!'POST' === $request->getMethod()) {
            throw new HttpException();
        }
        if(is_null($id) || strlen($id) == 0) {
            $app->addError("admin.coupon.coupon_id.notexists", "admin");
            return $app->redirect($app->url('admin_coupon_list'));
        }


        $form = $app['form.factory']->createBuilder('admin_coupon_search')->getForm();
        $service = $app['eccube.plugin.coupon.service.coupon'];

        // クーポン情報を削除する
        if($service->deleteCoupon($id)) {
            $app->addSuccess('admin.plugin.coupon.delete.success', 'admin');
        } else{
            $app->addError('admin.coupon.notfound', 'admin');
        }

        return $app->redirect($app->url('admin_coupon_list'));

    }

    /**
     * 編集画面用のrender
     * @param unknown $app
     * @param unknown $parameters
     */
    protected function renderRegistView($app, $parameters = array()) {
        // 商品検索フォーム
        $searchProductModalForm = $app['form.factory']->createBuilder('admin_search_product')->getForm();
        // カテゴリ検索フォーム
        $searchCategoryModalForm = $app['form.factory']->createBuilder('admin_coupon_search_category')->getForm();
        $viewParameters = array(
            'searchProductModalForm' => $searchProductModalForm->createView(),
            'searchCategoryModalForm' => $searchCategoryModalForm->createView(),
        );
        $viewParameters+= $parameters;
        return $app->render('Coupon/View/admin/regist.twig', $viewParameters);
    }

}
