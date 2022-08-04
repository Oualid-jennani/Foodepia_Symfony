<?php

namespace App\Form;

use App\Entity\City;
use App\Entity\Country;
use App\Entity\Province;
use App\Repository\ProvinceRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EditCityType extends AbstractType
{
    /**
     * @var ProvinceRepository
     */
    private $provinceRepository;

    /**
     * EditCityType constructor.
     * @param ProvinceRepository $provinceRepository
     */
    public function __construct(ProvinceRepository $provinceRepository)
    {
        $this->provinceRepository = $provinceRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var City $city */
        $city = $builder->getData();
        $builder
        ->add('name',TextType::class)
        ->add('country', EntityType::class, [
            'class' => Country::class,
            'required' => true,
            'choice_label' => 'name',
            'placeholder' => 'Choose Country'
        ])
        ->add('province', EntityType::class, [
            'class' => Province::class,
            'placeholder' => 'Choose the Province',
            'choice_label' => 'name',
            'choices'=> $this->provinceRepository->findByCountry($city->getCountry())
        ])
        ->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $arg) {
            $data = $arg->getData();
            /** @var Province $province */
            $province = $this->provinceRepository->find($data['province']);
            $form = $arg->getForm();
            $form->getData()->setProvince($province);
        })
    ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => City::class,
            'validation_groups' => false,
        ]);
    }
}
