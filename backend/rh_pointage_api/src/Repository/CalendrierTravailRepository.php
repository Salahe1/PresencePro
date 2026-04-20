<?php

namespace App\Repository;

use App\Entity\CalendrierTravail;
use App\Entity\Departement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

class CalendrierTravailRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CalendrierTravail::class);
    }

        public function findDepartmentRuleForDate(\DateTimeImmutable $date, Departement $departement): ?CalendrierTravail
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.dateJour = :date')
            ->andWhere('c.departement = :departement')
            ->setParameter('date', $date, Types::DATE_IMMUTABLE)
            ->setParameter('departement', $departement)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findGlobalRuleForDate(\DateTimeImmutable $date): ?CalendrierTravail
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.dateJour = :date')
            ->andWhere('c.departement IS NULL')
            ->setParameter('date', $date, Types::DATE_IMMUTABLE)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function existsForDateAndDepartment( \DateTimeImmutable $date, ?Departement $departement, ?int $excludeId = null ): bool
    {
        $qb = $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.dateJour = :date')
            ->setParameter('date', $date, Types::DATE_IMMUTABLE);

        if ($departement === null) {
            $qb->andWhere('c.departement IS NULL');
        } else {
            $qb->andWhere('c.departement = :departement')
               ->setParameter('departement', $departement);
        }

        if ($excludeId !== null) {
            $qb->andWhere('c.id != :excludeId')
               ->setParameter('excludeId', $excludeId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }
}