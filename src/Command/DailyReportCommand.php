<?php

namespace App\Command;

use App\Entity\DailyReport;
use App\Repository\DailyReportRepository;
use App\Repository\DailyReportTypeRepository;
use App\Repository\OvertimeEntryRepository;
use App\Service\ExcelService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:report:daily',
    description: 'Erstellt täglichen Report.',
)]
class DailyReportCommand extends Command
{

    public function __construct(
        private readonly OvertimeEntryRepository                           $overtimeEntryRepository,
        private readonly DailyReportRepository                             $reportRepository,
        private readonly DailyReportTypeRepository                         $reportTypeRepository,
        private readonly EntityManagerInterface                            $entityManager,
        private readonly LoggerInterface                                   $logger,
        private readonly ExcelService                                      $excelService,
        #[Autowire('%kernel.project_dir%')] private readonly string        $projectDir,
        #[Autowire(env: 'DAILY_REPORT_DIR_NAME')] private readonly string  $dailyReportDirName,
        #[Autowire(env: 'DAILY_REPORT_FILE_NAME')] private readonly string $dailyReportTemplateFile,
        #[Autowire(env: 'DAILY_REPORT_FILE_EXT')] private readonly string  $dailyReportTemplateFileExtension,
    )
    {
        parent::__construct();
    }

    public function run(InputInterface $input, OutputInterface $output): int
    {
        try {
            $reportData = $this->overtimeEntryRepository->countCancellationForToday();
            if (!$reportData) {
                throw new Exception("Keine Daten gefunden.");
            }

            // Hilfsvariablen
            $date = $reportData['DATE'];
            $cancelled = $reportData['CANCELLED'];
            $ill = $reportData['ILL'];
            $substituted = $reportData['SUBSTITUTED'];
            $merged = $reportData['MERGED'];
            $count = $reportData['COUNT'];

            // Bereits erstellt? Dann ignorieren
            $reportExists = $this->reportRepository->findOneBy([
                'date' => $date->format('Y-m-d'),
            ]);
            if ($reportExists) {
                throw new Exception("Report für '{$date->format('d.m.Y')}' existiert bereits.");
            }

            // Alle Typen erfassen (werden über DataFixtures erstellt)
            $cancellationType = $this->reportTypeRepository->findOneBy(['label' => 'Ausfall']);
            $illnessType = $this->reportTypeRepository->findOneBy(['label' => 'Krank']);
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

            if (!$illnessType) {
                throw new Exception("Typ 'Krank' existiert nicht.");
            }

            // Ausfall
            $report = new DailyReport();
            $report->setDate($date);
            $report->setType($cancellationType);
            $report->setAmount($cancelled);
            $report->setMax($count);
            $this->entityManager->persist($report);

            // Krankheit
            $report = new DailyReport();
            $report->setDate($date);
            $report->setType($illnessType);
            $report->setAmount($ill);
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

            // Datei nur updaten, wenn heute Freitag ist.
            if ($date->format('N') != "5") {
                $this->logger->notice("Heute ist nicht Freitag!");
                return Command::SUCCESS;
            }

            $this->updateExcelFile($report->getDate()->format('d.m.Y'));
        } catch (Exception $e) {
            $this->logger->notice($e->getMessage());
        }
        return Command::SUCCESS;
    }

    /**
     * @throws Exception
     */
    private function updateExcelFile(string $date): void
    {
        $date = \DateTimeImmutable::createFromFormat('d.m.Y', $date);

        if ($date === false) {
            throw new \InvalidArgumentException('Ungültiges Datumsformat, erwartet: TT.MM.JJJJ');
        }
        $dayOfWeek = (int) $date->format('N');

        if ($dayOfWeek > 1) {
            $mondayDate = $date->modify('monday this week');
        } else {
            $mondayDate = $date;
        }

        $configFile = $this->projectDir. '/config/report.json';
        $config = file_exists($configFile)
            ? json_decode(file_get_contents($configFile), true)
            : [
                'substituted' => 'L',
                'merged' => 'K',
                'cancelled' => 'E',
                'not_ill' => 'I',
                'total' => 'G',
                'row_offset' => 0,
                'page_index' => 3,
            ];
        $dateString = $config['UNTIL'];

            // Template für dieses Halbjahr
        $templatePath = rtrim($this->projectDir, '/') . '/' . trim($this->dailyReportDirName, '/') . '/' . trim($this->dailyReportTemplateFile . "-$dateString" . $this->dailyReportTemplateFileExtension, '/');

        // allgemeines Template, wenn kein vorausgefülltes gefunden wurde
        if (!file_exists($templatePath)) {
            $templatePath = rtrim($this->projectDir, '/') . '/' . trim($this->dailyReportDirName, '/') . '/' . trim($this->dailyReportTemplateFile . $this->dailyReportTemplateFileExtension, '/');
            $monthFile = rtrim($this->projectDir, '/') . '/' . trim($this->dailyReportDirName, '/') . '/' . trim($this->dailyReportTemplateFile . "-$dateString" . $this->dailyReportTemplateFileExtension, '/');
        } else {
            $monthFile = $templatePath;
        }

        // Abbruch, wenn kein Template gefunden wurde
        if (!file_exists($templatePath)) {
            throw new \RuntimeException(sprintf('Excel-Vorlage nicht gefunden unter: %s', $templatePath));
        }

        // Heute muss Freitag sein, ansonsten leeres Array
        $reportData = $this->reportRepository->findAllGroupedByRange();


        if (!empty($reportData)) {
            $cancelCount = $reportData['CANCELLED'];
            $substitutionCount = $reportData['SUBSTITUTED'];
            $mergeCount = $reportData['MERGED'];
            $illnessCount = $reportData['ILL'];
        } else {
            $this->logger->notice("keine Daten für den Zeitraum vorhanden!");
            return;
        }


        $configFile = $this->projectDir. '/config/report.json';
        $config = file_exists($configFile)
            ? json_decode(file_get_contents($configFile), true)
            : [
                'substituted' => 'L',
                'merged' => 'K',
                'cancelled' => 'E',
                'not_ill' => 'I',
                'total' => 'G',
                'row_offset' => 0,
                'page_index' => 3,
            ];

        // Zellen
        $pageIndex = $config['page_index'];
        $cancelled = $config['cancelled'];
        $notIll = $config['not_ill'];
        $substituted =  $config['substituted'];
        $merged = $config['merged'];
        $sum = $config['total'];
        $rowOffset = $config['row_offset'];


        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getSheet($pageIndex - 1);

        // Ziel-Zeile finden
        $foundCell = $this->excelService->findTargetRow($mondayDate->format('d.m.'), $sheet);
        if (!empty($foundCell)) {
            $row = $foundCell[1] + $rowOffset;
            $sheet->getCell($cancelled . $row)->setValue($illnessCount);
            $sheet->getCell($notIll . $row)->setValue($cancelCount);
            $sheet->getCell($substituted . $row)->setValue($substitutionCount);
            $sheet->getCell($merged . $row)->setValue($mergeCount);
        }
        $writer = new Xlsx($spreadsheet);
        $writer->save($monthFile);
    }

}
