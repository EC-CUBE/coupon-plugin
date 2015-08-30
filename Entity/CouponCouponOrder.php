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
class CouponCouponOrder extends \Eccube\Entity\AbstractEntity
{

    /**
     *
     * @var integer
     */
    private $id;

    /**
     *
     * @var integer
     */
    private $coupon_id;

    /**
     *
     * @var string
     */
    private $coupon_cd;

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
     * Constructor
     */
    public function __construct()
    {
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
     * Get id
     *
     * @return integer
     */
    public function getCouponId()
    {
        return $this->coupon_id;
    }

    /**
     * Set id
     *
     * @param integer $code
     * @return Module
     */
    public function setCouponId($coupon_id)
    {
        $this->coupon_id = $coupon_id;

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
     * Get order_id
     *
     * @return integer
     */
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * Set order_id
     *
     * @param integer $code
     * @return Module
     */
    public function setOrderId($order_id)
    {
        $this->order_id = $order_id;

        return $this;
    }

    /**
     * Set pre_order_id
     *
     * @param  string $preOrderId
     * @return Order
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
     * @param  \DateTime $orderDate
     * @return Order
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

}
