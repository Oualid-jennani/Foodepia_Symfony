<?php

namespace App\Form;

use App\Entity\Variant;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;

class VariantType extends AbstractType
{
    /**
     * @var Security
     */
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if(in_array('ROLE_RESTAURANT', $this->security->getUser()->getRoles()))
        {
            $builder
                ->add('size',ChoiceType::class,[
                    'choices'=>[
                        'Standard'=>'standard',
                        'SM'=>'sm',
                        'M'=>'m',
                        'L'=>'l',
                        'XL'=>'xl'
                    ]
                ])
            ;
        }
        elseif(in_array('ROLE_CHEF', $this->security->getUser()->getRoles()))
        {
            $builder
                ->add('size',ChoiceType::class,[
                    'choices'=>[
                        'Standard'=>'standard',
                        'U'=>'u',
                        'KG'=>'kg'
                    ]
                ])
            ;
        }
        elseif(in_array('ROLE_TRAITEUR', $this->security->getUser()->getRoles()))
        {
            $builder
                ->add('size',ChoiceType::class,[
                    'choices'=>[
                        'Standard'=>'standard',
                        'U'=>'u',
                        'KG'=>'kg'
                    ]
                ])
            ;
        }

        $builder
            ->add('price',NumberType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Variant::class,
        ]);
    }
}
