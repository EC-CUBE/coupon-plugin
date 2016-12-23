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

namespace Plugin\Coupon\ServiceProvider;

use Silex\Application as BaseApplication;
use Silex\ServiceProviderInterface;
use Eccube\Common\Constant;
use Plugin\Coupon\Form\Type\CouponSearchType;
use Plugin\Coupon\Form\Type\CouponType;
use Plugin\Coupon\Form\Type\CouponDetailType;
use Plugin\Coupon\Form\Type\CouponSearchCategoryType;
use Plugin\Coupon\Form\Type\CouponUseType;
use Plugin\Coupon\Service\CouponService;

/**
 * Class CouponServiceProvider
 * @package Plugin\Coupon\ServiceProvider
 */
class CouponServiceProvider implements ServiceProviderInterface
{
    /**
     * @param BaseApplication $app
     */
    public function register(BaseApplication $app)
    {
        // 管理画面定義
        $admin = $app['controllers_factory'];
        // 強制SSL
        if ($app['config']['force_ssl'] == Constant::ENABLED) {
            $admin->requireHttps();
        }

        // クーポンの一覧
        $admin->match('/plugin/coupon', 'Plugin\Coupon\Controller\CouponController::index')->value('id', null)->assert('id', '\d+|')->bind('admin_plugin_coupon_list');
        // クーポンの新規登録
        $admin->match('/plugin/coupon/new', 'Plugin\Coupon\Controller\CouponController::edit')->value('id', null)->bind('admin_plugin_coupon_new');
        // クーポンの編集
        $admin->match('/plugin/coupon/{id}/edit', 'Plugin\Coupon\Controller\CouponController::edit')->value('id', null)->assert('id', '\d+|')->bind('admin_plugin_coupon_edit');
        // クーポンの有効/無効化
        $admin->match('/plugin/coupon/{id}/enable', 'Plugin\Coupon\Controller\CouponController::enable')->value('id', null)->assert('id', '\d+|')->bind('admin_plugin_coupon_enable');
        // クーポンの削除
        $admin->match('/plugin/coupon/{id}/delete', 'Plugin\Coupon\Controller\CouponController::delete')->value('id', null)->assert('id', '\d+|')->bind('admin_plugin_coupon_delete');
        // 商品検索画面表示
        $admin->post('/plugin/coupon/search/product', 'Plugin\Coupon\Controller\CouponSearchModelController::searchProduct')->bind('admin_plugin_coupon_search_product');
        // カテゴリ検索画面表示
        $admin->post('/plugin/coupon/search/category', 'Plugin\Coupon\Controller\CouponSearchModelController::searchCategory')->bind('admin_plugin_coupon_search_category');
        $admin->match('/plugin/shopping/shopping_coupon', 'Plugin\Coupon\Controller\CouponController::shoppingCoupon')->bind('front_plugin_shopping_coupon');

        $app->mount('/'.trim($app['config']['admin_route'], '/').'/', $admin);

        // Formの登録
        $app['form.types'] = $app->share($app->extend('form.types', function ($types) use ($app) {
            $types[] = new CouponSearchType();
            $types[] = new CouponType($app);
            $types[] = new CouponDetailType($app);
            $types[] = new CouponSearchCategoryType();
            $types[] = new CouponUseType();

            return $types;
        }));

        // クーポン情報テーブルリポジトリ
        $app['eccube.plugin.coupon.repository.coupon'] = $app->share(function () use ($app) {
            return $app['orm.em']->getRepository('Plugin\Coupon\Entity\CouponCoupon');
        });
        // クーポン詳細情報テーブルリポジトリ
        $app['eccube.plugin.coupon.repository.coupon_detail'] = $app->share(function () use ($app) {
            return $app['orm.em']->getRepository('Plugin\Coupon\Entity\CouponCouponDetail');
        });
        // 受注クーポン情報テーブルリポジトリ
        $app['eccube.plugin.coupon.repository.coupon_order'] = $app->share(function () use ($app) {
            return $app['orm.em']->getRepository('Plugin\Coupon\Entity\CouponCouponOrder');
        });

        // -----------------------------
        // サービスの登録
        // -----------------------------
        $app['eccube.plugin.coupon.service.coupon'] = $app->share(function () use ($app) {
            return new CouponService($app);
        });

        // メッセージ登録
        $file = __DIR__.'/../Resource/locale/message.'.$app['locale'].'.yml';
        $app['translator']->addResource('yaml', $file, $app['locale']);

        // ============================================================
        // メニュー登録
        // ============================================================
        $app['config'] = $app->share($app->extend('config', function ($config) {
            $addNavi['id'] = "admin_plugin_coupon";
            $addNavi['name'] = "クーポン";
            $addNavi['url'] = "admin_plugin_coupon_list";

            $nav = $config['nav'];
            foreach ($nav as $key => $val) {
                if ("order" == $val["id"]) {
                    $nav[$key]['child'][] = $addNavi;
                }
            }
            $config['nav'] = $nav;

            return $config;
        }));

        // initialize logger (for 3.0.0 - 3.0.8)
        if (!method_exists('Eccube\Application', 'getInstance')) {
            eccube_log_init($app);
        }
    }

    /**
     * @param BaseApplication $app
     */
    public function boot(BaseApplication $app)
    {
    }

}
