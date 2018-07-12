<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\Coupon\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
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
            ->add('coupon_cd', TextType::class, [
                'label' => 'plugin_coupon.front.code',
                'required' => false,
                'trim' => true,
                'mapped' => false,
            ])
            ->add('coupon_use', ChoiceType::class, [
                'choices' => array_flip([0 => 'plugin_coupon.front.shopping_coupon.remove', 1 => 'plugin_coupon.front.shopping_coupon.use']),
                'required' => true,
                'expanded' => true,
                'multiple' => false,
                'data' => 1, // default choice
            ]);
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
