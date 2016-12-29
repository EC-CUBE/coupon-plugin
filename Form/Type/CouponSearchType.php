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
 * Class CouponSearchType.
 */
class CouponSearchType extends AbstractType
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
            ->add('id', 'text', array(
                'label' => 'クーポンID',
                'required' => false,
            ))
            ->add('coupon_cd', 'text', array(
                'label' => 'クーポンコード',
                'required' => false,
            ));
    }

    /**
     * getName.
     *
     * @return string
     */
    public function getName()
    {
        return 'admin_plugin_coupon_search';
    }
}
