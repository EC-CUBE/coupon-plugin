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
use Eccube\Entity\Category;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CouponSearchModelController.
 */
class CouponSearchModelController
{
    /**
     * search product modal.
     *
     * @param Application $app
     * @param Request     $request
     * @param int         $page_no
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function searchProduct(Application $app, Request $request, $page_no = null)
    {
        if (!$request->isXmlHttpRequest()) {
            return null;
        }

        $pageCount = $app['config']['default_page_count'];
        $session = $app['session'];
        if ('POST' === $request->getMethod()) {
            log_info('get search data with parameters ', array('id' => $request->get('id'), 'category_id' => $request->get('category_id')));
            $page_no = 1;
            $searchData = array(
                'id' => $request->get('id'),
            );
            if ($categoryId = $request->get('category_id')) {
                $searchData['category_id'] = $categoryId;
            }
            $session->set('eccube.plugin.coupon.product.search', $searchData);
            $session->set('eccube.plugin.coupon.product.search.page_no', $page_no);
        } else {
            $searchData = (array) $session->get('eccube.plugin.coupon.product.search');
            if (is_null($page_no)) {
                $page_no = intval($session->get('eccube.plugin.coupon.product.search.page_no'));
            } else {
                $session->set('eccube.plugin.coupon.product.search.page_no', $page_no);
            }
        }

        if (!empty($searchData['category_id'])) {
            $searchData['category_id'] = $app['eccube.repository.category']->find($searchData['category_id']);
        }

        $qb = $app['eccube.repository.product']->getQueryBuilderBySearchDataForAdmin($searchData);
        // 除外するproduct_idを設定する
        $existProductId = $request->get('exist_product_id');
        if (strlen($existProductId > 0)) {
            $qb->andWhere($qb->expr()->notin('p.id', ':existProductId'))
                ->setParameter('existProductId', explode(',', $existProductId));
        }

        /** @var \Knp\Component\Pager\Pagination\SlidingPagination $pagination */
        $pagination = $app['paginator']()->paginate(
            $qb,
            $page_no,
            $pageCount,
            array('wrap-queries' => true)
        );

        $paths = array();
        $paths[] = $app['config']['template_admin_realdir'];
        $app['twig.loader']->addLoader(new \Twig_Loader_Filesystem($paths));

        return $app->render('Coupon/Resource/template/admin/search_product.twig', array(
            'pagination' => $pagination,
        ));
    }

    /**
     * カテゴリ検索画面を表示する.
     *
     * @param Application $app
     * @param Request     $request
     *
     * @return \Symfony\Component\HttpFoundation\Response|null
     */
    public function searchCategory(Application $app, Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $categoryId = $request->get('category_id');
            $existCategoryId = $request->get('exist_category_id');

            $existCategoryIds = array(0);
            if (strlen($existCategoryId > 0)) {
                $existCategoryIds = explode(',', $existCategoryId);
            }

            if (empty($categoryId)) {
                $categoryId = 0;
            }

            $Category = $app['eccube.repository.category']->find($categoryId);
            $Categories = $app['eccube.repository.category']->getList($Category);

            if (empty($Categories)) {
                log_info('search category not found.');
            }

            // カテゴリーの一覧を作成する
            $list = array();
            if ($categoryId != 0 && !in_array($categoryId, $existCategoryIds)) {
                $name = $Category->getName();
                $list += array($Category->getId() => $name);
            }
            $list += $this->getCategoryList($Categories, $existCategoryIds);

            return $app->render('Coupon/Resource/template/admin/search_category.twig', array(
                'Categories' => $list,
            ));
        }

        return new Response();
    }

    /**
     * カテゴリーの一覧を作成する.
     *
     * @param Category $Categories
     * @param int      $existCategoryIds
     *
     * @return array
     */
    protected function getCategoryList($Categories, $existCategoryIds)
    {
        $result = array();
        foreach ($Categories as $Category) {
            // 除外IDがない場合は配列に値を追加する
            if (count($existCategoryIds) == 0 || !in_array($Category->getId(), $existCategoryIds)) {
                $name = $this->getCategoryFullName($Category);
                $result += array($Category->getId() => $name);
            }
            // 子カテゴリがあれば更に一覧を作成する
            if (count(($Category->getChildren())) > 0) {
                $childResult = $this->getCategoryList($Category->getChildren(), $existCategoryIds);
                $result += $childResult;
            }
        }

        return $result;
    }

    /**
     * 親カテゴリ名を含むカテゴリ名を取得する.
     *
     * @param Category $Category
     *
     * @return string
     */
    protected function getCategoryFullName(Category $Category)
    {
        if (is_null($Category)) {
            return '';
        }
        $fulName = $Category->getName();
        // 親カテゴリがない場合はカテゴリ名を返す.
        if (is_null($Category->getParent())) {
            return $fulName;
        }
        // 親カテゴリ名を結合する
        $ParentCategory = $Category->getParent();
        while (!is_null($ParentCategory)) {
            $fulName = $ParentCategory->getName().'　＞　'.$fulName;
            $ParentCategory = $ParentCategory->getParent();
        }

        return $fulName;
    }
}
