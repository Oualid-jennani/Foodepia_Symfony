<?php

namespace App\Form;

use App\Entity\Package;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PackageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('price',NumberType::class)
            ->add('name',TextType::class)
            ->add('memberShipLimit',NumberType::class)
            ->add('packUsage',ChoiceType::class,[
                'choices'=>[
                    'Unlimitted'=>'unlimitted',
                    'Limitted'=>'limitted'
                ]
            ])
            ->add('type',TextType::class)
            ->add('limitMerchantBySell',NumberType::class)
            ->add('description',TextareaType::class,[
                'required'=>false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Package::class,
        ]);
    }
}
