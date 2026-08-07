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

namespace Plugin\Coupon44\Tests\Form\Type;

use Eccube\Tests\Form\Type\AbstractTypeTestCase;
use Plugin\Coupon44\Entity\Coupon;
use Plugin\Coupon44\Form\Type\CouponType;

class CouponTypeTest extends AbstractTypeTestCase
{
    /** @var \Symfony\Component\Form\FormInterface */
    protected $form;

    /** @var array<string, mixed> デフォルト値（正常系）を設定 */
    protected $formData = [
        'coupon_cd' => 'aaaaa',
        'coupon_name' => 'test',
        'coupon_type' => Coupon::ALL,
        'coupon_member' => '0',
        'discount_type' => Coupon::DISCOUNT_PRICE,
        'coupon_lower_limit' => 0,
        'discount_price' => 0,
        'discount_rate' => 1,
        'available_from_date' => null,
        'available_to_date' => null,
        'coupon_release' => 1,
        'coupon_use_time' => null,
        'CouponDetails' => [],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // CSRF tokenを無効にしてFormを作成
        $this->form = $this->formFactory
            ->createBuilder(CouponType::class, null, [
                'csrf_protection' => false,
            ])
            ->getForm();
        $this->formData['available_from_date'] = (new \DateTime())->format('Y-m-d');
        $this->formData['available_to_date'] = (new \DateTime())->format('Y-m-d');
    }

    public function testValidDataDiscountPrice(): void
    {
        $this->form->submit($this->formData);
        $this->assertTrue($this->form->isValid());
    }

    public function testValidDataDiscountRate(): void
    {
        $this->formData['discount_type'] = Coupon::DISCOUNT_RATE;
        $this->form->submit($this->formData);
        $this->assertTrue($this->form->isValid());
    }

    public function testInvalidCouponCdBlank(): void
    {
        $this->formData['coupon_cd'] = '';
        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }

    public function testInvalidCouponCdInvaldCdStyle(): void
    {
        $this->formData['coupon_cd'] = 'aaa-aa';
        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }

    public function testInvalidCouponNameBlank(): void
    {
        $this->formData['coupon_name'] = '';
        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }

    public function testInvalidCouponTypeBlank(): void
    {
        $this->formData['coupon_type'] = '';
        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }

    public function testInvalidCouponTypeInvalidValue(): void
    {
        $this->formData['coupon_type'] = '99';
        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }

    public function testInvalidCouponMemberBlank(): void
    {
        $this->formData['coupon_member'] = '';
        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }

    public function testInvalidCouponMemberInvalidValue(): void
    {
        $this->formData['coupon_member'] = '99';
        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }

    public function testInvalidCouponDiscountTypeBlank(): void
    {
        $this->formData['discount_type'] = '';
        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }

    public function testInvalidCouponDiscountTypeInvalidValue(): void
    {
        $this->formData['discount_type'] = '99';
        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }

    public function testValidCouponLowerLimitBlank(): void
    {
        $this->formData['coupon_lower_limit'] = '';
        $this->form->submit($this->formData);
        $this->assertTrue($this->form->isValid());
    }

    public function testValidCouponLowerEqualsMinValue(): void
    {
        $this->formData['coupon_lower_limit'] = '0';
        $this->form->submit($this->formData);
        $this->assertTrue($this->form->isValid());
    }

    public function testInvalidCouponLowerLimitLessThanMinValue(): void
    {
        $this->formData['coupon_lower_limit'] = '-1';
        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }

    public function testInvalidCouponDiscountPriceBlankWhenDiscountTypePrice(): void
    {
        $this->formData['discount_type'] = Coupon::DISCOUNT_PRICE;
        $this->formData['discount_price'] = '';
        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }

    public function testValidCouponDiscountPriceEqualsMinValue(): void
    {
        $this->formData['discount_type'] = Coupon::DISCOUNT_PRICE;
        $this->formData['discount_price'] = '0';
        $this->form->submit($this->formData);
        $this->assertTrue($this->form->isValid());
    }

    public function testInvalidCouponDiscountPriceLessThanMinValue(): void
    {
        $this->formData['discount_type'] = Coupon::DISCOUNT_PRICE;
        $this->formData['discount_price'] = '-1';
        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }

    public function testInvalidCouponDiscountRateBlankWhenDiscountTypeRate(): void
    {
        $this->formData['discount_type'] = Coupon::DISCOUNT_RATE;
        $this->formData['discount_rate'] = '';
        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }

    public function testValidCouponDiscountRateEqualsMinValue(): void
    {
        $this->formData['discount_type'] = Coupon::DISCOUNT_RATE;
        $this->formData['discount_rate'] = '1';
        $this->form->submit($this->formData);
        $this->assertTrue($this->form->isValid());
    }

    public function testInvalidCouponDiscountRateLessThanMinValue(): void
    {
        $this->formData['discount_type'] = Coupon::DISCOUNT_RATE;
        $this->formData['discount_rate'] = '0';
        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }

    public function testValidCouponDiscountRateEqualsMaxValue(): void
    {
        $this->formData['discount_type'] = Coupon::DISCOUNT_RATE;
        $this->formData['discount_rate'] = '100';
        $this->form->submit($this->formData);
        $this->assertTrue($this->form->isValid());
    }

    public function testInvalidCouponDiscountRateGreaterThanMaxValue(): void
    {
        $this->formData['discount_type'] = Coupon::DISCOUNT_RATE;
        $this->formData['discount_rate'] = '101';
        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }

    public function testInvalidCouponAvailableFromDateBlank(): void
    {
        $this->formData['available_from_date'] = '';
        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }

    public function testInvalidAvailableFromDateInvalidValue(): void
    {
        $this->formData['available_from_date'] = '20000101';
        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }

    public function testInvalidCouponAvailableToDateBlank(): void
    {
        $this->formData['available_to_date'] = '';
        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }

    public function testInvalidAvailableToDateInvalidValue(): void
    {
        $this->formData['available_to_date'] = '20000101';
        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }

    public function testInvalidAvailableToDateGreaterThanFromDate(): void
    {
        $this->formData['available_to_date'] = (new \DateTime())->modify('-1 day')->format('Y-m-d');
        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }

    public function testInvalidCouponReleaseBlank(): void
    {
        $this->formData['coupon_release'] = '';
        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }

    public function testValidCouponReleaseEualsMinValue(): void
    {
        $this->formData['coupon_release'] = '1';
        $this->form->submit($this->formData);
        $this->assertTrue($this->form->isValid());
    }

    public function testInvalidCouponReleaseLessThanMinValue(): void
    {
        $this->formData['coupon_release'] = '0';
        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }

    public function testInvalidCouponReleaseEqualsMaxValue(): void
    {
        $this->formData['coupon_release'] = '1000000';
        $this->form->submit($this->formData);
        $this->assertTrue($this->form->isValid());
    }

    public function testInvalidCouponReleaseGreaterThanMaxValue(): void
    {
        $this->formData['coupon_release'] = '1000001';
        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }
}
