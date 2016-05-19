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

namespace Plugin\Coupon;

use Eccube\Entity\Master\DeviceType;
use Eccube\Entity\PageLayout;
use Eccube\Plugin\AbstractPluginManager;

class PluginManager extends AbstractPluginManager
{

    public function install($config, $app)
    {
        $this->migrationSchema($app, __DIR__.'/Migration', $config['code']);
    }

    public function uninstall($config, $app)
    {
        // uninstall時はdisableも同時に動く

        $this->migrationSchema($app, __DIR__.'/Migration', $config['code'], 0);

    }

    public function enable($config, $app)
    {

        // pagelayoutの作成
        $this->createPageLayout($app);

    }

    public function disable($config, $app)
    {

        // pagelayoutの削除
        $this->removePageLayout($app);

    }

    public function update($config, $app)
    {

        // pagelayoutの作成
        $this->createPageLayout($app);

    }


    /**
     * クーポン用ページレイアウトを作成
     *
     * @param $app
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
        $PageLayout->setUrl('plugin_shopping_coupon');
        $PageLayout->setFileName('../../Plugin/Coupon/View/shopping_coupon');
        $PageLayout->setMetaRobots('noindex');

        // DB登録
        $app['orm.em']->persist($PageLayout);
        $app['orm.em']->flush($PageLayout);

    }


    /**
     * クーポン用ページレイアウトを削除
     *
     * @param $app
     * @throws \Exception
     */
    private function removePageLayout($app)
    {
        // ページ情報の削除
        $PageLayout = $app['eccube.repository.page_layout']->findOneBy(array('url' => 'plugin_shopping_coupon'));

        if ($PageLayout) {
            // Blockの削除
            $app['orm.em']->remove($PageLayout);
            $app['orm.em']->flush($PageLayout);
        }

    }

}
