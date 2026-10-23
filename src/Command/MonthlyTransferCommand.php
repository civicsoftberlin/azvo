<?php

namespace App\Command;

use App\Entity\OvertimeEntry;
use App\Entity\Teacher;
use App\Repository\OvertimeEntryRepository;
use App\Repository\TeacherRepository;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:monthly-transfer:create',
    description: 'Erstellt monatliche Überträge für alle Lehrkräfte.',
)]
class MonthlyTransferCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private TeacherRepository $teacherRepository;
    private OvertimeEntryRepository $overtimeEntryRepository;

    public function __construct(EntityManagerInterface $entityManager, TeacherRepository $teacherRepository, OvertimeEntryRepository $overtimeEntryRepository)
    {
        $this->entityManager = $entityManager;
        $this->teacherRepository = $teacherRepository;
        $this->overtimeEntryRepository = $overtimeEntryRepository;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'period',
                InputArgument::OPTIONAL,
                'Zeitraum im Format YYYY-MM (z.B. 2026-09). Standard ist der vorherige Monat.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        try {
            $periodInput = $input->getArgument('period');
            if ($periodInput === null) {
                $now = new \DateTimeImmutable();
                $period = $now
                    ->modify('first day of this month')
                    ->modify('-1 month')
                    ->format('Y-m');

                //$io->note(sprintf('Kein Zeitraum angegeben. Verwende automatisch: %s', $period));
            } else {
                $date = DateTimeImmutable::createFromFormat('Y-m', $periodInput);
                if (!$date || $date->format('Y-m') !== $periodInput) {
                    $io->error(sprintf(
                        'Ungültiges Format "%s". Erwartet wird YYYY-MM (z.B. 2026-09).',
                        $periodInput
                    ));
                    return Command::FAILURE;
                }
                $period = $periodInput;
            }

            $startDate = DateTimeImmutable::createFromFormat('Y-m-d', $period . '-01');
            $endDate = $startDate->modify('last day of this month')->setTime(23, 59, 59);

            $this->calculateCarryOver($io, $startDate, $endDate);
            $this->calculateDecay($io, $startDate, $endDate);
            return Command::SUCCESS;
        } catch (Exception $e) {
            $io->error('Ein Fehler ist aufgetreten: ' . $e->getMessage());
            $this->entityManager->rollback();
            return Command::FAILURE;
        }
    }

    /**
     * @throws \DateMalformedStringException
     */
    private function calculateCarryOver(SymfonyStyle $io, DateTimeImmutable $startDate, DateTimeImmutable $endDate): void
    {
        $teacherSums = $this->overtimeEntryRepository->sumAmountByTeacherAndPeriod(DateTime::createFromImmutable($startDate), DateTime::createFromImmutable($endDate));

        if (count($teacherSums) === 0) {
            $io->warning('Keine Einträge im angegebenen Zeitraum gefunden.');
            return;
        }

        // [teacherId, teacherName, totalAmount]
        foreach ($teacherSums as $data) {
            $totalAmount = $data['totalAmount'];
            $teacher = $this->teacherRepository->find($data['teacherId']);

            if ($totalAmount == 0) {
                continue;
            }
            if ($totalAmount > 0) {
                $hasCarryOver = $this->overtimeEntryRepository->hasCarryOverForTeacherAndDate(
                    $data['teacherId'], DateTime::createFromImmutable($endDate->modify("+1 day")), "CARRYOVER"
                );
                if ($hasCarryOver) {
                    continue;
                }
                $carryOverEntry = new OvertimeEntry();
                $carryOverEntry->setTeacher($teacher);
                $carryOverEntry->setTeacherShortname($teacher->getShortName());
                $carryOverEntry->setAmount($totalAmount);
                $carryOverEntry->setIncidentDate(DateTime::createFromImmutable($endDate->modify("+1 day")));
                $carryOverEntry->setCourseHour(0);
                $carryOverEntry->setComment("CARRYOVER");
                $carryOverEntry->setExpiresAt(DateTime::createFromImmutable($startDate)->modify("+13 month")->modify('last day of this month'));
                $carryOverEntry->setReason("Übertrag aus Vormonat");
                $this->entityManager->persist($carryOverEntry);
            }
        }
        $this->entityManager->flush();
    }

    /**
     * @throws \DateMalformedStringException
     */
    private function calculateDecay(SymfonyStyle $io, DateTimeImmutable $startDate, DateTimeImmutable $endDate): void
    {
        $teacherSums = $this->overtimeEntryRepository->sumAmountByTeacherAndPeriod(DateTime::createFromImmutable($startDate), DateTime::createFromImmutable($endDate));

        if (count($teacherSums) === 0) {
            $io->warning('Keine Einträge im angegebenen Zeitraum gefunden.');
            return;
        }

        // [teacherId, teacherName, totalAmount]
        foreach ($teacherSums as $data) {
            $totalAmount = $data['totalAmount'];
            $teacher = $this->teacherRepository->find($data['teacherId']);
            if ($totalAmount == 0) {
                continue;
            }
            if ($totalAmount < 0) {
                $decayAmount = (int) abs($totalAmount);
                $hasCarryOver = $this->overtimeEntryRepository->hasCarryOverForTeacherAndDate(
                    $data['teacherId'], DateTime::createFromImmutable($endDate), "DECAY"
                );
                if ($hasCarryOver) {
                    continue;
                }

                $carryOverEntry = new OvertimeEntry();
                $carryOverEntry->setTeacher($teacher);
                $carryOverEntry->setTeacherShortname($teacher->getShortName());
                $carryOverEntry->setAmount(-($totalAmount));
                $carryOverEntry->setIncidentDate(DateTime::createFromImmutable($endDate));
                $carryOverEntry->setCourseHour(0);
                $carryOverEntry->setComment("DECAY");
                $carryOverEntry->setExpiresAt(null);
                $carryOverEntry->setReason("Verfall von Minderarbeit");
                $this->entityManager->persist($carryOverEntry);
                $this->entityManager->flush();

                $this->invalidateOldEntries($teacher, $decayAmount, $carryOverEntry);
            }

        }

    }

    /**
     * @throws \DateMalformedStringException
     */
    private function invalidateOldEntries(Teacher $teacher, int $amountToInvalidate, OvertimeEntry $decayEntry): void
    {
        if ($amountToInvalidate <= 0) return;

        $decayDateBegin = clone $decayEntry->getIncidentDate();
        $decayDateBegin->modify('-12 months')->modify('first day of this month');

        // Hole genau die Anzahl an ältesten, noch gültigen +1 Einträgen
        $entriesToKill = $this->entityManager->createQueryBuilder()
            ->select('e')
            ->from(OvertimeEntry::class, 'e')
            ->where('e.teacher = :teacher')
            ->andWhere('e.amount > 0')
            ->andWhere('e.invalidatedBy IS NULL')
            ->andWhere('e.comment IS NULL OR e.comment NOT IN (:systemComments)')
            ->andWhere('e.incidentDate >= :decayBegin')
            ->andWhere('e.incidentDate <= :decayDate')
            ->orderBy('e.incidentDate', 'ASC')
            ->setParameter('teacher', $teacher)
            ->setParameter('systemComments', ['CARRYOVER', 'DECAY'])
            ->setParameter('decayBegin', $decayDateBegin->format('Y-m-d'))
            ->setParameter('decayDate', $decayEntry->getIncidentDate()->format('Y-m-d'))
            ->getQuery()
            ->getResult();

        foreach ($entriesToKill as $entry) {
            $entry->setInvalidatedBy($decayEntry);
            $entry->setInvalidatedAt($decayEntry->getIncidentDate());
            $this->entityManager->persist($entry);
        }
        $this->entityManager->flush();
    }
}
