<?php

namespace App\Form;

use App\Entity\Menu;
use App\Entity\SubMenu;
use App\Repository\MenuRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;

class SubMenuType extends AbstractType
{
    /**
     * @var Security
     */
    private $security;

    /**
     * @var EntityManagerInterface
     */
    private $manager;

    /**
     * @var MenuRepository
     */
    private $menuRepository;


    public function __construct(EntityManagerInterface $manager, MenuRepository $menuRepository,Security $security)
    {
        $this->manager = $manager;
        $this->menuRepository = $menuRepository;
        $this->security = $security;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name',TextType::class)
            ->add('description',TextType::class)
            ->add('menu', EntityType::class, [
                'class' => Menu::class,
                'choices' => $this->menuRepository->findByNullVariantUser($this->security->getUser()),
                'required' => true,
                'choice_label' => 'name',
                'placeholder' => 'Chose Menu'
            ])
            ->add('brochure', FileType::class, [
                'label' => 'Brochure (Image file)',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => SubMenu::class,
        ]);
    }
}
