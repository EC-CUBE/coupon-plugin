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

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class CouponUseType.
 */
class CouponUseType extends AbstractType
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
            ->add('coupon_cd', 'text', array(
                'label' => 'クーポンコード',
                'required' => false,
                'trim' => true,
                'mapped' => false,
            ))
            ->add('coupon_use', 'choice', array(
                'choices' => array(0 => 'クーポンを利用しない', 1 => 'クーポンを利用する'),
                'required' => true,
                'expanded' => true,
                'multiple' => false,
                'label' => '',
                'data' => 1,
                'empty_value' => false,
            ));
    }

    /**
     * getName.
     *
     * @return string
     */
    public function getName()
    {
        return 'front_plugin_coupon_shopping';
    }
}
