<?php

namespace App\Repository;

use App\Entity\OvertimeEntry;
use App\Entity\Teacher;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use SortDirection;

/**
 * @extends ServiceEntityRepository<OvertimeEntry>
 */
class OvertimeEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OvertimeEntry::class);
    }

    public function sumAmountByPeriod(\DateTime $startDate, \DateTime $endDate, Teacher $teacher): int
    {
        $result = $this->createQueryBuilder('e')
            ->select('SUM(e.amount) as totalAmount')
            ->andWhere('e.incidentDate >= :startDate')
            ->andWhere('e.incidentDate < :endDate')
            ->andWhere('e.teacher = :teacher')
            ->andWhere('e.comment != :comment OR e.comment IS NULL')
            ->setParameter('startDate', $startDate->format('Y-m-d'))
            ->setParameter('endDate', $endDate->format('Y-m-d'))
            ->setParameter('teacher', $teacher)
            ->setParameter('comment', "CARRYOVER")
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
            ->orderBy('t.shortName', SortDirection::Descending)
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

    /**
     * @param Teacher $teacher
     * @param string $fromDate
     * @param string $toDate
     * @return OvertimeEntry[]
     */
    public function getEntriesWithinPeriod(
        Teacher $teacher,
        string $fromDate,
        string $toDate
    ): array {
        return $this->createQueryBuilder('e')
            ->andWhere('e.teacher = :teacherId')
            ->andWhere('e.incidentDate >= :fromDate')
            ->andWhere('e.incidentDate < :toDate')
            ->setParameter('teacherId', $teacher->getId())
            ->setParameter('fromDate', $fromDate)
            ->setParameter('toDate', $toDate)
            ->orderBy('e.incidentDate', 'ASC')
            ->getQuery()
            ->getResult();
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

    public function findOneByUntisId(?string $untisId): ?OvertimeEntry
    {
        if ($untisId == null) {
            return null;
        }
        $idPart = explode('_', $untisId)[0];
        return $this->createQueryBuilder('o')
            ->andWhere('o.untisId != :val')
            ->andWhere('o.untisId LIKE :valLike')
            ->setParameter('val', $untisId)
            ->setParameter('valLike', "$idPart%")
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
