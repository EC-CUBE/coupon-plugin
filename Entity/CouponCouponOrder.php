<?php
/*
 * This file is part of the Coupon plugin
 *
 * Copyright (C) 2016 LOCKON CO.,LTD. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Plugin\Coupon\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CouponCouponOrder
 */
class CouponCouponOrder extends \Eccube\Entity\AbstractEntity
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $coupon_id;

    /**
     * @var string
     */
    private $coupon_cd;

    /**
     * @var integer
     */
    private $user_id;

    /**
     * @var string
     */
    private $email;

    /**
     * @var integer
     */
    private $order_id;

    /**
     * @var string
     */
    private $pre_order_id;

    /**
     * @var \DateTime
     */
    private $order_date;

    /**
     * @var string
     */
    private $discount = 0;

    /**
     * @var integer
     */
    private $del_flg;

    /**
     * @var integer
     */
    private $coupon_cancel;

    /**
     * @var \DateTime
     */
    private $create_date;

    /**
     * @var \DateTime
     */
    private $update_date;


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
     * Set coupon_id
     *
     * @param integer $couponId
     * @return CouponCouponOrder
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
    public function getCouponId()
    {
        return $this->coupon_id;
    }

    /**
     * Set coupon_cd
     *
     * @param string $couponCd
     * @return CouponCouponOrder
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
     * Set user_id
     *
     * @param integer $userId
     * @return CouponCouponOrder
     */
    public function setUserId($userId)
    {
        $this->user_id = $userId;

        return $this;
    }

    /**
     * Get user_id
     *
     * @return integer 
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return CouponCouponOrder
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string 
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set order_id
     *
     * @param integer $orderId
     * @return CouponCouponOrder
     */
    public function setOrderId($orderId)
    {
        $this->order_id = $orderId;

        return $this;
    }

    /**
     * Get order_id
     *
     * @return integer 
     */
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * Set pre_order_id
     *
     * @param string $preOrderId
     * @return CouponCouponOrder
     */
    public function setPreOrderId($preOrderId)
    {
        $this->pre_order_id = $preOrderId;

        return $this;
    }

    /**
     * Get pre_order_id
     *
     * @return string 
     */
    public function getPreOrderId()
    {
        return $this->pre_order_id;
    }

    /**
     * Set order_date
     *
     * @param \DateTime $orderDate
     * @return CouponCouponOrder
     */
    public function setOrderDate($orderDate)
    {
        $this->order_date = $orderDate;

        return $this;
    }

    /**
     * Get order_date
     *
     * @return \DateTime 
     */
    public function getOrderDate()
    {
        return $this->order_date;
    }

    /**
     * Set discount
     *
     * @param string $discount
     * @return CouponCouponOrder
     */
    public function setDiscount($discount)
    {
        $this->discount = $discount;

        return $this;
    }

    /**
     * Get discount
     *
     * @return string 
     */
    public function getDiscount()
    {
        return $this->discount;
    }

    /**
     * Set del_flg
     *
     * @param integer $delFlg
     * @return CouponCouponOrder
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
     * @return int
     */
    public function getCouponCancel()
    {
        return $this->coupon_cancel;
    }

    /**
     * @param int $coupon_cancel
     */
    public function setCouponCancel($coupon_cancel)
    {
        $this->coupon_cancel = $coupon_cancel;
    }

    /**
     * Set create_date
     *
     * @param \DateTime $createDate
     * @return CouponCouponOrder
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
     * @return CouponCouponOrder
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
}
