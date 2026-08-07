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

namespace Plugin\Coupon44;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Common\EccubeConfig;
use Eccube\Entity\Layout;
use Eccube\Entity\Page;
use Eccube\Entity\PageLayout;
use Eccube\Plugin\AbstractPluginManager;
use Psr\Container\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class PluginManager.
 */
class PluginManager extends AbstractPluginManager
{
    private string $originalDir = __DIR__.'/Resource/template/default/';

    private string $template1 = 'coupon_shopping_item.twig';

    private string $template2 = 'coupon_shopping_item_confirm.twig';

    private string $template3 = 'mypage_history_coupon.twig';

    /**
     * @param array<string, mixed> $meta
     * @param ContainerInterface $container
     */
    public function enable(array $meta, ContainerInterface $container): void
    {
        $this->copyBlock($container);
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine')->getManager();
        $PageLayout = $entityManager->getRepository(Page::class)->findOneBy(['url' => 'plugin_coupon_shopping']);
        if (is_null($PageLayout)) {
            // pagelayoutの作成
            $this->createPageLayout($container);
        }
    }

    /**
     * @param array<string, mixed> $meta
     * @param ContainerInterface $container
     */
    public function disable(array $meta, ContainerInterface $container): void
    {
        $this->removeBlock($container);
        // pagelayoutの削除
        $this->removePageLayout($container);
    }

    /**
     * @param array<string, mixed> $meta
     * @param ContainerInterface $container
     */
    public function update(array $meta, ContainerInterface $container): void
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine')->getManager();
        $PageLayout = $entityManager->getRepository(Page::class)->findOneBy(['url' => 'plugin_coupon_shopping']);
        if (is_null($PageLayout)) {
            // pagelayoutの作成
            $this->createPageLayout($container);
        }
    }

    /**
     * @param ContainerInterface $container
     */
    private function createPageLayout(ContainerInterface $container): void
    {
        // ページレイアウトにプラグイン使用時の値を代入
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine')->getManager();
        /** @var Page $Page */
        $Page = $entityManager->getRepository(Page::class)->newPage();
        $Page->setEditType(Page::EDIT_TYPE_DEFAULT);
        $Page->setName('商品購入/クーポン利用');
        $Page->setUrl('plugin_coupon_shopping');
        $Page->setFileName('Coupon44/Resource/template/default/shopping_coupon');
        $Page->setMetaRobots('noindex');

        // DB登録
        $entityManager->persist($Page);
        $entityManager->flush();

        $Layout = $entityManager->getRepository(Layout::class)->find(Layout::DEFAULT_LAYOUT_UNDERLAYER_PAGE);
        $PageLayout = new PageLayout();
        $PageLayout->setPage($Page)
            ->setPageId($Page->getId())
            ->setLayout($Layout)
            ->setLayoutId($Layout->getId())
            ->setSortNo(0);

        $entityManager->persist($PageLayout);
        $entityManager->flush();
    }

    /**
     * クーポン用ページレイアウトを削除.
     *
     * @param ContainerInterface $container
     */
    private function removePageLayout(ContainerInterface $container): void
    {
        // ページ情報の削除
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine')->getManager();
        $Page = $entityManager->getRepository(Page::class)->findOneBy(['url' => 'plugin_coupon_shopping']);
        if ($Page) {
            $Layout = $entityManager->getRepository(Layout::class)->find(Layout::DEFAULT_LAYOUT_UNDERLAYER_PAGE);
            $PageLayout = $entityManager->getRepository(PageLayout::class)->findOneBy(['Page' => $Page, 'Layout' => $Layout]);
            // Blockの削除
            $entityManager->remove($PageLayout);
            $entityManager->remove($Page);
            $entityManager->flush();
        }
    }

    /**
     * Copy block template.
     *
     * @param ContainerInterface $container
     */
    private function copyBlock(ContainerInterface $container): void
    {
        $templateDir = $container->get(EccubeConfig::class)->get('eccube_theme_front_dir');
        // ファイルコピー
        $file = new Filesystem();
        // ブロックファイルをコピー
        $file->copy($this->originalDir.$this->template1, $templateDir.'/Coupon44/'.$this->template1);
        $file->copy($this->originalDir.$this->template2, $templateDir.'/Coupon44/'.$this->template2);
        $file->copy($this->originalDir.$this->template3, $templateDir.'/Coupon44/'.$this->template3);
    }

    /**
     * Remove block template.
     *
     * @param ContainerInterface $container
     */
    private function removeBlock(ContainerInterface $container): void
    {
        $templateDir = $container->get(EccubeConfig::class)->get('eccube_theme_front_dir');
        $file = new Filesystem();
        $file->remove($templateDir.'/Coupon44/'.$this->template1);
        $file->remove($templateDir.'/Coupon44/'.$this->template2);
        $file->remove($templateDir.'/Coupon44/'.$this->template3);
    }
}
