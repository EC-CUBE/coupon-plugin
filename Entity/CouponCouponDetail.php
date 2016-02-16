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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */
namespace Plugin\Coupon\Entity;

/**
 * Coupon Detail
 */
class CouponCouponDetail extends \Eccube\Entity\AbstractEntity
{

    /**
     *
     * @var integer
     */
    private $id;

    /**
     *
     * @var string
     */
    private $coupon_id;

    /**
     *
     * @var integer
     */
    private $coupon_type;

    /**
     *
     * @var \Eccube\Entity\Product
     */
    private $Product;

    /**
     *
     * @var \Eccube\Entity\Category
     */
    private $Category;

    /**
     *
     * @var integer
     */
    private $del_flg;

    /**
     *
     * @var \DateTime
     */
    private $create_date;

    /**
     *
     * @var \DateTime
     */
    private $update_date;

    /**
     * @var \Plugin\Coupon\Entity\CouponCoupon
     */
    private $Coupon;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set id
     *
     * @param integer $code
     * @return Module
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get coupon_id
     *
     * @return string
     */
    public function getCouponId()
    {
        return $this->coupon_id;
    }

    /**
     * Set coupon_id
     *
     * @param
     *            integer coupon_id
     * @return Module
     */
    public function setCouponId($couponId)
    {
        $this->coupon_id = $couponId;

        return $this;
    }

    /**
     * Get coupon_id
     *
     * @return integer
     */
    public function getCouponType()
    {
        return $this->coupon_type;
    }

    /**
     * Set coupon_type
     *
     * @param
     *            integer coupon_type
     * @return Module
     */
    public function setCouponType($coupon_type)
    {
        $this->coupon_type = $coupon_type;

        return $this;
    }

    /**
     * Get Product
     *
     * @return \Eccube\Entity\Product
     */
    public function getProduct()
    {
        return $this->Product;
    }

    /**
     * Set Product
     *
     * @param \Eccube\Entity\Product $Product
     * @return Module
     */
    public function setProduct($Product)
    {
        $this->Product = $Product;

        return $this;
    }

    /**
     * Get Categoryd
     *
     * @return \Eccube\Entity\Category
     */
    public function getCategory()
    {
        return $this->Category;
    }

    /**
     * Set Categoryd
     *
     * @param
     *            \Eccube\Entity\Category \Eccube\Entity\Category
     * @return Module
     */
    public function setCategory($Category)
    {
        $this->Category = $Category;

        return $this;
    }

    /**
     * Set del_flg
     *
     * @param integer $delFlg
     * @return Order
     */
    public function setDelFlg($delFlg)
    {
        $this->del_flg = $delFlg;

        return $this;
    }

    /**
     * Get del_flg
     *
     * @return integer
     */
    public function getDelFlg()
    {
        return $this->del_flg;
    }

    /**
     * Set create_date
     *
     * @param \DateTime $createDate
     * @return Module
     */
    public function setCreateDate($createDate)
    {
        $this->create_date = $createDate;

        return $this;
    }

    /**
     * Get create_date
     *
     * @return \DateTime
     */
    public function getCreateDate()
    {
        return $this->create_date;
    }

    /**
     * Set update_date
     *
     * @param \DateTime $updateDate
     * @return Module
     */
    public function setUpdateDate($updateDate)
    {
        $this->update_date = $updateDate;

        return $this;
    }

    /**
     * Get update_date
     *
     * @return \DateTime
     */
    public function getUpdateDate()
    {
        return $this->update_date;
    }
    /**
     * Set Order
     *
     * @param  \Plugin\Coupon\Entity\CouponCoupon $coupon
     * @return CouponDetail
     */
    public function setCoupon(\Plugin\Coupon\Entity\CouponCoupon $coupon)
    {
        $this->Coupon = $coupon;

        return $this;
    }

    /**
     * Get Order
     *
     * @return \Plugin\Coupon\Entity\CouponCoupon
     */
    public function getCoupon()
    {
        return $this->Coupon;
    }

    /**
     * 親カテゴリ名を含むカテゴリ名を取得する.
     * @return string
     */
    public function getCategoryFullName() {

        if(is_null($this->Category)) {
            return "";
        }
        $fulName = $this->Category->getName();

        // 親カテゴリがない場合はカテゴリ名を返す.
        if(is_null($this->Category->getParent())) {
            return $fulName;
        }

        // 親カテゴリ名を結合する
        $ParentCategory = $this->Category->getParent();
        while(!is_null($ParentCategory)) {
            $fulName = $ParentCategory->getName() . "　＞　" .  $fulName;
            $ParentCategory = $ParentCategory->getParent();
        }
        return $fulName;
    }
}
