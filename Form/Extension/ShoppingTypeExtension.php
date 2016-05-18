<?php
namespace Plugin\Coupon\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

class ShoppingTypeExtension extends AbstractTypeExtension
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
                'required' => false,
                'trim' => true,
                'mapped' => false,
            ));
    }

    public function getExtendedType()
    {
        return 'shopping';
    }
}