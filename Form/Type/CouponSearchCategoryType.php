<?php
/*
 * This file is part of the Coupon plugin
 *
 * Copyright (C) 2016 LOCKON CO.,LTD. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\Coupon\Form\Type;

use Eccube\Form\Type\Admin\CategoryType;
use Eccube\Form\Type\Master\ProductStatusType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
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
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('category_id', CategoryType::class, array(
                'label' => 'カテゴリ',
//                'empty_value' => 'すべて',
                'required' => false,
            ))
            ->add('status', ProductStatusType::class, array(
                'label' => '種別',
                'multiple' => true,
                'required' => false,
            ))
            ->add('create_date_start', DateType::class, array(
                'label' => '登録日(FROM)',
                'required' => false,
                'input' => 'datetime',
                'widget' => 'single_text',
//                'format' => 'yyyy-MM-dd',
//                'empty_value' => array('year' => '----', 'month' => '--', 'day' => '--'),
            ))
            ->add('create_date_end', DateType::class, array(
                'label' => '登録日(TO)',
                'required' => false,
                'input' => 'datetime',
                'widget' => 'single_text',
//                'format' => 'yyyy-MM-dd',
//                'empty_value' => array('year' => '----', 'month' => '--', 'day' => '--'),
            ))
            ->add('update_date_start', DateType::class, array(
                'label' => '更新日(FROM)',
                'required' => false,
                'input' => 'datetime',
                'widget' => 'single_text',
//                'format' => 'yyyy-MM-dd',
//                'empty_value' => array('year' => '----', 'month' => '--', 'day' => '--'),
            ))
            ->add('update_date_end', DateType::class, array(
                'label' => '更新日(TO)',
                'required' => false,
                'input' => 'datetime',
                'widget' => 'single_text',
//                'format' => 'yyyy-MM-dd',
//                'empty_value' => array('year' => '----', 'month' => '--', 'day' => '--'),
            ))
            ->add('link_status', HiddenType::class, array(
                'mapped' => false,
            ));
    }

    /**
     * getName.
     *
     * @return string
     */
    public function getName()
    {
        return 'admin_plugin_coupon_search_category';
    }
}
