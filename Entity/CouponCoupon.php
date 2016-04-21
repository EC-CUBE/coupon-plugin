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

use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Coupon
 */
class CouponCoupon extends \Eccube\Entity\AbstractEntity
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
    private $coupon_cd;

    /**
     *
     * @var string
     */
    private $coupon_name;

    /**
     *
     * @var integer
     */
    private $coupon_type;

    /**
     *
     * @var integer
     */
    private $discount_type;

    /**
     *
     * @var string
     */
    private $discount_price;

    /**
     *
     * @var string
     */
    private $discount_rate;

    /**
     *
     * @var integer
     */
    private $enable_flag;

    /**
     *
     * @var integer
     */
    private $del_flg;

    /**
     *
     * @var \DateTime
     */
    private $available_from_date;

    /**
     *
     * @var \DateTime
     */
    private $available_to_date;

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
     * @var \Doctrine\Common\Collections\Collection
     */
    private $CouponDetails;

    /**
     *
     * @var \coupon_use_time
     */
    private $coupon_use_time;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->CouponDetails = new \Doctrine\Common\Collections\ArrayCollection();
    }

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
     * Get coupon_cd
     *
     * @return string
     */
    public function getCouponCd()
    {
        return $this->coupon_cd;
    }

    /**
     * Set id
     *
     * @param
     *            string coupon_cd
     * @return Module
     */
    public function setCouponCd($coupon_cd)
    {
        $this->coupon_cd = $coupon_cd;

        return $this;
    }

    /**
     * Get coupon_type
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
     *            integer
     * @return Module
     */
    public function setCouponType($couponType)
    {
        $this->coupon_type = $couponType;

        return $this;
    }

    /**
     * Get discount_type
     *
     * @return integer
     */
    public function getDiscountType()
    {
        return $this->discount_type;
    }

    /**
     * Set discount_type
     *
     * @param
     *            integer
     * @return Module
     */
    public function setDiscountType($discountType)
    {
        $this->discount_type = $discountType;

        return $this;
    }

    /**
     * Get discount_price
     *
     * @return integer
     */
    public function getDiscountPrice()
    {
        return $this->discount_price;
    }

    /**
     * Set discount_price
     *
     * @param
     *            integer
     * @return Module
     */
    public function setDiscountPrice($discountPrice)
    {
        $this->discount_price = $discountPrice;

        return $this;
    }

    /**
     * Get discount_rate
     *
     * @return string
     */
    public function getDiscountRate()
    {
        return $this->discount_rate;
    }

    /**
     * Set discount_rate
     *
     * @param
     *            string
     * @return Module
     */
    public function setDiscountRate($discountRate)
    {
        $this->discount_rate = $discountRate;
        return $this;
    }

    /**
     * Get enable_flag
     *
     * @return integer
     */
    public function getEnableFlag()
    {
        return $this->enable_flag;
    }

    /**
     * Set enable_flag
     *
     * @param
     *            integer
     * @return Module
     */
    public function setEnableFlag($enableFlag)
    {
        $this->enable_flag = $enableFlag;

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
     * Add CouponDetails
     *
     * @param  \Plugin\OrderPdf\Entity\CouponCouponDetail
     * @return Coupon
     */
    public function addCouponDetail(\Plugin\Coupon\Entity\CouponCouponDetail $couponDetails)
    {
        $this->CouponDetails[] = $couponDetails;

        return $this;
    }

    /**
     * Remove CouponDetails
     *
     * @param \Eccube\Entity\OrderDetail $orderDetails
     */
    public function removeCouponDetail(\Plugin\Coupon\Entity\CouponCouponDetail $couponDetails)
    {
        $this->CouponDetails->removeElement($couponDetails);
    }

    /**
     * Get CouponDetails
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCouponDetails()
    {
        return $this->CouponDetails;
    }

    /**
     * Set coupon_name
     *
     * @param string $coupon_name
     * @return Module
     */
    public function setCouponName($coupon_name)
    {
        $this->coupon_name = $coupon_name;

        return $this;
    }

    /**
     * Get coupon_name
     *
     * @return string
     */
    public function getCouponName()
    {
        return $this->coupon_name;
    }

    /**
     * Set available_from_date
     *
     * @param \DateTime $available_from_date
     * @return Module
     */
    public function setAvailableFromDate($available_from_date)
    {
        $this->available_from_date = $available_from_date;

        return $this;
    }

    /**
     * Get available_from_date
     *
     * @return \DateTime
     */
    public function getAvailableFromDate()
    {
        return $this->available_from_date;
    }

    /**
     * Set available_from_date
     *
     * @param \DateTime $available_from_date
     * @return Module
     */
    public function setAvailableToDate($available_to_date)
    {
        $this->available_to_date = $available_to_date;

        return $this;
    }

    /**
     * Get available_to_date
     *
     * @return \DateTime
     */
    public function getAvailableToDate()
    {
        return $this->available_to_date;
    }

    /**
     * @return \coupon_use_time
     */
    public function getCouponUseTime()
    {
        return $this->coupon_use_time;
    }

    /**
     * @param \coupon_use_time $coupon_use_time
     */
    public function setCouponUseTime($coupon_use_time)
    {
        $this->coupon_use_time = $coupon_use_time;
    }
}
