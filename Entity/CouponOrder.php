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

namespace Plugin\Coupon44\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\AbstractEntity;
use Plugin\Coupon44\Repository\CouponOrderRepository;

/**
 * Coupon Order
 */
#[ORM\Table(name: 'plg_coupon_order')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discriminator_type', type: 'string', length: 255)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: CouponOrderRepository::class)]
class CouponOrder extends AbstractEntity
{
    #[ORM\Column(name: 'coupon_order_id', type: Types::INTEGER, options: ['unsigned' => true])]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    #[ORM\Column(name: 'coupon_id', type: Types::INTEGER, options: ['unsigned' => true])]
    private ?int $coupon_id = null;

    #[ORM\Column(name: 'coupon_cd', type: Types::STRING, nullable: true, length: 20)]
    private ?string $coupon_cd = null;

    #[ORM\Column(name: 'coupon_name', type: Types::STRING, nullable: true, length: 50)]
    private ?string $coupon_name = null;

    #[ORM\Column(name: 'user_id', type: Types::INTEGER, options: ['unsigned' => true], nullable: true)]
    private ?int $user_id = null;

    #[ORM\Column(name: 'email', type: Types::STRING, length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(name: 'order_id', type: Types::INTEGER, options: ['unsigned' => true])]
    private ?int $order_id = null;

    #[ORM\Column(name: 'pre_order_id', type: Types::STRING, length: 255, nullable: true)]
    private ?string $pre_order_id = null;

    #[ORM\Column(name: 'order_date', type: Types::DATETIMETZ_MUTABLE, nullable: true)]
    private ?\DateTime $order_date = null;

    #[ORM\Column(name: 'order_item_id', type: Types::INTEGER, options: ['unsigned' => true], nullable: true)]
    private ?int $order_item_id = null;

    #[ORM\Column(name: 'discount', type: Types::DECIMAL, precision: 12, scale: 2, options: ['unsigned' => true, 'default' => 0])]
    private ?string $discount = '0';

    #[ORM\Column(name: 'visible', type: Types::BOOLEAN, options: ['default' => true])]
    private ?bool $visible = null;

    #[ORM\Column(name: 'order_change_status', type: Types::BOOLEAN, options: ['default' => true])]
    private ?bool $order_change_status = null;

    #[ORM\Column(name: 'create_date', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTime $create_date = null;

    #[ORM\Column(name: 'update_date', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTime $update_date = null;

    /**
     * Get id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set coupon_id.
     *
     * @return CouponOrder
     */
    public function setCouponId(?int $couponId): self
    {
        $this->coupon_id = $couponId;

        return $this;
    }

    /**
     * Get coupon_id.
     */
    public function getCouponId(): ?int
    {
        return $this->coupon_id;
    }

    /**
     * Set coupon_cd.
     *
     * @return CouponOrder
     */
    public function setCouponCd(?string $couponCd): self
    {
        $this->coupon_cd = $couponCd;

        return $this;
    }

    /**
     * Get coupon_cd.
     */
    public function getCouponCd(): ?string
    {
        return $this->coupon_cd;
    }

    /**
     * Set user_id.
     *
     * @return CouponOrder
     */
    public function setUserId(?int $userId): self
    {
        $this->user_id = $userId;

        return $this;
    }

    /**
     * Get user_id.
     */
    public function getUserId(): ?int
    {
        return $this->user_id;
    }

    /**
     * Set email.
     *
     * @return CouponOrder
     */
    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email.
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * Set order_id.
     *
     * @return CouponOrder
     */
    public function setOrderId(?int $orderId): self
    {
        $this->order_id = $orderId;

        return $this;
    }

    /**
     * Get order_id.
     */
    public function getOrderId(): ?int
    {
        return $this->order_id;
    }

    /**
     * Set pre_order_id.
     *
     * @return CouponOrder
     */
    public function setPreOrderId(?string $preOrderId): self
    {
        $this->pre_order_id = $preOrderId;

        return $this;
    }

    /**
     * Get pre_order_id.
     */
    public function getPreOrderId(): ?string
    {
        return $this->pre_order_id;
    }

    /**
     * Set order_date.
     *
     * @return CouponOrder
     */
    public function setOrderDate(?\DateTime $orderDate): self
    {
        $this->order_date = $orderDate;

        return $this;
    }

    /**
     * Get order_date.
     */
    public function getOrderDate(): ?\DateTime
    {
        return $this->order_date;
    }

    /**
     * Set discount.
     *
     * @return CouponOrder
     */
    public function setDiscount(string|int|float|null $discount): self
    {
        $this->discount = $discount;

        return $this;
    }

    /**
     * Get discount.
     */
    public function getDiscount(): ?string
    {
        return $this->discount;
    }

    /**
     * Set del_flg.
     *
     * @return CouponOrder
     */
    public function setVisible(?bool $visible): self
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * is visible.
     */
    public function isVisible(): ?bool
    {
        return $this->visible;
    }

    /**
     * Set create_date.
     *
     * @return CouponOrder
     */
    public function setCreateDate(?\DateTime $createDate): self
    {
        $this->create_date = $createDate;

        return $this;
    }

    /**
     * Get create_date.
     */
    public function getCreateDate(): ?\DateTime
    {
        return $this->create_date;
    }

    /**
     * Set update_date.
     *
     * @return CouponOrder
     */
    public function setUpdateDate(?\DateTime $updateDate): self
    {
        $this->update_date = $updateDate;

        return $this;
    }

    /**
     * Get update_date.
     */
    public function getUpdateDate(): ?\DateTime
    {
        return $this->update_date;
    }

    public function getCouponName(): ?string
    {
        return $this->coupon_name;
    }

    /**
     * @return $this
     */
    public function setCouponName(?string $coupon_name): self
    {
        $this->coupon_name = $coupon_name;

        return $this;
    }

    public function getOrderChangeStatus(): ?bool
    {
        return $this->order_change_status;
    }

    /**
     * @return $this
     */
    public function setOrderChangeStatus(bool|int|null $orderChangeStatus): self
    {
        $this->order_change_status = $orderChangeStatus;

        return $this;
    }

    public function getOrderItemId(): ?int
    {
        return $this->order_item_id;
    }

    /**
     * @return $this
     */
    public function setOrderItemId(int $order_item_id): self
    {
        $this->order_item_id = $order_item_id;

        return $this;
    }
}
