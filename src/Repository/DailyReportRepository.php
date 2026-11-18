<?php

namespace App\Repository;

use App\Entity\DailyReport;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DailyReport>
 */
class DailyReportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DailyReport::class);
    }

    /**
     * @return DailyReport[] Returns an array of DailyReport objects
     */
    public function findAllDistinct(): array
    {
        return $this->createQueryBuilder('d')
            ->select('DISTINCT d.date')
            ->orderBy('d.id', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

//    public function findOneBySomeField($value): ?DailyReport
//    {
//        return $this->createQueryBuilder('d')
//            ->andWhere('d.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
