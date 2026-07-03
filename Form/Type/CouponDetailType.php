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

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Category;
use Eccube\Entity\Product;
use Eccube\Form\DataTransformer\EntityToIdTransformer;
use Plugin\Coupon44\Entity\CouponDetail;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class CouponDetailType.
 */
class CouponDetailType extends AbstractType
{
    /**
     * CouponDetailType constructor.
     *
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        protected EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * buildForm.
     *
     * @param FormBuilderInterface $builder
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                $builder->create('Product', HiddenType::class)
                    ->addModelTransformer(new EntityToIdTransformer($this->entityManager, Product::class))
            )
            ->add(
                $builder->create('Category', HiddenType::class)
                    ->addModelTransformer(new EntityToIdTransformer($this->entityManager, Category::class))
            )
            ->add('id', HiddenType::class, [
                'label' => 'クーポン詳細ID',
                'required' => false,
            ])
            ->add('coupon_type', HiddenType::class, [
                'required' => false,
            ]);
    }

    /**
     * configureOptions.
     *
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CouponDetail::class,
        ]);
    }

    /**
     * getBlockPrefix.
     */
    public function getBlockPrefix(): string
    {
        return 'coupon_detail';
    }
}
