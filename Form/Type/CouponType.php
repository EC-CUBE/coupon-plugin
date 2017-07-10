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
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolationList;
use Eccube\Application;

/**
 * Class CouponType.
 */
class CouponType extends AbstractType
{
    /**
     * @var Application
     */
    private $app;

    /**
     * CouponType constructor.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
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
        $app = $this->app;
        $builder
            ->add('coupon_cd', 'text', array(
                'label' => 'クーポンコード',
                'required' => true,
                'trim' => true,
                'constraints' => array(
                    new Assert\NotBlank(),
                    new Assert\Regex(array('pattern' => '/^[a-zA-Z0-9]+$/i')),
                ),
            ))
            ->add('coupon_name', 'text', array(
                'label' => 'クーポン名',
                'required' => true,
                'trim' => true,
                'constraints' => array(
                    new Assert\NotBlank(),
                ),
            ))
            ->add('coupon_type', 'choice', array(
                'choices' => array(1 => '商品', 2 => 'カテゴリ', 3 => '全商品'),
                'required' => true,
                'expanded' => true,
                'multiple' => false,
                'label' => '対象商品',
                'empty_value' => false,
                'constraints' => array(
                    new Assert\NotBlank(),
                ),
            ))
            ->add('coupon_member', 'choice', array(
                'choices' => array(1 => '会員のみ', 0 => 'なし'),
                'required' => true,
                'expanded' => true,
                'multiple' => false,
                'label' => '利用制限',
                'empty_value' => false,
                'constraints' => array(
                    new Assert\NotBlank(),
                ),
            ))
            ->add('discount_type', 'choice', array(
                'choices' => array(1 => '値引き額', 2 => '値引率'),
                'required' => true,
                'expanded' => true,
                'multiple' => false,
                'label' => '値引き種別',
                'constraints' => array(
                    new Assert\NotBlank(),
                ),
            ))
            ->add('coupon_lower_limit', 'money', array(
                'label' => '下限金額(円)',
                'required' => false,
                'currency' => 'JPY',
                'precision' => 0,
                'constraints' => array(
                    new Assert\Range(array(
                        'min' => 0,
                    )),
                ),
            ))
            ->add('discount_price', 'money', array(
                'label' => '値引き額(円)',
                'required' => false,
                'currency' => 'JPY',
                'precision' => 0,
                'constraints' => array(
                    new Assert\Range(array(
                        'min' => 0,
                    )),
                ),
            ))
            ->add('discount_rate', 'integer', array(
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
            ->add('available_from_date', 'date', array(
                'label' => '有効期間',
                'required' => true,
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'empty_value' => array('year' => '----', 'month' => '--', 'day' => '--'),
                'constraints' => array(
                    new Assert\NotBlank(),
                ),
            ))
            // 有効期間(TO)
            ->add('available_to_date', 'date', array(
                'label' => '有効期間日(TO)',
                'required' => true,
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'empty_value' => array('year' => '----', 'month' => '--', 'day' => '--'),
                'constraints' => array(
                    new Assert\NotBlank(),
                ),
            ))
            ->add('coupon_release', 'integer', array(
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
            ->add('coupon_use_time', 'hidden', array())
            ->add('CouponDetails', 'collection', array(
                'type' => 'admin_plugin_coupon_detail',
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
            ))
            ->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use ($app) {
                $form = $event->getForm();
                $data = $form->getData();
                if (count($data['CouponDetails']) == 0 && $data['coupon_type'] != 3) {
                    $form['coupon_type']->addError(new FormError($app->trans('admin.plugin.coupon.coupontype')));
                }

                if ($data['discount_type'] == 1) {
                    // 値引き額
                    /** @var ConstraintViolationList $errors */
                    $errors = $app['validator']->validateValue($data['discount_price'], array(
                        new Assert\NotBlank(),
                    ));
                    if ($errors->count() > 0) {
                        foreach ($errors as $error) {
                            $form['discount_price']->addError(new FormError($error->getMessage()));
                        }
                    }
                } elseif ($data['discount_type'] == 2) {
                    // 値引率
                    /** @var ConstraintViolationList $errors */
                    $errors = $app['validator']->validateValue($data['discount_rate'], array(
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
                        $form['available_from_date']->addError(new FormError($app->trans('admin.plugin.coupon.avaiabledate')));
                    }
                }

                // 既に登録されているクーポンコードは利用できない
                if (null !== $data->getCouponCd()) {
                    $qb = $app['coupon.repository.coupon']
                        ->createQueryBuilder('c')
                        ->select('COUNT(c)')
                        ->where('c.coupon_cd = :coupon_cd')
                        ->setParameter('coupon_cd', $data->getCouponCd());

                    // 新規登録時.
                    if ($data->getId() === null) {
                        $count = $qb->getQuery()->getSingleScalarResult();
                    } else {
                        $qb->andWhere('c.id <> :coupon_id')->setParameter('coupon_id', $data->getId());
                        $count = $qb->getQuery()->getSingleScalarResult();
                    }
                    if ($count > 0) {
                        $form['coupon_cd']->addError(new FormError($app->trans('admin.plugin.coupon.duplicate')));
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
