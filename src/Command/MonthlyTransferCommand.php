<?php

namespace App\Command;

use App\Entity\OvertimeEntry;
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

                $io->note(sprintf('Kein Zeitraum angegeben. Verwende automatisch: %s', $period));
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
                    $data['teacherId'], DateTime::createFromImmutable($startDate), "CARRYOVER"
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
                $hasCarryOver = $this->overtimeEntryRepository->hasCarryOverForTeacherAndDate(
                    $data['teacherId'], DateTime::createFromImmutable($endDate), "DECAY"
                );
                if ($hasCarryOver) {
                    continue;
                }
                $hasCarryOver = $this->overtimeEntryRepository->hasCarryOverForTeacherAndDate(
                    $data['teacherId'], DateTime::createFromImmutable($startDate), "CARRYOVER"
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
                $carryOverEntry->setExpiresAt(DateTime::createFromImmutable($endDate->modify("+12 month")));
                $carryOverEntry->setReason("Verfall von Minderarbeit");
                $this->entityManager->persist($carryOverEntry);
            }
        }
        $this->entityManager->flush();
    }
}
