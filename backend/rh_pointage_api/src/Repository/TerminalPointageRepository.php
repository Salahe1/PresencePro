<?php

namespace App\Repository;

use App\Entity\TerminalPointage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Ulid;

/**
 * @extends ServiceEntityRepository<TerminalPointage>
 */
class TerminalPointageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TerminalPointage::class);
    }
    
    public function findOneByIdentifiant(Ulid $identifiant): ?TerminalPointage{
    //    return $this->createQueryBuilder('t')
    //                ->andWhere('t.identifiant = :identifiant')
    //                ->setParameter('identifiant', $identifiant)
    //                ->getQuery()
    //                ->getOneOrNullResult();
     return $this->findOneBy(['identifiant' => $identifiant]);
    }
}
//    /**
//     * @return TerminalPointage[] Returns an array of TerminalPointage objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('t.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?TerminalPointage
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }


