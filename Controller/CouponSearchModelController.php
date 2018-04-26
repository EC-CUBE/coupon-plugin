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

use Eccube\Controller\AbstractController;
use Eccube\Entity\Category;
use Eccube\Repository\CategoryRepository;
use Eccube\Repository\ProductRepository;
use Knp\Component\Pager\Paginator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CouponSearchModelController.
 */
class CouponSearchModelController extends AbstractController
{
    /**
     * @var CategoryRepository
     */
    private $categoryRepository;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * CouponSearchModelController constructor.
     * @param CategoryRepository $categoryRepository
     * @param ProductRepository $productRepository
     */
    public function __construct(CategoryRepository $categoryRepository, ProductRepository $productRepository)
    {
        $this->categoryRepository = $categoryRepository;
        $this->productRepository = $productRepository;
    }


    /**
     * search product modal.
     *
     * @param Request   $request
     * @param int       $page_no
     * @param Paginator $paginator
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/%eccube_admin_route%/plugin/coupon/search/product", name="plugin_coupon_search_product")
     * @Route("/%eccube_admin_route%/plugin/coupon/search/product/page/{page_no}", requirements={"page_no" = "\d+"}, name="plugin_coupon_search_product_page")
     */
    public function searchProduct(Request $request, $page_no = null, Paginator $paginator)
    {
        if (!$request->isXmlHttpRequest()) {
            return null;
        }

        $pageCount = $this->eccubeConfig['eccube_default_page_count'];
        $session = $this->session;
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
            $searchData['category_id'] = $this->categoryRepository->find($searchData['category_id']);
        }

        $qb = $this->productRepository->getQueryBuilderBySearchDataForAdmin($searchData);
        // 除外するproduct_idを設定する
        $existProductId = $request->get('exist_product_id');
        if (strlen($existProductId > 0)) {
            $qb->andWhere($qb->expr()->notin('p.id', ':existProductId'))
                ->setParameter('existProductId', explode(',', $existProductId));
        }

        /** @var \Knp\Component\Pager\Pagination\SlidingPagination $pagination */
        $pagination = $paginator->paginate(
            $qb,
            $page_no,
            $pageCount,
            array('wrap-queries' => true)
        );

        return $this->render('Coupon/Resource/template/admin/search_product.twig', array(
            'pagination' => $pagination,
        ));
    }

    /**
     * カテゴリ検索画面を表示する.
     *
     * @param Request     $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/%eccube_admin_route%/plugin/coupon/search/category", name="plugin_coupon_search_category")
     */
    public function searchCategory(Request $request)
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

            $Category = $this->categoryRepository->find($categoryId);
            $Categories = $this->categoryRepository->getList($Category);

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

            return $this->render('Coupon/Resource/template/admin/search_category.twig', array(
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
