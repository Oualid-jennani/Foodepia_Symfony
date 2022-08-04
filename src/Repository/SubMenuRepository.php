<?php

namespace App\Repository;

use App\Entity\SubMenu;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SubMenu|null find($id, $lockMode = null, $lockVersion = null)
 * @method SubMenu|null findOneBy(array $criteria, array $orderBy = null)
 * @method SubMenu[]    findAll()
 * @method SubMenu[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SubMenuRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SubMenu::class);
    }

    // /**
    //  * @return SubMenu[] Returns an array of SubMenu objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?SubMenu
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

}
