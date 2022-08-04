<?php


namespace App\Service;


use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Partner
{
    /**
     * @var EntityManagerInterface
     */
    private $manager;

    /**
     * Partner constructor.
     * @param EntityManagerInterface $manager
     */
    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    public function activePartner(User $partner)
    {
        if (in_array("ROLE_CHEF",$partner->getRoles()) || in_array("ROLE_TRAITEUR",$partner->getRoles()) ){
            $partner->setStatus('active');
            $this->manager->flush();
        }else{
            throw new NotFoundHttpException();
        }
    }

    public function banPartner(User $partner)
    {
        if (in_array("ROLE_CHEF",$partner->getRoles()) || in_array("ROLE_TRAITEUR",$partner->getRoles()) ){
            $partner->setStatus('block');
            $this->manager->flush();
        }else{
            throw new NotFoundHttpException();
        }
    }
}