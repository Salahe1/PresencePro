<?php

namespace App\Repository;

use App\Entity\Retard;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\Types\Types;

/**
 * @extends ServiceEntityRepository<Retard>
 */
class RetardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Retard::class);
    }

    public function findAllByDateDesc(): array
    {
        return $this->createQueryBuilder('r')
            ->orderBy('r.dateJour', 'DESC')
            ->addOrderBy('r.heureArrivee', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByDate(\DateTimeInterface $date): array
    {
        $dateOnly = \DateTimeImmutable::createFromInterface($date)->setTime(0, 0, 0);

        return $this->createQueryBuilder('r')
        ->where('r.dateJour = :date')
        ->setParameter('date', $dateOnly, Types::DATE_IMMUTABLE)
        ->orderBy('r.dateJour', 'DESC')
        ->addOrderBy('r.heureArrivee', 'DESC')
        ->getQuery()
        ->getResult();
    }

    public function findByEmploye (int $employeId) : array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('IDENTITY(r.employe) = :id')
            ->setParameter('id', $employeId)
            ->orderBy('r.dateJour', 'DESC')
            ->addOrderBy('r.heureArrivee', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
//    /**
//     * @return Retard[] Returns an array of Retard objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('r.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Retard
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

