<?php
namespace Plugin\Coupon\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class CouponUseType
 */
class CouponUseType extends AbstractType
{

    /**
     * buildForm
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
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

    /**
     * getName
     * @return string
     */
    public function getName()
    {
        return 'front_plugin_coupon_shopping';
    }
}