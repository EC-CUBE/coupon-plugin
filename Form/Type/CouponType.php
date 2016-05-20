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

use Carbon\Carbon;
use Plugin\Coupon\Form\Type;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolationList;

class CouponType extends AbstractType
{

    private $app;

    public function __construct(\Eccube\Application $app)
    {
        $this->app = $app;
    }

    /**
     * Build config type form
     *
     * @param FormBuilderInterface $builder
     * @param array $options
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
                    new Assert\Regex(array(
                            'pattern' => '/^[a-zA-Z0-9]+$/i'
                        )
                    )
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
                'choices' => array(1 => '商品', 2 => 'カテゴリ'),
                'required' => true,
                'expanded' => true,
                'multiple' => false,
                'label' => 'クーポン有効対象',
                'empty_value' => false,
                'constraints' => array(
                    new Assert\NotBlank(),
                )
            ))
            ->add('discount_type', 'choice', array(
                'choices' => array(1 => '値引き額', 2 => '値引率'),
                'required' => true,
                'expanded' => true,
                'multiple' => false,
                'label' => '値引き種別',
                'constraints' => array(
                    new Assert\NotBlank()
                ),
            ))
            ->add('discount_price', 'money', array(
                'label' => '値引き額',
                'required' => false,
                'currency' => 'JPY',
                'precision' => 0
            ))
            ->add('discount_rate', 'integer', array(
                'label' => '値引率',
                'required' => false,
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
                    new Assert\NotBlank()
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
                    new Assert\NotBlank()
                ),
            ))
            ->add('coupon_use_time', 'integer', array(
                'label' => '発行枚数',
                'required' => true,
                'constraints' => array(
                    new Assert\NotBlank(),
                    new Assert\Range(array(
                        'min' => 1,
                        'max' => 100000,
                    ))
                ),
            ))
            ->add('CouponDetails', 'collection', array(
                'type' => new CouponDetailType($this->app),
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
            ))
            ->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use ($app) {
                $form = $event->getForm();
                $data = $form->getData();

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

                } else if ($data['discount_type'] == 2) {
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
                    $now = Carbon::today();
                    $fromDate = Carbon::instance($data['available_from_date']);
                    $toDate = Carbon::instance($data['available_to_date']);

                    if ($fromDate->gt($toDate)) {
                        $form['available_from_date']->addError(new FormError('有効期間に誤りがあります。'));
                    }
                }

                // 既に登録されているクーポンコードは利用できない
                if (null !== $data->getCouponCd()) {
                    $qb = $app['eccube.plugin.coupon.repository.coupon']
                        ->createQueryBuilder('c')
                        ->select('COUNT(c)')
                        ->where('c.coupon_cd = :coupon_cd')
                        ->setParameter('coupon_cd', $data->getCouponCd());

                    // 新規登録時.
                    if ($data->getId() === null) {
                        $count = $qb->getQuery()->getSingleScalarResult();

                    // 編集時.
                    } else {
                        $qb->andWhere('c.id <> :coupon_id')
                            ->setParameter('coupon_id', $data->getId());
                        $count = $qb->getQuery()->getSingleScalarResult();
                    }

                    if ($count > 0) {
                        $form['coupon_cd']->addError(new FormError('既に利用されているクーポンコードです。'));
                    }
                }
            });
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Plugin\Coupon\Entity\CouponCoupon',
        ));
    }


    public function getName()
    {
        return 'admin_coupon';
    }
}
