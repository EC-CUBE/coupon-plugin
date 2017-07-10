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

use Eccube\Entity\AbstractEntity;

/**
 * CouponOrder.
 */
class CouponOrder extends AbstractEntity
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var int
     */
    private $coupon_id;

    /**
     * @var string
     */
    private $coupon_cd;

    /**
     * @var string
     */
    private $coupon_name;

    /**
     * @var int
     */
    private $user_id;

    /**
     * @var string
     */
    private $email;

    /**
     * @var int
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
     * @var int
     */
    private $del_flg;
    /**
     * @var int
     */
    private $order_change_status;

    /**
     * @var int
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
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set coupon_id.
     *
     * @param int $couponId
     *
     * @return CouponOrder
     */
    public function setCouponId($couponId)
    {
        $this->coupon_id = $couponId;

        return $this;
    }

    /**
     * Get coupon_id.
     *
     * @return int
     */
    public function getCouponId()
    {
        return $this->coupon_id;
    }

    /**
     * Set coupon_cd.
     *
     * @param string $couponCd
     *
     * @return CouponOrder
     */
    public function setCouponCd($couponCd)
    {
        $this->coupon_cd = $couponCd;

        return $this;
    }

    /**
     * Get coupon_cd.
     *
     * @return string
     */
    public function getCouponCd()
    {
        return $this->coupon_cd;
    }

    /**
     * Set user_id.
     *
     * @param int $userId
     *
     * @return CouponOrder
     */
    public function setUserId($userId)
    {
        $this->user_id = $userId;

        return $this;
    }

    /**
     * Get user_id.
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set email.
     *
     * @param string $email
     *
     * @return CouponOrder
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set order_id.
     *
     * @param int $orderId
     *
     * @return CouponOrder
     */
    public function setOrderId($orderId)
    {
        $this->order_id = $orderId;

        return $this;
    }

    /**
     * Get order_id.
     *
     * @return int
     */
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * Set pre_order_id.
     *
     * @param string $preOrderId
     *
     * @return CouponOrder
     */
    public function setPreOrderId($preOrderId)
    {
        $this->pre_order_id = $preOrderId;

        return $this;
    }

    /**
     * Get pre_order_id.
     *
     * @return string
     */
    public function getPreOrderId()
    {
        return $this->pre_order_id;
    }

    /**
     * Set order_date.
     *
     * @param \DateTime $orderDate
     *
     * @return CouponOrder
     */
    public function setOrderDate($orderDate)
    {
        $this->order_date = $orderDate;

        return $this;
    }

    /**
     * Get order_date.
     *
     * @return \DateTime
     */
    public function getOrderDate()
    {
        return $this->order_date;
    }

    /**
     * Set discount.
     *
     * @param string $discount
     *
     * @return CouponOrder
     */
    public function setDiscount($discount)
    {
        $this->discount = $discount;

        return $this;
    }

    /**
     * Get discount.
     *
     * @return string
     */
    public function getDiscount()
    {
        return $this->discount;
    }

    /**
     * Set del_flg.
     *
     * @param int $delFlg
     *
     * @return CouponOrder
     */
    public function setDelFlg($delFlg)
    {
        $this->del_flg = $delFlg;

        return $this;
    }

    /**
     * Get del_flg.
     *
     * @return int
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
     * @param int $couponCancel
     */
    public function setCouponCancel($couponCancel)
    {
        $this->coupon_cancel = $couponCancel;
    }

    /**
     * Set create_date.
     *
     * @param \DateTime $createDate
     *
     * @return CouponOrder
     */
    public function setCreateDate($createDate)
    {
        $this->create_date = $createDate;

        return $this;
    }

    /**
     * Get create_date.
     *
     * @return \DateTime
     */
    public function getCreateDate()
    {
        return $this->create_date;
    }

    /**
     * Set update_date.
     *
     * @param \DateTime $updateDate
     *
     * @return CouponOrder
     */
    public function setUpdateDate($updateDate)
    {
        $this->update_date = $updateDate;

        return $this;
    }

    /**
     * Get update_date.
     *
     * @return \DateTime
     */
    public function getUpdateDate()
    {
        return $this->update_date;
    }

    /**
     * @return string
     */
    public function getCouponName()
    {
        return $this->coupon_name;
    }

    /**
     * @param string $coupon_name
     */
    public function setCouponName($coupon_name)
    {
        $this->coupon_name = $coupon_name;
    }

    /**
     * @return int
     */
    public function getOrderChangeStatus()
    {
        return $this->order_change_status;
    }

    /**
     * @param int $orderChangeStatus
     */
    public function setOrderChangeStatus($orderChangeStatus)
    {
        $this->order_change_status = $orderChangeStatus;
    }
}
