<?php

namespace App\Repository;

use App\Entity\DailyReport;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\SortOrder;
use SortDirection;

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
            ->orderBy('d.id', SortDirection::Descending)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findAllGroupedByDate(): array
    {
        return $this->createQueryBuilder('dr')
            ->select('dr.date as DATE')
            ->addSelect('MAX(dr.max) AS COUNT')
            ->addSelect('SUM(CASE WHEN t.label = \'Krank\' THEN dr.amount ELSE 0 END) AS ILL')
            ->addSelect('SUM(CASE WHEN t.label = \'Ausfall\' THEN dr.amount ELSE 0 END) AS CANCELLED')
            ->addSelect('SUM(CASE WHEN t.label = \'Veranstaltung\' THEN dr.amount ELSE 0 END) AS EVENT')
            ->addSelect('SUM(CASE WHEN t.label = \'Fortbildung\' THEN dr.amount ELSE 0 END) AS TRAINING')
            ->addSelect('SUM(CASE WHEN t.label = \'Vertretung\' THEN dr.amount ELSE 0 END) AS SUBSTITUTED')
            ->addSelect('SUM(CASE WHEN t.label = \'Mehrarbeit\' THEN dr.amount ELSE 0 END) AS ADDITIONAL')
            ->addSelect('SUM(CASE WHEN t.label = \'Zusammenlegung\' THEN dr.amount ELSE 0 END) AS MERGED')
            ->leftJoin('dr.type', 't')
            ->groupBy('dr.date')
            ->having('MAX(dr.max) > 0')
            ->orderBy('dr.date', SortDirection::Descending)
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * @throws \DateMalformedStringException
     */
    public function findAllGroupedByRange(\DateTimeImmutable $dateTime): array
    {
        $friday = clone $dateTime;
        if ($friday->format('N') != 5) {
            return [];
        }
        $monday = $friday->modify('monday this week');

        return $this->createQueryBuilder('dr')
            ->select('dr.date as DATE')
            ->addSelect('MAX(dr.max) AS COUNT')
            ->addSelect('SUM(CASE WHEN t.label = \'Ausfall\' THEN dr.amount ELSE 0 END) AS CANCELLED')
            ->addSelect('SUM(CASE WHEN t.label = \'Krank\' THEN dr.amount ELSE 0 END) AS ILL')
            ->addSelect('SUM(CASE WHEN t.label = \'Vertretung\' THEN dr.amount ELSE 0 END) AS SUBSTITUTED')
            ->addSelect('SUM(CASE WHEN t.label = \'Mehrarbeit\' THEN dr.amount ELSE 0 END) AS ADDITIONAL')
            ->addSelect('SUM(CASE WHEN t.label = \'Zusammenlegung\' THEN dr.amount ELSE 0 END) AS MERGED')
            ->addSelect('SUM(CASE WHEN t.label = \'Veranstaltung\' THEN dr.amount ELSE 0 END) AS EVENT')
            ->addSelect('SUM(CASE WHEN t.label = \'Fortbildung\' THEN dr.amount ELSE 0 END) AS TRAINING')
            ->addSelect('SUM(CASE WHEN t.label = \'Sondereinsatz\' THEN dr.amount ELSE 0 END) AS SPECIAL')
            ->leftJoin('dr.type', 't')
            ->andWhere('dr.date >= :monday')
            ->andWhere('dr.date <= :friday')
            ->setParameter('monday', $monday->format('Y-m-d'))
            ->setParameter('friday', $friday->format('Y-m-d'))
            ->groupBy('dr.date')
            ->orderBy('dr.date', SortDirection::Descending)
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
