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

use Carbon\Carbon;
use Plugin\Coupon\Entity\Coupon;
use Plugin\Coupon\Repository\CouponRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolationList;
use Eccube\Application;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class CouponType.
 */
class CouponType extends AbstractType
{
    /**
     * @var CouponRepository
     */
    private $couponRepository;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * CouponType constructor.
     * @param CouponRepository $couponRepository
     * @param ValidatorInterface $validator
     * @param ContainerInterface $container
     */
    public function __construct(CouponRepository $couponRepository, ValidatorInterface $validator, ContainerInterface $container)
    {
        $this->couponRepository = $couponRepository;
        $this->validator = $validator;
        $this->container = $container;
    }


    /**
     * buildForm.
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $currency = $this->container->getParameter('currency');
        $builder
            ->add('coupon_cd', TextType::class, array(
                'label' => 'クーポンコード',
                'required' => true,
                'trim' => true,
                'constraints' => array(
                    new Assert\NotBlank(),
                    new Assert\Regex(array('pattern' => '/^[a-zA-Z0-9]+$/i')),
                ),
            ))
            ->add('coupon_name', TextType::class, array(
                'label' => 'クーポン名',
                'required' => true,
                'trim' => true,
                'constraints' => array(
                    new Assert\NotBlank(),
                ),
            ))
            ->add('coupon_type', ChoiceType::class, array(
                'choices' => array_flip([
                    Coupon::PRODUCT => '商品',
                    Coupon::CATEGORY => 'カテゴリ',
                    Coupon::ALL => '全商品'
                ]),
                'required' => true,
                'expanded' => true,
                'multiple' => false,
                'label' => '対象商品',
                'constraints' => array(
                    new Assert\NotBlank(),
                ),
            ))
            ->add('coupon_member', ChoiceType::class, array(
                'choices' => array_flip([
                    1 => '会員のみ',
                    0 => 'なし'
                ]),
                'required' => true,
                'expanded' => true,
                'multiple' => false,
                'label' => '利用制限',
                'constraints' => array(
                    new Assert\NotBlank(),
                ),
            ))
            ->add('discount_type', ChoiceType::class, array(
                'choices' => array_flip([
                    Coupon::DISCOUNT_PRICE => '値引き額',
                    Coupon::DISCOUNT_RATE => '値引率'
                ]),
                'required' => true,
                'expanded' => true,
                'multiple' => false,
                'label' => '値引き種別',
                'constraints' => array(
                    new Assert\NotBlank(),
                ),
            ))
            ->add('coupon_lower_limit', MoneyType::class, array(
                'label' => '下限金額(円)',
                'required' => false,
                'currency' => $currency,
                'constraints' => array(
                    new Assert\Range(array(
                        'min' => 0,
                    )),
                ),
            ))
            ->add('discount_price', MoneyType::class, array(
                'label' => '値引き額(円)',
                'required' => false,
                'currency' => $currency,
                'constraints' => array(
                    new Assert\Range(array(
                        'min' => 0,
                    )),
                ),
            ))
            ->add('discount_rate', IntegerType::class, array(
                'label' => '値引率(％)',
                'required' => false,
                'constraints' => array(
                    new Assert\Range(array(
                        'min' => 1,
                        'max' => 100,
                    )),
                ),
            ))
            // 有効期間(FROM)
            ->add('available_from_date', DateType::class, array(
                'label' => '有効期間',
                'required' => true,
                'input' => 'datetime',
                'widget' => 'single_text',
                'constraints' => array(
                    new Assert\NotBlank(),
                ),
            ))
            // 有効期間(TO)
            ->add('available_to_date', DateType::class, array(
                'label' => '有効期間日(TO)',
                'required' => true,
                'input' => 'datetime',
                'widget' => 'single_text',
                'constraints' => array(
                    new Assert\NotBlank(),
                ),
            ))
            ->add('coupon_release', IntegerType::class, array(
                'label' => '発行枚数',
                'required' => true,
                'constraints' => array(
                    new Assert\NotBlank(),
                    new Assert\Range(array(
                        'min' => 1,
                        'max' => 1000000,
                    )),
                ),
            ))
            ->add('coupon_use_time', HiddenType::class, array())
            ->add('CouponDetails', CollectionType::class, array(
                'entry_type' => CouponDetailType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
            ))
            ->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
                $form = $event->getForm();
                $data = $form->getData();
                if (count($data['CouponDetails']) == 0 && $data['coupon_type'] != Coupon::ALL) {
                    $form['coupon_type']->addError(new FormError('admin.plugin.coupon.coupontype'));
                }

                if ($data['discount_type'] == Coupon::DISCOUNT_PRICE) {
                    // 値引き額
                    /** @var ConstraintViolationList $errors */
                    $errors = $this->validator->validate($data['discount_price'], array(
                        new Assert\NotBlank(),
                    ));
                    if ($errors->count() > 0) {
                        foreach ($errors as $error) {
                            $form['discount_price']->addError(new FormError($error->getMessage()));
                        }
                    }
                } elseif ($data['discount_type'] == Coupon::DISCOUNT_RATE) {
                    // 値引率
                    /** @var ConstraintViolationList $errors */
                    $errors = $this->validator->validate($data['discount_rate'], array(
                        new Assert\NotBlank(),
                        new Assert\Range(array(
                            'min' => 0,
                            'max' => 100,
                        )),
                    ));
                    if ($errors->count() > 0) {
                        foreach ($errors as $error) {
                            $form['discount_rate']->addError(new FormError($error->getMessage()));
                        }
                    }
                }

                if (!empty($data['available_from_date']) && !empty($data['available_to_date'])) {
                    $fromDate = Carbon::instance($data['available_from_date']);
                    $toDate = Carbon::instance($data['available_to_date']);
                    if ($fromDate->gt($toDate)) {
                        $form['available_from_date']->addError(new FormError('admin.plugin.coupon.avaiabledate'));
                    }
                }
            });
    }

    /**
     * configureOptions
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Plugin\Coupon\Entity\Coupon',
        ));
    }

    /**
     * getName.
     *
     * @return string
     */
    public function getName()
    {
        return 'admin_plugin_coupon';
    }
}
