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

use Doctrine\ORM\Mapping as ORM;

/**
 * CouponCoupon
 */
class CouponCoupon extends \Eccube\Entity\AbstractEntity
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $coupon_cd;

    /**
     * @var integer
     */
    private $coupon_type;

    /**
     * @var string
     */
    private $coupon_name;

    /**
     * @var integer
     */
    private $discount_type;

    /**
     * @var integer
     */
    private $coupon_use_time;

    /**
     * @var string
     */
    private $discount_price;

    /**
     * @var string
     */
    private $discount_rate;

    /**
     * @var integer
     */
    private $enable_flag;

    /**
     * @var \DateTime
     */
    private $available_from_date;

    /**
     * @var \DateTime
     */
    private $available_to_date;

    /**
     * @var integer
     */
    private $del_flg;

    /**
     * @var integer
     */
    private $coupon_member;

    /**
     * @var integer
     */
    private $coupon_lower_limit;



    /**
     * @var \DateTime
     */
    private $create_date;

    /**
     * @var \DateTime
     */
    private $update_date;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $CouponDetails;

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
     * Set coupon_cd
     *
     * @param string $couponCd
     * @return CouponCoupon
     */
    public function setCouponCd($couponCd)
    {
        $this->coupon_cd = $couponCd;

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
     * Set coupon_type
     *
     * @param integer $couponType
     * @return CouponCoupon
     */
    public function setCouponType($couponType)
    {
        $this->coupon_type = $couponType;

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
     * Set coupon_name
     *
     * @param string $couponName
     * @return CouponCoupon
     */
    public function setCouponName($couponName)
    {
        $this->coupon_name = $couponName;

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
     * Set discount_type
     *
     * @param integer $discountType
     * @return CouponCoupon
     */
    public function setDiscountType($discountType)
    {
        $this->discount_type = $discountType;

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
     * Set coupon_use_time
     *
     * @param integer $couponUseTime
     * @return CouponCoupon
     */
    public function setCouponUseTime($couponUseTime)
    {
        $this->coupon_use_time = $couponUseTime;

        return $this;
    }

    /**
     * Get coupon_use_time
     *
     * @return integer 
     */
    public function getCouponUseTime()
    {
        return $this->coupon_use_time;
    }

    /**
     * Set discount_price
     *
     * @param string $discountPrice
     * @return CouponCoupon
     */
    public function setDiscountPrice($discountPrice)
    {
        $this->discount_price = $discountPrice;

        return $this;
    }

    /**
     * Get discount_price
     *
     * @return string 
     */
    public function getDiscountPrice()
    {
        return $this->discount_price;
    }

    /**
     * Set discount_rate
     *
     * @param string $discountRate
     * @return CouponCoupon
     */
    public function setDiscountRate($discountRate)
    {
        $this->discount_rate = $discountRate;

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
     * Set enable_flag
     *
     * @param integer $enableFlag
     * @return CouponCoupon
     */
    public function setEnableFlag($enableFlag)
    {
        $this->enable_flag = $enableFlag;

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
     * Set available_from_date
     *
     * @param \DateTime $availableFromDate
     * @return CouponCoupon
     */
    public function setAvailableFromDate($availableFromDate)
    {
        $this->available_from_date = $availableFromDate;

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
     * Set available_to_date
     *
     * @param \DateTime $availableToDate
     * @return CouponCoupon
     */
    public function setAvailableToDate($availableToDate)
    {
        $this->available_to_date = $availableToDate;

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
     * Set del_flg
     *
     * @param integer $delFlg
     * @return CouponCoupon
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
     * @return CouponCoupon
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
     * @return CouponCoupon
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
     * @param \Plugin\Coupon\Entity\CouponCouponDetail $couponDetails
     * @return CouponCoupon
     */
    public function addCouponDetail(\Plugin\Coupon\Entity\CouponCouponDetail $couponDetails)
    {
        $this->CouponDetails[] = $couponDetails;

        return $this;
    }

    /**
     * Remove CouponDetails
     *
     * @param \Plugin\Coupon\Entity\CouponCouponDetail $couponDetails
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
     * @return int
     */
    public function getCouponMember()
    {
        return $this->coupon_member;
    }

    /**
     * @param int $couponMember
     */
    public function setCouponMember($couponMember)
    {
        $this->coupon_member = $couponMember;
    }

    /**
     * @return int
     */
    public function getCouponLowerLimit()
    {
        return $this->coupon_lower_limit;
    }

    /**
     * @param int $couponLowerLimit
     */
    public function setCouponLowerLimit($couponLowerLimit)
    {
        $this->coupon_lower_limit = $couponLowerLimit;
    }
}
