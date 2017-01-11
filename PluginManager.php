<?php
/*
 * This file is part of the Coupon plugin
 *
 * Copyright (C) 2016 LOCKON CO.,LTD. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\Coupon;

use Eccube\Application;
use Eccube\Plugin\AbstractPluginManager;
use Eccube\Entity\Master\DeviceType;
use Eccube\Entity\PageLayout;

/**
 * Class PluginManager.
 */
class PluginManager extends AbstractPluginManager
{
    /**
     * @param array       $config
     * @param Application $app
     */
    public function install($config, $app)
    {
    }

    /**
     * @param array       $config
     * @param Application $app
     */
    public function uninstall($config, $app)
    {
        $this->migrationSchema($app, __DIR__.'/Resource/doctrine/migration', $config['code'], 0);
    }

    /**
     * @param array       $config
     * @param Application $app
     */
    public function enable($config, $app)
    {
        $this->migrationSchema($app, __DIR__.'/Resource/doctrine/migration', $config['code']);
        $PageLayout = $app['eccube.repository.page_layout']->findOneBy(array('url' => 'plugin_coupon_shopping'));
        if (is_null($PageLayout)) {
            // pagelayoutの作成
            $this->createPageLayout($app);
        }
    }

    /**
     * @param array       $config
     * @param Application $app
     */
    public function disable($config, $app)
    {
        // pagelayoutの削除
        $this->removePageLayout($app);
    }

    /**
     * @param array       $config
     * @param Application $app
     */
    public function update($config, $app)
    {
        $this->migrationSchema($app, __DIR__.'/Resource/doctrine/migration', $config['code']);
        $PageLayout = $app['eccube.repository.page_layout']->findOneBy(array('url' => 'plugin_coupon_shopping'));
        if (is_null($PageLayout)) {
            // pagelayoutの作成
            $this->createPageLayout($app);
        }
    }

    /**
     * クーポン用ページレイアウトを作成.
     *
     * @param $app
     *
     * @throws \Exception
     */
    private function createPageLayout($app)
    {
        // ページレイアウトにプラグイン使用時の値を代入
        $DeviceType = $app['eccube.repository.master.device_type']->find(DeviceType::DEVICE_TYPE_PC);
        /** @var \Eccube\Entity\PageLayout $PageLayout */
        $PageLayout = $app['eccube.repository.page_layout']->findOrCreate(null, $DeviceType);
        $PageLayout->setEditFlg(PageLayout::EDIT_FLG_DEFAULT);
        $PageLayout->setName('商品購入/クーポン利用');
        $PageLayout->setUrl('plugin_coupon_shopping');
        $PageLayout->setFileName('../../Plugin/Coupon/Resource/template/default/shopping_coupon');
        $PageLayout->setMetaRobots('noindex');
        // DB登録
        $app['orm.em']->persist($PageLayout);
        $app['orm.em']->flush($PageLayout);
    }
    /**
     * クーポン用ページレイアウトを削除.
     *
     * @param $app
     *
     * @throws \Exception
     */
    private function removePageLayout($app)
    {
        // ページ情報の削除
        $PageLayout = $app['eccube.repository.page_layout']->findOneBy(array('url' => 'plugin_coupon_shopping'));
        if ($PageLayout) {
            // Blockの削除
            $app['orm.em']->remove($PageLayout);
            $app['orm.em']->flush($PageLayout);
        }
    }
}
