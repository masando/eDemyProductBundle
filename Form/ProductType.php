<?php

namespace eDemy\ProductBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            //->add('name', null, ['label' => 'edit_product.name'])
            ->add('name')
            ->add('model')
            ->add('description', 'ckeditor')
            ->add('price')
            ->add('category','entity', array(
                'class' => 'eDemyProductBundle:ProductCategory',
                'property' => 'name',
                'empty_value' => '',
                'required' => false,
            ))
            ->add('published')
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'eDemy\ProductBundle\Entity\Product'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'edemy_productbundle_product';
    }
}
