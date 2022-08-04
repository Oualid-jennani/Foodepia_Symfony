<?php

namespace App\Repository;

use App\Entity\Variant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Variant|null find($id, $lockMode = null, $lockVersion = null)
 * @method Variant|null findOneBy(array $criteria, array $orderBy = null)
 * @method Variant[]    findAll()
 * @method Variant[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VariantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Variant::class);
    }

    // /**
    //  * @return Variant[] Returns an array of Variant objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('v.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Variant
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    /**
     * @param $size
     * @param $menu
     * @param $subMenu
     * @param $id
     * @return Variant[] Returns an array of Variant objects
     */

    public function findByVariantSize($size,$menu,$subMenu,$id)
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.id <> :id')
            ->andWhere('v.size = :size')
            ->andWhere('v.menu = :menu or v.subMenu = :subMenu')
            ->setParameter('id', $id)
            ->setParameter('size', $size)
            ->setParameter('menu', $menu)
            ->setParameter('subMenu', $subMenu)
            ->orderBy('v.id', 'ASC')
            ->getQuery()
            ->getResult()
            ;
    }

}
