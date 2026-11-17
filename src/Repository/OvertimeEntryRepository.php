<?php

namespace App\Repository;

use App\Entity\OvertimeEntry;
use App\Entity\Teacher;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
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

    public function sumDecaysForPeriod(DateTimeInterface $startDate, DateTimeInterface $endDate): array
    {
        $qb = $this->createQueryBuilder('e');

        $results = $qb->select('e.incidentDate as date, SUM(e.amount) as totalAmount')
            ->andWhere('e.incidentDate >= :startDate')
            ->andWhere('e.incidentDate <= :endDate')
            ->andWhere('e.amount < 0')
            ->andWhere('e.comment IS NULL')
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->setParameter('startDate', $startDate->format('Y-m-d'))
            ->setParameter('endDate', $endDate->format('Y-m-d'))
            ->getQuery()
            ->getResult();

        $mappedResults = [];
        foreach ($results as $row) {
            $key = sprintf('%04d-%02d-%02d', $row['date']->format('Y'), $row['date']->format('m'), $row['date']->format('d'));
            $mappedResults[$key] = (float) $row['totalAmount'];
        }

        return $mappedResults;
    }

    /**
     * @throws \DateMalformedStringException
     */
    public function sumRealDecays(): int
    {
        $now = new \DateTimeImmutable('first day of this month')->modify('-1 day');
        return (int) $this->createQueryBuilder('e')
            ->select('SUM(e.amount)')
            ->andWhere('e.incidentDate = :startDate')
            ->andWhere('e.comment = :comment')
            ->setParameter('startDate', $now->format('Y-m-d'))
            ->setParameter('comment', 'DECAY')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
    }

    /**
     * @throws \DateMalformedStringException
     */
    public function sumRealCarryOvers(): int
    {
        $now = new \DateTimeImmutable('first day of this month');
        return (int) $this->createQueryBuilder('e')
            ->select('SUM(e.amount)')
            ->andWhere('e.incidentDate = :startDate')
            ->andWhere('e.comment = :comment')
            ->setParameter('startDate', $now->format('Y-m-d'))
            ->setParameter('comment', 'CARRYOVER')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
    }

    public function sumCarryOversForPeriod(DateTimeInterface $startDate, DateTimeInterface $endDate, bool $includeCarryOver = false): array
    {
        $qb = $this->createQueryBuilder('e');

        $qb->select('e.incidentDate, SUM(e.amount) as totalAmount')
            ->andWhere('e.incidentDate >= :startDate')
            ->andWhere('e.incidentDate <= :endDate')
            ->andWhere('e.amount > 0');

        if ($includeCarryOver) {
             $qb->andWhere('e.comment = :comment');
             $qb->orWhere('e.comment IS NULL');
            $qb->setParameter('comment', "CARRYOVER");
        } else {
            $qb->andWhere('e.comment IS NULL');
        }

        $qb->groupBy('e.incidentDate')
            ->orderBy('e.incidentDate', 'ASC')
            ->setParameter('startDate', $startDate->format('Y-m-d'))
            ->setParameter('endDate', $endDate->format('Y-m-d'));


        $results = $qb->getQuery()->getResult();

        $mappedResults = [];
        foreach ($results as $row) {
            $key = sprintf('%04d-%02d-%02d', $row['incidentDate']->format('Y'), $row['incidentDate']->format('m'), $row['incidentDate']->format('d'));
            $mappedResults[$key] = (float) $row['totalAmount'];
        }

        return $mappedResults;
    }

    public function getWeeklyHourDistribution(): array
    {
        $startDate = new \DateTimeImmutable('first day of last month');
        $endDate = new \DateTimeImmutable('last day of last month');
        $entries = $this->createQueryBuilder('e')
            ->select('e.incidentDate', 'e.courseHour', 'e.amount')
            ->andWhere('e.incidentDate >= :startDate')
            ->andWhere('e.incidentDate <= :endDate')
            ->andWhere('e.amount < 0')
            ->andWhere('e.untisId NOT LIKE :excludeVert')
            ->andWhere('e.untisId NOT LIKE :excludeOrig')
            ->setParameter('excludeVert', '%vert')
            ->setParameter('excludeOrig', '%orig')
            ->setParameter('startDate', $startDate->format('Y-m-d'))
            ->setParameter('endDate', $endDate->format('Y-m-d'))
            ->getQuery()
            ->getResult();

        $aggregated = [];
        foreach ($entries as $entry) {
            $date = $entry['incidentDate'];
            $hour = $entry['courseHour'];

            $dayOfWeek = (int) $date->format('N');

            if ($dayOfWeek >= 1 && $dayOfWeek <= 5) {
                $x = $dayOfWeek - 1;
                $y = (int) $hour;

                if (!isset($aggregated[$x][$y])) {
                    $aggregated[$x][$y] = 0;
                }
                $aggregated[$x][$y]++;
            }
        }

        $heatmapData = [];

        foreach ($aggregated as $x => $hours) {
            foreach ($hours as $y => $count) {
                $heatmapData[] = [$x, $y, $count];
            }
        }

        return $heatmapData;
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

    public function findAllFromToday(): array
    {
        $now = new \DateTimeImmutable();
        return $this->createQueryBuilder('e')
            ->andWhere('e.incidentDate >= :today')
            ->andWhere('e.comment IS NULL')
            ->setParameter('today', $now->format('Y-m-d'))
            ->getQuery()
            ->getResult();
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
