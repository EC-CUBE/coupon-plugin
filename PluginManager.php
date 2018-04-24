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
use Eccube\Entity\Layout;
use Eccube\Entity\Page;
use Eccube\Plugin\AbstractPluginManager;
use Eccube\Entity\Master\DeviceType;
use Eccube\Entity\PageLayout;
use Eccube\Repository\LayoutRepository;
use Eccube\Repository\Master\DeviceTypeRepository;
use Eccube\Repository\PageLayoutRepository;
use Eccube\Repository\PageRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class PluginManager.
 */
class PluginManager extends AbstractPluginManager
{
    public function enable($meta = null, Application $app = null, ContainerInterface $container)
    {
        $PageLayout = $container->get(PageRepository::class)->findOneBy(array('url' => 'plugin_coupon_shopping'));
        if (is_null($PageLayout)) {
            // pagelayoutの作成
            $this->createPageLayout($container);
        }
    }

    /**
     * @param null $meta
     * @param Application|null $app
     * @param ContainerInterface $container
     */
    public function disable($meta = null, Application $app = null, ContainerInterface $container)
    {
        // pagelayoutの削除
        $this->removePageLayout($container);
    }

    /**
     * @param null $meta
     * @param Application|null $app
     * @param ContainerInterface $container
     */
    public function update($meta = null, Application $app = null, ContainerInterface $container)
    {
        $PageLayout = $container->get(PageRepository::class)->findOneBy(array('url' => 'plugin_coupon_shopping'));
        if (is_null($PageLayout)) {
            // pagelayoutの作成
            $this->createPageLayout($container);
        }
    }

    /**
     * @param ContainerInterface $container
     */
    private function createPageLayout(ContainerInterface $container)
    {
        // ページレイアウトにプラグイン使用時の値を代入
        $DeviceType = $container->get(DeviceTypeRepository::class)->find(DeviceType::DEVICE_TYPE_PC);

        /** @var \Eccube\Entity\Page $Page */
        $Page = $container->get(PageRepository::class)->findOrCreate(null, $DeviceType);
        $Page->setEditType(Page::EDIT_TYPE_DEFAULT);
        $Page->setName('商品購入/クーポン利用');
        $Page->setUrl('plugin_coupon_shopping');
        $Page->setFileName('../../Plugin/Coupon/Resource/template/default/shopping_coupon');
        $Page->setMetaRobots('noindex');

        // DB登録
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $entityManager->persist($Page);
        $entityManager->flush($Page);

        $Layout = $container->get(LayoutRepository::class)->find(Layout::DEFAULT_LAYOUT_UNDERLAYER_PAGE);
        $PageLayout = new PageLayout();
        $PageLayout->setPage($Page)
            ->setPageId($Page->getId())
            ->setLayout($Layout)
            ->setLayoutId($Layout->getId())
            ->setSortNo(0);

        $entityManager->persist($PageLayout);
        $entityManager->flush($PageLayout);
    }

    /**
     * クーポン用ページレイアウトを削除.
     *
     * @param ContainerInterface $container
     */
    private function removePageLayout(ContainerInterface $container)
    {
        // ページ情報の削除
        $Page = $container->get(PageRepository::class)->findOneBy(array('url' => 'plugin_coupon_shopping'));
        if ($Page) {
            $Layout = $container->get(LayoutRepository::class)->find(Layout::DEFAULT_LAYOUT_UNDERLAYER_PAGE);
            $PageLayout = $container->get(PageLayoutRepository::class)->findOneBy(['Page' => $Page, 'Layout' => $Layout]);
            // Blockの削除
            $entityManager = $container->get('doctrine.orm.entity_manager');
            $entityManager->remove($PageLayout);
            $entityManager->remove($Page);
            $entityManager->flush($PageLayout);
            $entityManager->flush($Page);
        }
    }
}
