<?php

namespace App\Repository;


use App\Entity\Employe;
use App\Entity\Pointage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Pointage>
 */
class PointageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Pointage::class);
    }


    public function hasPointageBetween( Employe $employe, \DateTimeImmutable $debut, \DateTimeImmutable $fin ): bool 
    {
     $count = (int) $this->createQueryBuilder('p')
        ->select('COUNT(p.id)')
        ->andWhere('p.employe = :employe')
        ->andWhere('p.timeStamp >= :debut')
        ->andWhere('p.timeStamp <= :fin')
        ->setParameter('employe', $employe)
        ->setParameter('debut', $debut, Types::DATETIME_IMMUTABLE)
        ->setParameter('fin', $fin, Types::DATETIME_IMMUTABLE)
        ->getQuery()
        ->getSingleScalarResult();

      return $count > 0;
    }

    public function findTodayPointagesByEmploye(Employe $employe, \DateTimeImmutable $date): ArrayCollection
    {
        $startOfDay = $date->setTime(0, 0, 0);
        $startOfNextDay = $startOfDay->modify('+1 day');

        $result = $this->createQueryBuilder('p')
            ->andWhere('p.employe = :employe')
            ->andWhere('p.timeStamp >= :startOfDay')
            ->andWhere('p.timeStamp < :startOfNextDay')
            ->setParameter('employe', $employe)
            ->setParameter('startOfDay', $startOfDay, Types::DATETIME_IMMUTABLE)
            ->setParameter('startOfNextDay', $startOfNextDay, Types::DATETIME_IMMUTABLE)
            ->orderBy('p.timeStamp', 'ASC')
            ->getQuery()
            ->getResult();

        return new ArrayCollection($result);
    }

    public function findByEmployeId(int $employeId): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('IDENTITY(p.employe) = :employeId')
            ->setParameter('employeId', $employeId)
            ->orderBy('p.timeStamp', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findAllOrderByTimeStampDesc(): array
    {
     return $this->createQueryBuilder('p')
        ->orderBy('p.timeStamp', 'DESC')
        ->getQuery()
        ->getResult();
    }

    public function findByDate(\DateTimeInterface $date): array
    {
     $startOfDay = \DateTimeImmutable::createFromInterface($date)->setTime(0, 0, 0);
     $nextDay = $startOfDay->modify('+1 day');

     return $this->createQueryBuilder('p')
        ->andWhere('p.timeStamp >= :start')
        ->andWhere('p.timeStamp < :nextDay')
        ->setParameter('start', $startOfDay)
        ->setParameter('nextDay', $nextDay)
        ->orderBy('p.timeStamp', 'ASC')
        ->getQuery()
        ->getResult();
    }
}
    // public function findByDate(\DateTimeInterface $date) : array
    // {
    //     $startOfDay = (clone $date)->setTime(0, 0, 0);
    //     $endOfDay = (clone $date)->setTime(23, 59, 59);

    //     return $this->createQueryBuilder('p')
    //                 ->andWhere('p.timeStamp BETWEEN :start AND :end')
    //                 ->setParameter('start', $startOfDay)
    //                 ->setParameter('end', $endOfDay)
    //                 ->orderBy('p.timeStamp', 'ASC')
    //                 ->getQuery()
    //                 ->getResult();
    // }

//    /**
//     * @return Pointage[] Returns an array of Pointage objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Pointage
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

