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

namespace Plugin\Coupon44\Form\Type;

use Eccube\Form\Type\Master\CategoryType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class CouponSearchCategoryType.
 */
class CouponSearchCategoryType extends AbstractType
{
    /**
     * buildForm.
     *
     * @param FormBuilderInterface $builder
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('category_id', CategoryType::class, [
                'label' => 'カテゴリ',
                'required' => false,
                'placeholder' => 'common.select__all_products',
            ]);
    }

    /**
     * getBlockPrefix.
     */
    public function getBlockPrefix(): string
    {
        return 'coupon_search_category';
    }
}
