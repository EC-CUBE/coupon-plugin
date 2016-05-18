<?php
namespace Plugin\Coupon\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class CouponUseType extends AbstractType
{

    protected $app;

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('coupon_cd', 'text', array(
                'label' => 'クーポンコード',
                'required' => true,
                'trim' => true,
                'mapped' => false,
            ));
    }

    public function getName()
    {
        return 'shopping_coupon';
    }
}