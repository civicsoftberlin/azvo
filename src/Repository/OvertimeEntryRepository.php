<?php

namespace App\Repository;

use App\Entity\OvertimeEntry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OvertimeEntry>
 */
class OvertimeEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OvertimeEntry::class);
    }

    public function sumAmountByPeriod(\DateTime $startDate, \DateTime $endDate): int
    {
        $result = $this->createQueryBuilder('e')
            ->select('SUM(e.amount) as totalAmount')
            ->andWhere('e.incidentDate > :startDate')
            ->andWhere('e.incidentDate < :endDate')
            ->setParameter('startDate', $startDate->format('Y-m-d'))
            ->setParameter('endDate', $endDate->format('Y-m-d'))
            ->getQuery()
            ->getSingleScalarResult();

        return $result ?? 0;
    }
    public function sumAmountByTeacherAndPeriod(\DateTime $startDate, \DateTime $endDate): array
    {
        return $this->createQueryBuilder('e')
            ->select('t.id as teacherId, t.shortName as teacherName, SUM(e.amount) as totalAmount')
            ->join('e.teacher', 't')
            ->andWhere('e.incidentDate >= :startDate')
            ->andWhere('e.incidentDate <= :endDate')
            ->setParameter('startDate', $startDate->format('Y-m-d'))
            ->setParameter('endDate', $endDate->format('Y-m-d'))
            ->groupBy('t.id, t.shortName')
            ->orderBy('t.shortName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function hasCarryOverForTeacherAndDate(
        int $teacherId,
        \DateTime $incidentDate,
        string $untisId
    ): bool {
        return $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->andWhere('e.teacher = :teacherId')
            ->andWhere('e.incidentDate = :incidentDate')
            ->andWhere('e.comment = :reason')
            ->setParameter('teacherId', $teacherId)
            ->setParameter('incidentDate', $incidentDate->format('Y-m-d'))
            ->setParameter('reason', $untisId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getCarryOverForTeacherAndDate(
        int $teacherId,
        \DateTime $incidentDate,
    ): int {
        return $this->createQueryBuilder('e')
            ->select('e.amount as totalAmount')
            ->andWhere('e.teacher = :teacherId')
            ->andWhere('e.incidentDate = :incidentDate')
            ->andWhere('e.comment = :reason')
            ->setParameter('teacherId', $teacherId)
            ->setParameter('incidentDate', $incidentDate->format('Y-m-d'))
            ->setParameter('reason', "CARRYOVER")
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function hasEndOfMonthHook(
        int $teacherId,
        \DateTime $incidentDate
    ): bool {
        $count = $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->andWhere('e.teacher = :teacherId')
            ->andWhere('e.incidentDate = :incidentDate')
            ->andWhere('e.hook = :reason')
            ->setParameter('teacherId', $teacherId)
            ->setParameter('incidentDate', $incidentDate->format('Y-m-d'))
            ->setParameter('reason', 'EOM')
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }


//    /**
//     * @return OvertimeEntry[] Returns an array of OvertimeEntry objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('o.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?OvertimeEntry
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
