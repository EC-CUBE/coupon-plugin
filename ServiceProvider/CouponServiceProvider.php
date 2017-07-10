<?php
/*
 * This file is part of the Coupon plugin
 *
 * Copyright (C) 2016 LOCKON CO.,LTD. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
use Plugin\Coupon\Event\Event;
use Plugin\Coupon\Event\EventLegacy;

// include log functions (for 3.0.0 - 3.0.11)
require_once __DIR__.'/../log.php';

/**
 * Class CouponServiceProvider.
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
        //Frontend
        $front = $app['controllers_factory'];
        // 強制SSL
        if ($app['config']['force_ssl'] == Constant::ENABLED) {
            $admin->requireHttps();
            $front->requireHttps();
        }

        // クーポンの一覧
        $admin->match('/plugin/coupon', 'Plugin\Coupon\Controller\CouponController::index')->value('id', null)->assert('id', '\d+|')->bind('plugin_coupon_list');
        // クーポンの新規登録
        $admin->match('/plugin/coupon/new', 'Plugin\Coupon\Controller\CouponController::edit')->value('id', null)->bind('plugin_coupon_new');
        // クーポンの編集
        $admin->match('/plugin/coupon/{id}/edit', 'Plugin\Coupon\Controller\CouponController::edit')->value('id', null)->assert('id', '\d+|')->bind('plugin_coupon_edit');
        // クーポンの有効/無効化
        $admin->match('/plugin/coupon/{id}/enable', 'Plugin\Coupon\Controller\CouponController::enable')->value('id', null)->assert('id', '\d+|')->bind('plugin_coupon_enable');
        // クーポンの削除
        $admin->delete('/plugin/coupon/{id}/delete', 'Plugin\Coupon\Controller\CouponController::delete')->value('id', null)->assert('id', '\d+|')->bind('plugin_coupon_delete');
        //ajax link
        $admin->post('/plugin/coupon/save/delivery', 'Plugin\Coupon\Controller\CouponController::saveDelivery')->bind('plugin_coupon_save_delivery');
        // 商品検索画面表示
        $admin->post('/plugin/coupon/search/product', 'Plugin\Coupon\Controller\CouponSearchModelController::searchProduct')->bind('plugin_coupon_search_product');
        // カテゴリ検索画面表示
        $admin->post('/plugin/coupon/search/category', 'Plugin\Coupon\Controller\CouponSearchModelController::searchCategory')->bind('plugin_coupon_search_category');
        //product search dialog paginator
        $admin->match('/plugin/coupon/search/product/page/{page_no}', 'Plugin\Coupon\Controller\CouponSearchModelController::searchProduct')
            ->assert('page_no', '\d+')->bind('plugin_coupon_search_product_page');

        $app->mount('/'.trim($app['config']['admin_route'], '/').'/', $admin);

        $front->match('/plugin/coupon/shopping/shopping_coupon', 'Plugin\Coupon\Controller\CouponController::shoppingCoupon')->bind('plugin_coupon_shopping');
        $app->mount('', $front);

        // イベントの追加
        $app['eccube.plugin.coupon.event'] = $app->share(function () use ($app) {
            return new Event($app);
        });
        $app['eccube.plugin.coupon.event.legacy'] = $app->share(function () use ($app) {
            return new EventLegacy($app);
        });

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
        $app['coupon.repository.coupon'] = $app->share(function () use ($app) {
            return $app['orm.em']->getRepository('Plugin\Coupon\Entity\Coupon');
        });
        // クーポン詳細情報テーブルリポジトリ
        $app['coupon.repository.coupon_detail'] = $app->share(function () use ($app) {
            return $app['orm.em']->getRepository('Plugin\Coupon\Entity\CouponDetail');
        });
        // 受注クーポン情報テーブルリポジトリ
        $app['coupon.repository.coupon_order'] = $app->share(function () use ($app) {
            return $app['orm.em']->getRepository('Plugin\Coupon\Entity\CouponOrder');
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
            $addNavi['id'] = 'plugin_coupon';
            $addNavi['name'] = 'クーポン';
            $addNavi['url'] = 'plugin_coupon_list';

            $nav = $config['nav'];
            foreach ($nav as $key => $val) {
                if ('order' == $val['id']) {
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
