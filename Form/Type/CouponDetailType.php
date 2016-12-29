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

use Eccube\Form\DataTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class CouponDetailType.
 */
class CouponDetailType extends AbstractType
{
    /**
     * @var \Eccube\Application
     */
    protected $app;

    /**
     * CouponDetailType constructor.
     *
     * @param \Eccube\Application $app
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * buildForm.
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                $builder->create('Product', 'hidden')
                    ->addModelTransformer(new DataTransformer\EntityToIdTransformer($this->app['orm.em'], '\Eccube\Entity\Product'))
            )
            ->add(
                $builder->create('Category', 'hidden')
                    ->addModelTransformer(new DataTransformer\EntityToIdTransformer($this->app['orm.em'], '\Eccube\Entity\Category'))
            )
            ->add('id', 'hidden', array(
                'label' => 'クーポン詳細ID',
                'required' => false,
            ))
            ->add('coupon_type', 'hidden', array(
                'required' => false,
            ));
    }

    /**
     * configureOptions.
     *
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Plugin\Coupon\Entity\CouponDetail',
        ));
    }

    /**
     * getName.
     *
     *  @return string
     */
    public function getName()
    {
        return 'admin_plugin_coupon_detail';
    }
}
