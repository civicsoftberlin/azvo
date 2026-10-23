<?php

namespace App\Repository;

use App\Entity\Teacher;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Teacher>
 */
class TeacherRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Teacher::class);
    }

    /**
     * Holt alle eindeutigen Lehrer, die einen TeacherDebutat-Eintrag
     * für das gegebene validFrom-Datum haben.
     */
    public function findDistinctByTeacherDebutatValidFrom(\DateTime $validFrom): array
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.teacherDebutats', 'td')
            ->where('td.validFrom = :validFrom')
            ->setParameter('validFrom', $validFrom->format('Y-m-d'))
            ->distinct()
            ->orderBy('t.shortName', 'ASC') // Optional: direkt sortieren
            ->getQuery()
            ->getResult();
    }

//    /**
//     * @return Teacher[] Returns an array of Teacher objects
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

//    public function findOneBySomeField($value): ?Teacher
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
