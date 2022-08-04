<?php

namespace App\Form;

use App\Entity\Menu;
use App\Entity\MenuCategory;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MenuType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name',TextType::class)
            ->add('description',TextType::class)
            ->add('menuCategory', EntityType::class, [
                'class' => MenuCategory::class,
                'required' => true,
                'choice_label' => 'cuisineName',
                'placeholder' => 'Chose Menu Category'
            ])
            ->add('images', FileType::class, [
                'attr' =>
                    [
                        'label' => 'Brochure (Image file)',
                        'accept'=>'image/*'
                    ],

                'required' => false,
                'multiple'=>true
            ])

        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Menu::class,
        ]);
    }
}
