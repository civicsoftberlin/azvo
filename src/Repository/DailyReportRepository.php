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

    public function findAllDistinct(): array
    {
        return $this->createQueryBuilder('d')
            ->select('DISTINCT d.date')
            ->orderBy('d.id', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findAllGroupedByDate(): array
    {
        return $this->createQueryBuilder('dr')
            ->select('dr.date as DATE')
            ->addSelect('MAX(dr.max) AS COUNT')
            ->addSelect('SUM(CASE WHEN t.label = \'Ausfall\' THEN dr.amount ELSE 0 END) AS CANCELLED')
            ->addSelect('SUM(CASE WHEN t.label = \'Vertretung\' THEN dr.amount ELSE 0 END) AS SUBSTITUTED')
            ->addSelect('SUM(CASE WHEN t.label = \'Zusammenlegung\' THEN dr.amount ELSE 0 END) AS MERGED')
            ->leftJoin('dr.type', 't')
            ->groupBy('dr.date')
            ->orderBy('dr.date', 'DESC')
            ->getQuery()
            ->getArrayResult(); // WICHTIG: Kein Entity-Overhead, nur rohe Arrays!
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
