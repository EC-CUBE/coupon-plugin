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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\AbstractEntity;
use Plugin\Coupon44\Repository\CouponRepository;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Coupon
 */
#[ORM\Table(name: 'plg_coupon')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discriminator_type', type: 'string', length: 255)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: CouponRepository::class)]
#[UniqueEntity('coupon_cd')]
class Coupon extends AbstractEntity
{
    public const PRODUCT = 1;
    public const CATEGORY = 2;
    public const ALL = 3;

    public const DISCOUNT_PRICE = 1;
    public const DISCOUNT_RATE = 2;

    #[ORM\Column(name: 'coupon_id', type: Types::INTEGER, options: ['unsigned' => true])]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    #[ORM\Column(name: 'coupon_cd', type: Types::STRING, nullable: true, length: 20, unique: true)]
    private ?string $coupon_cd = null;

    #[ORM\Column(name: 'coupon_type', type: Types::SMALLINT, nullable: true)]
    private ?int $coupon_type = null;

    #[ORM\Column(name: 'coupon_name', type: Types::STRING, nullable: true, length: 50)]
    private ?string $coupon_name = null;

    #[ORM\Column(name: 'discount_type', type: Types::SMALLINT, nullable: true)]
    private ?int $discount_type = null;

    #[ORM\Column(name: 'coupon_use_time', type: Types::INTEGER, nullable: true)]
    private ?int $coupon_use_time = null;

    #[ORM\Column(name: 'discount_price', type: Types::DECIMAL, nullable: true, precision: 12, scale: 2, options: ['unsigned' => true, 'default' => 0])]
    private ?string $discount_price = null;

    #[ORM\Column(name: 'discount_rate', type: Types::DECIMAL, nullable: true, precision: 10, scale: 0, options: ['unsigned' => true, 'default' => 0])]
    private ?string $discount_rate = null;

    #[ORM\Column(name: 'enable_flag', type: Types::BOOLEAN, nullable: false, options: ['default' => true])]
    private ?bool $enable_flag = null;

    #[ORM\Column(name: 'available_from_date', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTime $available_from_date = null;

    #[ORM\Column(name: 'available_to_date', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTime $available_to_date = null;

    #[ORM\Column(name: 'visible', type: Types::BOOLEAN, options: ['default' => true])]
    private ?bool $visible = null;

    #[ORM\Column(name: 'coupon_member', type: Types::BOOLEAN, options: ['default' => false])]
    private ?bool $coupon_member = null;

    #[ORM\Column(name: 'coupon_lower_limit', type: Types::DECIMAL, nullable: true, precision: 12, scale: 2, options: ['unsigned' => true, 'default' => 0])]
    private ?string $coupon_lower_limit = null;

    /**
     * The number of coupon release
     */
    #[ORM\Column(name: 'coupon_release', type: Types::INTEGER, nullable: false)]
    private ?int $coupon_release = null;

    #[ORM\Column(name: 'create_date', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTime $create_date = null;

    #[ORM\Column(name: 'update_date', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTime $update_date = null;

    /**
     * @var Collection<int, CouponDetail>
     */
    #[ORM\OneToMany(targetEntity: CouponDetail::class, mappedBy: 'Coupon', cascade: ['persist', 'remove'])]
    private $CouponDetails;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->CouponDetails = new ArrayCollection();
    }

    /**
     * Get id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set coupon_cd.
     *
     * @return Coupon
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
     * Set coupon_type.
     *
     * @return Coupon
     */
    public function setCouponType(?int $couponType): self
    {
        $this->coupon_type = $couponType;

        return $this;
    }

    /**
     * Get coupon_type.
     */
    public function getCouponType(): ?int
    {
        return $this->coupon_type;
    }

    /**
     * Set coupon_name.
     *
     * @return Coupon
     */
    public function setCouponName(?string $couponName): self
    {
        $this->coupon_name = $couponName;

        return $this;
    }

    /**
     * Get coupon_name.
     */
    public function getCouponName(): ?string
    {
        return $this->coupon_name;
    }

    /**
     * Set discount_type.
     *
     * @return Coupon
     */
    public function setDiscountType(?int $discountType): self
    {
        $this->discount_type = $discountType;

        return $this;
    }

    /**
     * Get discount_type.
     */
    public function getDiscountType(): ?int
    {
        return $this->discount_type;
    }

    /**
     * Set coupon_use_time.
     *
     * @return Coupon
     */
    public function setCouponUseTime(?int $couponUseTime): self
    {
        $this->coupon_use_time = $couponUseTime;

        return $this;
    }

    /**
     * Get coupon_use_time.
     */
    public function getCouponUseTime(): ?int
    {
        return $this->coupon_use_time;
    }

    /**
     * Set discount_price.
     *
     * @return Coupon
     */
    public function setDiscountPrice(string|int|float|null $discountPrice): self
    {
        $this->discount_price = $discountPrice;

        return $this;
    }

    /**
     * Get discount_price.
     */
    public function getDiscountPrice(): ?string
    {
        return $this->discount_price;
    }

    /**
     * Set discount_rate.
     *
     * @return Coupon
     */
    public function setDiscountRate(string|int|float|null $discountRate): self
    {
        $this->discount_rate = $discountRate;

        return $this;
    }

    /**
     * Get discount_rate.
     */
    public function getDiscountRate(): ?string
    {
        return $this->discount_rate;
    }

    /**
     * Set enable_flag.
     *
     * @return Coupon
     */
    public function setEnableFlag(bool|int|null $enableFlag): self
    {
        $this->enable_flag = $enableFlag;

        return $this;
    }

    /**
     * Get enable_flag.
     */
    public function getEnableFlag(): ?bool
    {
        return $this->enable_flag;
    }

    /**
     * Set available_from_date.
     *
     * @return Coupon
     */
    public function setAvailableFromDate(?\DateTime $availableFromDate): self
    {
        $this->available_from_date = $availableFromDate;

        return $this;
    }

    /**
     * Get available_from_date.
     */
    public function getAvailableFromDate(): ?\DateTime
    {
        return $this->available_from_date;
    }

    /**
     * Set available_to_date.
     *
     * @return Coupon
     */
    public function setAvailableToDate(?\DateTime $availableToDate): self
    {
        $this->available_to_date = $availableToDate;

        return $this;
    }

    /**
     * Get available_to_date.
     */
    public function getAvailableToDate(): ?\DateTime
    {
        return $this->available_to_date;
    }

    /**
     * Set del_flg.
     *
     * @return Coupon
     */
    public function setVisible(?bool $visible): self
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * Get del_flg.
     */
    public function isVisible(): ?bool
    {
        return $this->visible;
    }

    /**
     * Set create_date.
     *
     * @return Coupon
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
     * @return Coupon
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

    /**
     * Add CouponDetails.
     *
     * @return Coupon
     */
    public function addCouponDetail(CouponDetail $couponDetails): self
    {
        $this->CouponDetails[] = $couponDetails;

        return $this;
    }

    /**
     * Remove CouponDetails.
     */
    public function removeCouponDetail(CouponDetail $couponDetails): void
    {
        $this->CouponDetails->removeElement($couponDetails);
    }

    /**
     * Get CouponDetails.
     *
     * @return Collection<int, CouponDetail>
     */
    public function getCouponDetails(): Collection
    {
        return $this->CouponDetails;
    }

    public function getCouponMember(): ?bool
    {
        return $this->coupon_member;
    }

    /**
     * @return Coupon
     */
    public function setCouponMember(bool|int|null $couponMember): self
    {
        $this->coupon_member = $couponMember;

        return $this;
    }

    public function getCouponLowerLimit(): ?string
    {
        return $this->coupon_lower_limit;
    }

    /**
     * @return Coupon
     */
    public function setCouponLowerLimit(string|int|float|null $couponLowerLimit): self
    {
        $this->coupon_lower_limit = $couponLowerLimit;

        return $this;
    }

    public function getCouponRelease(): ?int
    {
        return $this->coupon_release;
    }

    /**
     * @return Coupon
     */
    public function setCouponRelease(?int $coupon_release): self
    {
        $this->coupon_release = $coupon_release;

        return $this;
    }
}
