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

namespace Plugin\Coupon42\Tests\Form\Type;

use Eccube\Tests\Form\Type\AbstractTypeTestCase;
use Plugin\Coupon42\Entity\Coupon;
use Plugin\Coupon42\Form\Type\CouponType;

class CouponTypeTest extends AbstractTypeTestCase
{
    /** @var \Symfony\Component\Form\FormInterface */
    protected $form;

    /** @var array デフォルト値（正常系）を設定 */
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
        'CouponDetails' => []
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
        $this->formData['available_from_date'] = (new \DateTIme())->format('Y-m-d');
        $this->formData['available_to_date'] = (new \DateTIme())->format('Y-m-d');
    }

    public function testValidDataDiscountPrice()
    {
        $this->form->submit($this->formData);
        $this->assertTrue($this->form->isValid());
    }

    public function testValidDataDiscountRate()
    {
        $this->formData['discount_type'] = Coupon::DISCOUNT_RATE;
        $this->form->submit($this->formData);
        $this->assertTrue($this->form->isValid());
    }

    public function testInvalidCouponCdBlank()
    {
        $this->formData['coupon_cd'] = '';
        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }

    public function testInvalidCouponCdInvaldCdStyle()
    {
        $this->formData['coupon_cd'] = 'aaa-aa';
        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }

    public function testInvalidCouponNameBlank()
    {
        $this->formData['coupon_name'] = '';
        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }

    public function testInvalidCouponTypeBlank()
    {
        $this->formData['coupon_type'] = '';
        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }

    public function testInvalidCouponTypeInvalidValue()
    {
        $this->formData['coupon_type'] = '99';
        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }

    public function testInvalidCouponMemberBlank()
    {
        $this->formData['coupon_member'] = '';
        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }

    public function testInvalidCouponMemberInvalidValue()
    {
        $this->formData['coupon_member'] = '99';
        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }

    public function testInvalidCouponDiscountTypeBlank()
    {
        $this->formData['discount_type'] = '';
        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }

    public function testInvalidCouponDiscountTypeInvalidValue()
    {
        $this->formData['discount_type'] = '99';
        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }

    public function testValidCouponLowerLimitBlank()
    {
        $this->formData['coupon_lower_limit'] = '';
        $this->form->submit($this->formData);
        $this->assertTrue($this->form->isValid());
    }

    public function testValidCouponLowerEqualsMinValue()
    {
        $this->formData['coupon_lower_limit'] = '0';
        $this->form->submit($this->formData);
        $this->assertTrue($this->form->isValid());
    }

    public function testInvalidCouponLowerLimitLessThanMinValue()
    {
        $this->formData['coupon_lower_limit'] = '-1';
        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }

    public function testInvalidCouponDiscountPriceBlankWhenDiscountTypePrice()
    {
        $this->formData['discount_type'] = Coupon::DISCOUNT_PRICE;
        $this->formData['discount_price'] = '';
        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }

    public function testValidCouponDiscountPriceEqualsMinValue()
    {
        $this->formData['discount_type'] = Coupon::DISCOUNT_PRICE;
        $this->formData['discount_price'] = '0';
        $this->form->submit($this->formData);
        $this->assertTrue($this->form->isValid());
    }

    public function testInvalidCouponDiscountPriceLessThanMinValue()
    {
        $this->formData['discount_type'] = Coupon::DISCOUNT_PRICE;
        $this->formData['discount_price'] = '-1';
        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }

    public function testInvalidCouponDiscountRateBlankWhenDiscountTypeRate()
    {
        $this->formData['discount_type'] = Coupon::DISCOUNT_RATE;
        $this->formData['discount_rate'] = '';
        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }

    public function testValidCouponDiscountRateEqualsMinValue()
    {
        $this->formData['discount_type'] = Coupon::DISCOUNT_RATE;
        $this->formData['discount_rate'] = '1';
        $this->form->submit($this->formData);
        $this->assertTrue($this->form->isValid());
    }

    public function testInvalidCouponDiscountRateLessThanMinValue()
    {
        $this->formData['discount_type'] = Coupon::DISCOUNT_RATE;
        $this->formData['discount_rate'] = '0';
        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }

    public function testValidCouponDiscountRateEqualsMaxValue()
    {
        $this->formData['discount_type'] = Coupon::DISCOUNT_RATE;
        $this->formData['discount_rate'] = '100';
        $this->form->submit($this->formData);
        $this->assertTrue($this->form->isValid());
    }

    public function testInvalidCouponDiscountRateGreaterThanMaxValue()
    {
        $this->formData['discount_type'] = Coupon::DISCOUNT_RATE;
        $this->formData['discount_rate'] = '101';
        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }

    public function testInvalidCouponAvailableFromDateBlank()
    {
        $this->formData['available_from_date'] = '';
        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }

    public function testInvalidAvailableFromDateInvalidValue()
    {
        $this->formData['available_from_date'] = '20000101';
        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }

    public function testInvalidCouponAvailableToDateBlank()
    {
        $this->formData['available_to_date'] = '';
        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }

    public function testInvalidAvailableToDateInvalidValue()
    {
        $this->formData['available_to_date'] = '20000101';
        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }

    public function testInvalidAvailableToDateGreaterThanFromDate()
    {
        $this->formData['available_to_date'] = (new \DateTime())->modify('-1 day')->format('Y-m-d');
        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }

    public function testInvalidCouponReleaseBlank()
    {
        $this->formData['coupon_release'] = '';
        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }

    public function testValidCouponReleaseEualsMinValue()
    {
        $this->formData['coupon_release'] = '1';
        $this->form->submit($this->formData);
        $this->assertTrue($this->form->isValid());
    }

    public function testInvalidCouponReleaseLessThanMinValue()
    {
        $this->formData['coupon_release'] = '0';
        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }

    public function testInvalidCouponReleaseEqualsMaxValue()
    {
        $this->formData['coupon_release'] = '1000000';
        $this->form->submit($this->formData);
        $this->assertTrue($this->form->isValid());
    }

    public function testInvalidCouponReleaseGreaterThanMaxValue()
    {
        $this->formData['coupon_release'] = '1000001';
        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }
}
