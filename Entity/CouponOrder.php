<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\Coupon42\Entity;

use Eccube\Entity\AbstractEntity;
use Doctrine\ORM\Mapping as ORM;

/**
 * Coupon Order
 *
 * @ORM\Table(name="plg_coupon_order")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="Plugin\Coupon42\Repository\CouponOrderRepository")
 */
class CouponOrder extends AbstractEntity
{
    /**
     * @var int
     *
     * @ORM\Column(name="coupon_order_id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="coupon_id", type="integer", options={"unsigned":true})
     */
    private $coupon_id;

    /**
     * @var string
     *
     * @ORM\Column(name="coupon_cd", type="string", nullable=true, length=20)
     */
    private $coupon_cd;

    /**
     * @var string
     *
     * @ORM\Column(name="coupon_name", type="string", nullable=true, length=50)
     */
    private $coupon_name;

    /**
     * @var int
     *
     * @ORM\Column(name="user_id", type="integer", options={"unsigned":true}, nullable=true)
     */
    private $user_id;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255, nullable=true)
     */
    private $email;

    /**
     * @var int
     *
     * @ORM\Column(name="order_id", type="integer", options={"unsigned":true})
     */
    private $order_id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="pre_order_id", type="string", length=255, nullable=true)
     */
    private $pre_order_id;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="order_date", type="datetimetz", nullable=true)
     */
    private $order_date;

    /**
     * @var int
     *
     * @ORM\Column(name="order_item_id", type="integer", options={"unsigned":true}, nullable=true)
     */
    private $order_item_id;

    /**
     * @var string
     *
     * @ORM\Column(name="discount", type="decimal", precision=12, scale=2, options={"unsigned":true,"default":0})
     */
    private $discount = 0;

    /**
     * @var boolean
     *
     * @ORM\Column(name="visible", type="boolean", options={"default":true})
     */
    private $visible;

    /**
     * @var boolean
     *
     * @ORM\Column(name="order_change_status", type="boolean", options={"default":true})
     */
    private $order_change_status;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="create_date", type="datetimetz")
     */
    private $create_date;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="update_date", type="datetimetz")
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
     * @param \DateTime|null $orderDate
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
     * @return \DateTime|null
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
     * @param bool $visible
     *
     * @return CouponOrder
     */
    public function setVisible($visible)
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * is visible.
     *
     * @return bool
     */
    public function isVisible()
    {
        return $this->visible;
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
     *
     * @return $this
     */
    public function setCouponName($coupon_name)
    {
        $this->coupon_name = $coupon_name;

        return $this;
    }

    /**
     * @return bool
     */
    public function getOrderChangeStatus()
    {
        return $this->order_change_status;
    }

    /**
     * @param bool $orderChangeStatus
     *
     * @return $this
     */
    public function setOrderChangeStatus($orderChangeStatus)
    {
        $this->order_change_status = $orderChangeStatus;

        return $this;
    }

    /**
     * @return int
     */
    public function getOrderItemId()
    {
        return $this->order_item_id;
    }

    /**
     * @param int $order_item_id
     *
     * @return $this
     */
    public function setOrderItemId(int $order_item_id)
    {
        $this->order_item_id = $order_item_id;

        return $this;
    }
}
