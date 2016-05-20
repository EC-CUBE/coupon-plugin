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

namespace Plugin\Coupon\Service;

use Eccube\Application;
use Eccube\Common\Constant;
use Eccube\Entity\Customer;
use Eccube\Entity\Order;
use Plugin\Coupon\Entity\CouponCoupon;

class CouponService
{
    /** @var \Eccube\Application */
    public $app;

    /**
     * コンストラクタ
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * クーポン情報を新規登録する
     *
     * @param $data
     * @return bool
     */
    public function createCoupon($data)
    {
        // クーポン詳細情報を生成する
        $coupon = $this->newCoupon($data);

        $em = $this->app['orm.em'];

        // クーポン情報を登録する
        $em->persist($coupon);

        // クーポン詳細情報を登録する
        foreach ($data['CouponDetails'] as $detail) {
            $couponDetail = $this->newCouponDetail($coupon, $detail);
            $em->persist($couponDetail);
        }

        $em->flush();

        return true;
    }

    /**
     * クーポン情報を更新する
     *
     * @param $data
     * @return bool
     */
    public function updateCoupon($data)
    {
        $dateTime = new \DateTime();
        $em = $this->app['orm.em'];

        // クーポン情報を取得する
        $coupon = $this->app['eccube.plugin.coupon.repository.coupon']->find($data['id']);
        if (is_null($coupon)) {
            false;
        }

        // クーポン情報を書き換える
        $coupon->setCouponCd($data['coupon_cd']);
        $coupon->setCouponName($data['coupon_name']);
        $coupon->setCouponType($data['coupon_type']);
        $coupon->setDiscountType($data['discount_type']);
        $coupon->setDiscountPrice($data['discount_price']);
        $coupon->setDiscountRate($data['discount_rate']);
        $coupon->setAvailableFromDate($data['available_from_date']);
        $coupon->setAvailableToDate($data['available_to_date']);
        $coupon->setCouponUseTime($data['coupon_use_time']);
        $coupon->setUpdateDate($dateTime);

        // クーポン情報を更新する
        $em->persist($coupon);

        // クーポン詳細情報を取得する
        $details = $coupon->getCouponDetails();

        // クーポン詳細情報を一旦削除する
        foreach ($details as $detail) {
            // クーポン詳細情報を書き換える
            $detail->setDelFlg(Constant::ENABLED);
            $detail->setUpdateDate($dateTime);
            $em->persist($detail);
        }

        // クーポン詳細情報を登録/更新する
        $details = $data->getCouponDetails();
        foreach ($details as $detail) {
            $couponDetail = null;
            if (is_null($detail->getId())) {
                $couponDetail = new \Plugin\Coupon\Entity\CouponCouponDetail();
                $couponDetail = $detail;
                $couponDetail->setCoupon($coupon);
                $couponDetail->setCouponType($coupon->getCouponType());
                $couponDetail->setCreateDate($dateTime);
            } else {
                $couponDetail = $this->app['eccube.plugin.coupon.repository.coupon_detail']->find($detail->getId());
            }

            $couponDetail->setDelFlg(Constant::DISABLED);
            $couponDetail->setUpdateDate($dateTime);

            $em->persist($couponDetail);
        }

        $em->flush();

        return true;
    }

    /**
     * クーポン情報を有効/無効にする
     *
     * @param $couponId
     * @return bool
     */
    public function enableCoupon($couponId)
    {
        $em = $this->app['orm.em'];

        // クーポン情報を取得する
        $coupon = $this->app['eccube.plugin.coupon.repository.coupon']->find($couponId);
        if (is_null($coupon)) {
            return false;
        }

        // クーポン情報を書き換える
        $coupon->setEnableFlag($coupon->getEnableFlag() == 0 ? 1 : 0);
        $coupon->setUpdateDate(new \DateTime());

        // クーポン情報を登録する
        $em->persist($coupon);

        $em->flush();

        return true;
    }

    /**
     * クーポン情報を削除する
     *
     * @param $couponId
     * @return bool
     */
    public function deleteCoupon($couponId)
    {
        $currentDateTime = new \DateTime();
        $em = $this->app['orm.em'];

        // クーポン情報を取得する
        $coupon = $this->app['eccube.plugin.coupon.repository.coupon']->find($couponId);
        if (is_null($coupon)) {
            return false;
        }
        // クーポン情報を書き換える
        $coupon->setDelFlg(Constant::ENABLED);
        $coupon->setUpdateDate($currentDateTime);

        // クーポン情報を登録する
        $em->persist($coupon);

        // クーポン詳細情報を取得する
        $details = $coupon->getCouponDetails();
        foreach ($details as $detail) {
            // クーポン詳細情報を書き換える
            $detail->setDelFlg(Constant::ENABLED);
            $detail->setUpdateDate($currentDateTime);
            $em->persist($detail);
        }

        $em->flush();

        return true;
    }

    /**
     * クーポンコードを生成する.
     *
     * @param int $length
     * @return string
     */
    public function generateCouponCd($length = 12)
    {

        $couponCd = substr(base_convert(md5(uniqid()), 16, 36), 0, $length);

        return $couponCd;
    }

    /**
     * クーポン情報を生成する
     *
     * @param $data
     * @return CouponCoupon
     */
    protected function newCoupon($data)
    {
        $dateTime = new \DateTime();


        $coupon = new \Plugin\Coupon\Entity\CouponCoupon();

        $coupon->setCouponCd($data['coupon_cd']);
        $coupon->setCouponName($data['coupon_name']);

        $coupon->setCouponType($data['coupon_type']);
        $coupon->setDiscountType($data['discount_type']);

        $coupon->setDiscountPrice($data['discount_price']);
        $coupon->setDiscountRate($data['discount_rate']);
        $coupon->setCouponUseTime($data['coupon_use_time']);

        $coupon->setEnableFlag(Constant::ENABLED);
        $coupon->setDelFlg(Constant::DISABLED);
        $coupon->setCreateDate($dateTime);
        $coupon->setUpdateDate($dateTime);


        $coupon->setAvailableFromDate($data['available_from_date']);
        $coupon->setAvailableToDate($data['available_to_date']);

        return $coupon;
    }

    /**
     * クーポン詳細情報を生成する
     *
     * @param CouponCoupon $coupon
     * @param \Plugin\Coupon\Entity\CouponCouponDetail $detail
     * @return \Plugin\Coupon\Entity\CouponCouponDetail
     */
    protected function newCouponDetail(\Plugin\Coupon\Entity\CouponCoupon $coupon, \Plugin\Coupon\Entity\CouponCouponDetail $detail)
    {

        $couponDetail = new \Plugin\Coupon\Entity\CouponCouponDetail();

        $couponDetail->setCoupon($coupon);
        $couponDetail->setCouponType($coupon->getCouponType());
        $couponDetail->setUpdateDate($coupon->getUpdateDate());
        $couponDetail->setCreateDate($coupon->getCreateDate());

        $couponDetail->setCategory($detail->getCategory());
        $couponDetail->setProduct($detail->getProduct());

        $couponDetail->setDelFlg(Constant::DISABLED);

        return $couponDetail;
    }

    /**
     * 注文にクーポン対象商品が含まれているか確認する.
     *
     * @param CouponCoupon $Coupon
     * @param Order $Order
     * @return bool
     */
    public function existsCouponProduct(CouponCoupon $Coupon, Order $Order)
    {
        $applyDiscountFlg = false;
        if (!is_null($Coupon)) {
            // 対象商品の存在確認
            if ($Coupon->getCouponType() == 1) {
                // 商品の場合
                $applyDiscountFlg = $this->containsProduct($Coupon, $Order);
            } else if ($Coupon->getCouponType() == 2) {
                // カテゴリの場合
                $applyDiscountFlg = $this->containsCategory($Coupon, $Order);
            }
        }

        return $applyDiscountFlg;

    }

    /**
     * 商品がクーポン適用の対象か調査する
     *
     * @param CouponCoupon $Coupon
     * @param Order $Order
     * @return bool
     */
    private function containsProduct(\Plugin\Coupon\Entity\CouponCoupon $Coupon, \Eccube\Entity\Order $Order)
    {
        // クーポンの対象商品IDを配列にする
        $targetProductIds = array();
        foreach ($Coupon->getCouponDetails() as $detail) {
            $targetProductIds[] = $detail->getProduct()->getId();
        }

        // 一致する商品IDがあればtrueを返す
        foreach ($Order->getOrderDetails() as $detail) {
            if (in_array($detail->getProduct()->getId(), $targetProductIds)) {
                return true;
            }
        }

        return false;
    }

    /**
     * カテゴリがクーポン適用の対象か調査する.
     * 下位のカテゴリから上位のカテゴリに向けて検索する
     *
     * @param CouponCoupon $Coupon
     * @param Order $Order
     * @return bool
     */
    private function containsCategory(\Plugin\Coupon\Entity\CouponCoupon $Coupon, \Eccube\Entity\Order $Order)
    {
        // クーポンの対象カテゴリIDを配列にする
        $targetCategoryIds = array();
        foreach ($Coupon->getCouponDetails() as $detail) {
            $targetCategoryIds[] = $detail->getCategory()->getId();
        }

        // 受注データからカテゴリIDを取得する
        foreach ($Order->getOrderDetails() as $orderDetail) {
            foreach ($orderDetail->getProduct()->getProductCategories() as $productCategory) {
                if ($this->existsDepthCategory($targetCategoryIds, $productCategory->getCategory())) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * クーポン対象のカテゴリが存在するか確認にする.
     *
     * @param $targetCategoryIds
     * @param \Eccube\Entity\Category $Category
     * @return bool
     */
    private function existsDepthCategory(&$targetCategoryIds, \Eccube\Entity\Category $Category)
    {
        // Categoryがnullならfalse
        if (is_null($Category)) {
            return false;
        }

        // 対象カテゴリか確認
        if (in_array($Category->getId(), $targetCategoryIds)) {
            return true;
        }

        // Categoryをテーブルから取得
        if (is_null($Category->getParent())) {
            return false;
        }

        // 親カテゴリをテーブルから取得
        $ParentCategory = $this->app['eccube.repository.category']->find($Category->getParent());
        if (is_null($ParentCategory)) {
            return false;
        }

        return $this->existsDepthCategory($targetCategoryIds, $ParentCategory);
    }

    /**
     * クーポン受注情報を保存する
     *
     * @param Order $Order
     * @param CouponCoupon $Coupon
     * @param $couponCd
     * @param Customer $Customer
     * @param $discount
     */
    public function saveCouponOrder(Order $Order, CouponCoupon $Coupon, $couponCd, Customer $Customer, $discount)
    {

        if (is_null($Order)) {
            return;
        }

        $repository = $this->app['eccube.plugin.coupon.repository.coupon_order'];

        // クーポン受注情報を取得する
        $CouponOrder = $repository->findOneBy(array(
            'pre_order_id' => $Order->getPreOrderId()
        ));

        if (is_null($CouponOrder)) {
            // 未登録の場合
            $CouponOrder = new \Plugin\Coupon\Entity\CouponCouponOrder();

            $CouponOrder->setOrderId($Order->getId());
            $CouponOrder->setPreOrderId($Order->getPreOrderId());

            $CouponOrder->setDelFlg(Constant::DISABLED);
            $CouponOrder->setCreateDate(new \DateTime());
        }

        // 更新対象データ
        if (is_null($Coupon) || (is_null($couponCd) || strlen($couponCd) == 0)) {
            // クーポンがない または クーポンコードが空の場合
            $CouponOrder->setCouponCd($couponCd);
            $CouponOrder->setCouponId(null);
        } else {
            // クーポン情報があるが、対象商品がない場合はクーポンIDにnullを設定する。
            // そうでない場合はクーポンIDを設定する
            if ($this->existsCouponProduct($Coupon, $Order)) {
                $CouponOrder->setCouponId($Coupon->getId());
            } else {
                $CouponOrder->setCouponId(null);
            }
            $CouponOrder->setCouponCd($Coupon->getCouponCd());
        }

        // ログイン済みの場合は, user_id取得
        if ($this->app->isGranted('ROLE_USER')) {
            $CouponOrder->setUserId($Customer->getId());
        } else {
            $CouponOrder->setEmail($Customer->getEmail());
        }

        // 割引金額をセット
        $CouponOrder->setDiscount($discount);

        $CouponOrder->setUpdateDate(new \DateTime());

        $repository->save($CouponOrder);

    }

    /**
     * 合計、値引きを再計算する
     *
     * @param Order $Order
     * @param CouponCoupon $Coupon
     * @return float|int|string
     * @throws \Doctrine\ORM\NoResultException
     */
    public function recalcOrder(Order $Order, CouponCoupon $Coupon)
    {
        $discount = 0;

        // ------------------------------------------
        // クーポンコードが存在する場合
        // カートに入っている商品の値引き額を設定する
        // ------------------------------------------
        if ($Coupon) {

            // 対象商品の存在確認.
            // 割引対象商品が存在する場合は値引き額を取得する
            if ($this->existsCouponProduct($Coupon, $Order)) {
                // 割引対象商品がある場合は値引き額を計算する
                if ($Coupon->getDiscountType() == 1) {
                    $discount = $Coupon->getDiscountPrice();
                } else {
                    // 課税区分の取得
                    $taxService = $this->app['eccube.service.tax_rule'];
                    $TaxRule = $this->app['eccube.repository.tax_rule']->getByRule();

                    // 小数点以下は四捨五入
                    $discount = $taxService->calcTax(
                        $Order->getTotal(),
                        $Coupon->getDiscountRate(),
                        $TaxRule->getCalcRule()->getId(),
                        $TaxRule->getTaxAdjust()
                    );
                }

            }
        }

        return $discount;

    }

    /**
     * カート内の商品(ORderDetail)がクーポン対象商品か確認する
     *
     * @param Order $Order
     * @return bool
     */
    public function isOrderInActiveCoupon(Order $Order)
    {

        // 有効なクーポン一覧を取得する
        $Coupons = $this->app['eccube.plugin.coupon.repository.coupon']->findActiveCouponAll();

        // 有効なクーポンを持つ商品の存在を確認する
        foreach ($Coupons as $Coupon) {
            if ($this->existsCouponProduct($Coupon, $Order)) {
                return true;
            }
        }

        return false;
    }


    /**
     * クーポン受注情報を取得する.
     *
     * @param $preOrderId
     * @return null|object
     */
    public function getCouponOrder($preOrderId)
    {
        $CouponOrder = $this->app['eccube.plugin.coupon.repository.coupon_order']
            ->findOneBy(array(
                'pre_order_id' => $preOrderId,
            ));

        return $CouponOrder;
    }


}
