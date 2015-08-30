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
use Eccube\Common\Constant;

class CouponSearchModelController
{

    private $main_title;

    private $sub_title;

    public function __construct()
    {}

    /**
     * 商品検索画面を表示する
     * @param Application $app
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function searchProduct(Application $app, Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $app['monolog']->addDebug('search product start.');

            $searchData = array(
//                 'id' => $request->get('id'),
                'name' => $request->get('id'),
            );

            if ($categoryId = $request->get('category_id')) {
                $Category = $app['eccube.repository.category']->find($categoryId);
                $searchData['category_id'] = $Category;
            }

            /** @var $Products \Eccube\Entity\Product[] */
            $qb = $app['eccube.repository.product']->getQueryBuilderBySearchData($searchData);

            // 除外するproduct_idを設定する
            $existProductId = $request->get('exist_product_id');
            if(strlen($existProductId > 0)) {
                $qb->andWhere($qb->expr()->notin('p.id', ':existProductId'))
                    ->setParameter('existProductId', explode(",", $existProductId));
            }

            $Products = $qb->getQuery()->getResult();

            if (empty($Products)) {
                $app['monolog']->addDebug('search product not found.');
            }

            $forms = array();
            foreach ($Products as $Product) {
                /* @var $builder \Symfony\Component\Form\FormBuilderInterface */
                $builder = $app['form.factory']->createNamedBuilder('', 'add_cart', null, array(
                    'product' => $Product,
                ));
                $addCartForm = $builder->getForm();
                $forms[$Product->getId()] = $addCartForm->createView();
            }
            return $app->render('Coupon/View/admin/search_product.twig', array(
                'forms' => $forms,
                'Products' => $Products,
            ));
        }
    }

    /**
     * カテゴリ検索画面を表示する
     * @param Application $app
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function searchCategory(Application $app, Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $app['monolog']->addDebug('search category start.');
            $categoryId = $request->get('category_id');
            $existCategoryId = $request->get('exist_category_id');

            $existCategoryIds = array(0);
            if(strlen($existCategoryId > 0)) {
                $existCategoryIds = explode(",", $existCategoryId);
            }

            $Categories = $app['eccube.repository.category']->getList($categoryId);

            if (empty($Categories)) {
                $app['monolog']->addDebug('search category not found.');
            }

            // カテゴリーの一覧を作成する
            $list = $this->getCategolyList($Categories, $existCategoryIds);
            return $app->render('Coupon/View/admin/search_category.twig', array(
                 'Categories' => $list,
            ));
        }
    }

    /**
     * カテゴリーの一覧を作成する
     * @param unknown $Categories
     * @param unknown &$existCategoryIds
     */
    protected function getCategolyList($Categories, &$existCategoryIds) {
        $result = array();

        foreach ($Categories as $Category) {

            // 除外IDがない場合は配列に値を追加する
            if(count($existCategoryIds) == 0 || !in_array($Category->getId(), $existCategoryIds)) {
                $name = $this->getCategoryFullName($Category);
                $result += array($Category->getId() => $name);
            }

            // 子カテゴリがあれば更に一覧を作成する
            if(count(($Category->getChildren())) > 0) {
                $childResult = $this->getCategolyList($Category->getChildren(), $existCategoryIds);
                $result += $childResult;
            }

        }
        return $result;
    }

    /**
     * 親カテゴリ名を含むカテゴリ名を取得する.
     *
     * @param unknown $Category
     * @return string|unknown
     */
    protected function getCategoryFullName($Category) {

        if(is_null($Category)) {
            return "";
        }
        $fulName = $Category->getName();

        // 親カテゴリがない場合はカテゴリ名を返す.
        if(is_null($Category->getParent())) {
            return $fulName;
        }

        // 親カテゴリ名を結合する
        $ParentCategory = $Category->getParent();
        while(!is_null($ParentCategory)) {
            $fulName = $ParentCategory->getName() . "　＞　" .  $fulName;
            $ParentCategory = $ParentCategory->getParent();
        }
        return $fulName;
    }

}
