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

namespace Plugin\Coupon44\Controller\Admin;

use Eccube\Controller\AbstractController;
use Eccube\Entity\Category;
use Eccube\Repository\CategoryRepository;
use Eccube\Repository\ProductRepository;
use Knp\Component\Pager\Pagination\SlidingPagination;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Class CouponSearchModelController.
 */
class CouponSearchModelController extends AbstractController
{
    /**
     * CouponSearchModelController constructor.
     *
     * @param CategoryRepository $categoryRepository
     * @param ProductRepository $productRepository
     */
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly ProductRepository $productRepository,
        private readonly PaginatorInterface $paginator,
    ) {
    }

    /**
     * search product modal.
     *
     * @param Request   $request
     * @param int|null  $page_no
     *
     * @return array<string, mixed>|null
     */
    #[Route(path: '/%eccube_admin_route%/plugin/coupon/search/product', name: 'plugin_coupon_search_product')]
    #[Route(path: '/%eccube_admin_route%/plugin/coupon/search/product/page/{page_no}', requirements: ['page_no' => '\d+'], name: 'plugin_coupon_search_product_page')]
    #[Template('@Coupon44/admin/search_product.twig')]
    public function searchProduct(Request $request, $page_no = null)
    {
        if (!$request->isXmlHttpRequest()) {
            return null;
        }

        $pageCount = $this->eccubeConfig['eccube_default_page_count'];
        $session = $this->session;
        if ('POST' === $request->getMethod()) {
            log_info('get search data with parameters ', ['id' => $request->get('id'), 'category_id' => $request->get('category_id')]);
            $page_no = 1;
            $searchData = [
                'id' => $request->get('id'),
            ];
            if ($categoryId = $request->get('category_id')) {
                if (!$this->isValidId($categoryId)) {
                    throw new BadRequestHttpException();
                }
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
            // セッションに残った以前の検索条件も、不正な ID なら条件から外す
            $searchData['category_id'] = $this->isValidId($searchData['category_id'])
                ? $this->categoryRepository->find($searchData['category_id'])
                : null;
        }

        $qb = $this->productRepository->getQueryBuilderBySearchDataForAdmin($searchData);
        // 除外するproduct_idを設定する
        $existProductIds = $this->parseIds($request->get('exist_product_id'));
        if (count($existProductIds) > 0) {
            $qb->andWhere($qb->expr()->notin('p.id', ':existProductId'))
                ->setParameter('existProductId', $existProductIds);
        }

        /** @var SlidingPagination<int, mixed> $pagination */
        $pagination = $this->paginator->paginate(
            $qb,
            $page_no,
            $pageCount,
            ['wrap-queries' => true]
        );

        return [
            'pagination' => $pagination,
        ];
    }

    /**
     * カテゴリ検索画面を表示する.
     *
     * @param Request     $request
     *
     * @return array<string, mixed>
     */
    #[Route(path: '/%eccube_admin_route%/plugin/coupon/search/category', name: 'plugin_coupon_search_category')]
    #[Template('@Coupon44/admin/search_category.twig')]
    public function searchCategory(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $categoryId = $request->get('category_id');
            $existCategoryId = $request->get('exist_category_id');

            $existCategoryIds = [0];
            if (is_string($existCategoryId) && strlen($existCategoryId) > 0) {
                $existCategoryIds = explode(',', $existCategoryId);
            }

            if (empty($categoryId)) {
                $categoryId = 0;
            } elseif (!$this->isValidId($categoryId)) {
                throw new BadRequestHttpException();
            }

            $Category = $this->categoryRepository->find($categoryId);
            $Categories = $this->categoryRepository->getList($Category);

            if (empty($Categories)) {
                log_info('search category not found.');
            }

            // カテゴリーの一覧を作成する
            $list = [];
            if ($categoryId != 0 && !in_array($categoryId, $existCategoryIds)) {
                $name = $Category->getName();
                $list += [$Category->getId() => $name];
            }
            $list += $this->getCategoryList($Categories, $existCategoryIds);

            return [
                'Categories' => $list,
            ];
        }

        return [];
    }

    /**
     * カテゴリーの一覧を作成する.
     *
     * @param iterable<Category> $Categories
     * @param array<int, mixed>  $existCategoryIds
     *
     * @return array<int, string>
     */
    protected function getCategoryList(iterable $Categories, array $existCategoryIds): array
    {
        $result = [];
        foreach ($Categories as $Category) {
            // 除外IDがない場合は配列に値を追加する
            if (count($existCategoryIds) == 0 || !in_array($Category->getId(), $existCategoryIds)) {
                $name = $this->getCategoryFullName($Category);
                $result += [$Category->getId() => $name];
            }
            // 子カテゴリがあれば更に一覧を作成する
            if (count($Category->getChildren()) > 0) {
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
    protected function getCategoryFullName(Category $Category): string
    {
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

    /**
     * 整数の ID として扱える値か (DB の integer の範囲に収まる正の整数).
     */
    private function isValidId(mixed $value): bool
    {
        if (!is_int($value) && !is_string($value)) {
            return false;
        }

        return preg_match('/\A[1-9][0-9]{0,9}\z/', (string) $value) === 1 && (int) $value <= 2147483647;
    }

    /**
     * カンマ区切りの ID を配列にする. 空の要素は読み飛ばし、不正な ID を含む場合は 400 を返す.
     *
     * @return array<int, int>
     */
    private function parseIds(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }
        if (!is_string($value)) {
            throw new BadRequestHttpException();
        }

        $ids = [];
        foreach (explode(',', $value) as $id) {
            // 末尾のカンマなどによる空の要素は読み飛ばす
            if ($id === '') {
                continue;
            }
            if (!$this->isValidId($id)) {
                throw new BadRequestHttpException();
            }
            $ids[] = (int) $id;
        }

        return $ids;
    }
}
