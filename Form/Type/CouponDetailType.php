<?php
/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) 2000-2015 LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

namespace Plugin\Coupon\Form\Type;

use Eccube\Form\DataTransformer;
use Plugin\Coupon\Form\Type;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

// use Plugin\Coupon\Form\Type\DataTransformer;

class CouponDetailType extends AbstractType
{
    protected $app;

    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add($builder->create('Product', 'hidden')
                ->addModelTransformer(new DataTransformer\EntityToIdTransformer(
                    $this->app['orm.em'],
                    '\Eccube\Entity\Product'
                )))
            ->add($builder->create('Category', 'hidden')
                ->addModelTransformer(new DataTransformer\EntityToIdTransformer(
                    $this->app['orm.em'],
                    '\Eccube\Entity\Category'
                )))
            ->add('id', 'hidden', array(
                'label' => 'クーポン詳細ID',
                'required' => false,
            ))
            ->add('coupon_type', 'hidden', array(
                'required' => false,
            ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Plugin\Coupon\Entity\CouponCouponDetail',
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'admin_coupon_detail';
    }


}

