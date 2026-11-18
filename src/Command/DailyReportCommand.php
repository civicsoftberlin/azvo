<?php

namespace App\Command;

use App\Entity\DailyReport;
use App\Repository\DailyReportRepository;
use App\Repository\DailyReportTypeRepository;
use App\Repository\OvertimeEntryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:report:daily',
    description: 'Erstellt täglichen Report.',
)]
class DailyReportCommand extends Command
{

    public function __construct(
        private OvertimeEntryRepository $overtimeEntryRepository,
        private DailyReportRepository $reporRepository,
        private DailyReportTypeRepository $reportTypeRepository,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    )
    {
        parent::__construct();
    }

    public function run(InputInterface $input, OutputInterface $output): int
    {
        try {
            $reportData = $this->overtimeEntryRepository->countCancellationForYesterday();
            if (!$reportData) {
                throw new Exception("Keine Daten gefunden.");
            }

            // Hilfsvariablen
            $date = $reportData['DATE'];
            $cancelled = $reportData['CANCELLED'];
            $substituted = $reportData['SUBSTITUTED'];
            $merged = $reportData['MERGED'];
            $count = $reportData['COUNT'];

            // Bereits erstellt? Dann ignorieren
            $reportExists = $this->reporRepository->findOneBy([
                'date' => $date->format('Y-m-d'),
            ]);
            if ($reportExists) {
                throw new Exception("Report für '{$date->format('d.m.Y')}' existiert bereits.");
            }

            // Alle Typen erfassen (werden über DataFixtures erstellt)
            $cancellationType = $this->reportTypeRepository->findOneBy(['label' => 'Ausfall']);
            $substitutionType = $this->reportTypeRepository->findOneBy(['label' => 'Vertretung']);
            $mergeType = $this->reportTypeRepository->findOneBy(['label' => 'Zusammenlegung']);

            if (!$cancellationType) {
                throw new Exception("Typ 'Ausfall' existiert nicht.");
            }
            if (!$substitutionType) {
                throw new Exception("Typ 'Vertretung' existiert nicht.");
            }
            if (!$mergeType) {
                throw new Exception("Typ 'Zusammenlegung' existiert nicht.");
            }

            // Ausfall
            $report = new DailyReport();
            $report->setDate($date);
            $report->setType($cancellationType);
            $report->setAmount($cancelled);
            $report->setMax($count);
            $this->entityManager->persist($report);

            // Vertretung
            $report = new DailyReport();
            $report->setDate($date);
            $report->setType($substitutionType);
            $report->setAmount($substituted);
            $report->setMax($count);
            $this->entityManager->persist($report);

            // Zusammenlegung
            $report = new DailyReport();
            $report->setDate($date);
            $report->setType($mergeType);
            $report->setAmount($merged);
            $report->setMax($count);
            $this->entityManager->persist($report);

            // Persistieren
            $this->entityManager->flush();
        } catch (Exception $e) {
            $this->logger->notice($e->getMessage());
        }
        return Command::SUCCESS;
    }

}
