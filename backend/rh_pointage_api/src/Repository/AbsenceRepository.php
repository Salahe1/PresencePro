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

    public function getAllAbsences(): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.employe', 'e')
            ->addSelect('e')
            ->leftJoin('a.justification', 'j')
            ->addSelect('j')
            ->orderBy('a.date', 'DESC')
            ->addOrderBy('a.ordrePlage', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getAllAbsencesEmploye(int $employeId): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.employe', 'e')
            ->addSelect('e')
            ->leftJoin('a.justification', 'j')
            ->addSelect('j')
            ->andWhere('e.id = :employeId')
            ->setParameter('employeId', $employeId)
            ->orderBy('a.date', 'DESC')
            ->addOrderBy('a.ordrePlage', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getAllAbsencesByDate(\DateTimeImmutable $date): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.employe', 'e')
            ->addSelect('e')
            ->leftJoin('a.justification', 'j')
            ->addSelect('j')
            ->andWhere('a.date = :date')
            ->setParameter('date', $date, Types::DATE_IMMUTABLE)
            ->orderBy('a.ordrePlage', 'DESC')
            ->getQuery()
            ->getResult();
    }

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

