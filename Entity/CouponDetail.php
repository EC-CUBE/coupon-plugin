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
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\AbstractEntity;
use Eccube\Entity\Category;
use Eccube\Entity\Product;
use Plugin\Coupon44\Repository\CouponDetailRepository;

/**
 * Coupon Detail
 */
#[ORM\Table(name: 'plg_coupon_detail')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discriminator_type', type: 'string', length: 255)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: CouponDetailRepository::class)]
class CouponDetail extends AbstractEntity
{
    #[ORM\Column(name: 'coupon_detail_id', type: Types::INTEGER, options: ['unsigned' => true])]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    #[ORM\Column(name: 'coupon_type', type: Types::SMALLINT, nullable: true)]
    private ?int $coupon_type = null;

    #[ORM\Column(name: 'visible', type: Types::BOOLEAN, options: ['default' => true])]
    private ?bool $visible = null;

    #[ORM\Column(name: 'create_date', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTime $create_date = null;

    #[ORM\Column(name: 'update_date', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTime $update_date = null;

    #[ORM\ManyToOne(targetEntity: Coupon::class, inversedBy: 'CouponDetails')]
    #[ORM\JoinColumn(name: 'coupon_id', referencedColumnName: 'coupon_id')]
    private ?Coupon $Coupon = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(name: 'product_id', referencedColumnName: 'id')]
    private ?Product $Product = null;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id')]
    private ?Category $Category = null;

    /**
     * Get id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set id.
     *
     * @return CouponDetail
     */
    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set coupon_type.
     *
     * @return CouponDetail
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
     * Set del_flg.
     *
     * @return CouponDetail
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
     * @return CouponDetail
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
     * @return CouponDetail
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
     * Set Coupon.
     *
     * @return CouponDetail
     */
    public function setCoupon(Coupon $coupon): self
    {
        $this->Coupon = $coupon;

        return $this;
    }

    /**
     * Get Coupon.
     */
    public function getCoupon(): ?Coupon
    {
        return $this->Coupon;
    }

    /**
     * Set Product.
     *
     * @return CouponDetail
     */
    public function setProduct(?Product $product = null): self
    {
        $this->Product = $product;

        return $this;
    }

    /**
     * Get Product.
     */
    public function getProduct(): ?Product
    {
        return $this->Product;
    }

    /**
     * Set Category.
     *
     * @return CouponDetail
     */
    public function setCategory(?Category $category = null): self
    {
        $this->Category = $category;

        return $this;
    }

    /**
     * Get Category.
     */
    public function getCategory(): ?Category
    {
        return $this->Category;
    }

    /**
     * 親カテゴリ名を含むカテゴリ名を取得する.
     */
    public function getCategoryFullName(): ?string
    {
        try {
            if (is_null($this->Category)) {
                return null;
            }
            $fulName = $this->Category->getName();
            // 親カテゴリがない場合はカテゴリ名を返す.
            if (is_null($this->Category->getParent())) {
                return $fulName;
            }
            // 親カテゴリ名を結合する
            $ParentCategory = $this->Category->getParent();
            while (!is_null($ParentCategory)) {
                $fulName = $ParentCategory->getName().'　＞　'.$fulName;
                $ParentCategory = $ParentCategory->getParent();
            }

            return $fulName;
        } catch (EntityNotFoundException) {
            return null;
        }
    }

    /**
     * get product name.
     */
    public function getProductName(): ?string
    {
        try {
            if (is_null($this->Product)) {
                return null;
            }
            $fulName = $this->Product->getName();

            return $fulName;
        } catch (EntityNotFoundException) {
            return null;
        }
    }
}
