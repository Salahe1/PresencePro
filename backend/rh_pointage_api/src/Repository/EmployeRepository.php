<?php

namespace App\Repository;

use App\Entity\Employe;
use App\Entity\Departement;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;


/**
 * @extends ServiceEntityRepository<Employe>
 */
class EmployeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Employe::class);
    }
    public function findActifsByDepartementAndDate( Departement $departement, \DateTimeImmutable $date): array 
    {
           return $this->createQueryBuilder('e')
               ->andWhere('e.departement = :departement')
               ->andWhere('e.actif = :actif')
               ->andWhere('e.dateEmbauche <= :date')
               ->setParameter('departement', $departement)
               ->setParameter('actif', true)
               ->setParameter('date', $date, Types::DATE_IMMUTABLE)
               ->orderBy('e.nom', 'ASC')
               ->addOrderBy('e.prenom', 'ASC')
               ->getQuery()
               ->getResult();
}

//    /**
//     * @return Employe[] Returns an array of Employe objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('e.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Employe
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
