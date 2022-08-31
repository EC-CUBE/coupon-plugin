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

namespace Plugin\Coupon42\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

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
            ])
            ->addEventListener(FormEvents::POST_SUBMIT, function(FormEvent $event) {
                $form = $event->getForm();
                $data = $form->getData();
                if ($data['coupon_use'] == 1 && empty($form['coupon_cd']->getData())) {
                    $form['coupon_cd']->addError(new FormError(trans('plugin_coupon.front.shopping_coupon.body')));
                }
            });
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
