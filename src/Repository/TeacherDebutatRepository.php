<?php

namespace App\Repository;

use App\Entity\TeacherDebutat;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TeacherDebutat>
 */
class TeacherDebutatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TeacherDebutat::class);
    }

    /**
     * Holt alle eindeutigen Startdaten (validFrom) absteigend sortiert.
     *
     * @return string[]
     */
    public function findDistinctValidFromDates(): array
    {
        $result = $this->createQueryBuilder('td')
            ->select('DISTINCT td.validFrom')
            ->orderBy('td.validFrom', 'DESC')
            ->getQuery()
            ->getScalarResult();

        // array_column flacht das Array ab, sodass wir direkt ein Array aus DateTime-Objekten haben
        return array_column($result, 'validFrom');
    }

//    public function findOneBySomeField($value): ?TeacherDebutat
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
