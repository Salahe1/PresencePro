<?php

namespace App\Repository;

use App\Entity\Absence;
use App\Entity\Employe;
use Doctrine\DBAL\Types\Types;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Absence>
 */
class AbsenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Absence::class);
    }

    public function existsForEmployeDateAndOrdrePlage( Employe $employe, \DateTimeImmutable $date, int $ordrePlage ): bool 
    {
     $count = (int) $this->createQueryBuilder('a')
        ->select('COUNT(a.id)')
        ->andWhere('a.employe = :employe')
        ->andWhere('a.date = :date')
        ->andWhere('a.ordrePlage = :ordrePlage')
        ->setParameter('employe', $employe)
        ->setParameter('date', $date, Types::DATE_IMMUTABLE)
        ->setParameter('ordrePlage', $ordrePlage)
        ->getQuery()
        ->getSingleScalarResult();

     return $count > 0;
    }
//    /**
//     * @return Absence[] Returns an array of Absence objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('a.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Absence
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
