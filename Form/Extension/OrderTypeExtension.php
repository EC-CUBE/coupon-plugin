<?php

namespace Plugin\Coupon4\Form\Extension;

use Eccube\Form\Type\Shopping\OrderType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class OrderTypeExtension extends AbstractTypeExtension
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('coupon_cd', TextType::class, [
                'label' => 'plugin_coupon.front.code',
                'required' => false,
                'trim' => true,
                'mapped' => false,
            ]);

    }

    public function getExtendedType()
    {
        return OrderType::class;
    }
}
