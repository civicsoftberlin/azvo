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
        $dateString = $date->format('Ym');

        // Template für diesen Monat
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

        $report = $this->reportRepository->findBy(['date' => $date->format('Y-m-d')]);
        if (!$report) {
            return;
        }

        // Hilfsvariablen
        $cancelCount = 0;
        $substitutionCount = 0;
        $mergeCount = 0;
        $maxCount = $report[0]->getMax();

        foreach ($report as $reportItem) {
            if (null == $reportItem->getType()) {
                throw new \RuntimeException("Typ der Änderung muss angegeben werden.");
            }
            if ($reportItem->getType()->getLabel() == 'Ausfall') {
                $cancelCount = $reportItem->getAmount();
            }
            if ($reportItem->getType()->getLabel() == 'Vertretung') {
                $substitutionCount = $reportItem->getAmount();
            }
            if ($reportItem->getType()->getLabel() == 'Zusammenlegung') {
                $mergeCount = $reportItem->getAmount();
            }
        }

        $configFile = $this->projectDir. '/config/report.json';
        $config = file_exists($configFile)
            ? json_decode(file_get_contents($configFile), true)
            : [
                'substituted' => 'A',
                'merged' => 'C',
                'cancelled' => 'E',
                'total' => 'G',
                'row_offset' => 2,
                'page_index' => 2
            ];

        // Zellen
        $pageIndex = $config['page_index'];
        $cancelled = $config['cancelled'];
        $substituted =  $config['substituted'];
        $merged = $config['merged'];
        $sum = $config['total'];
        $rowOffset = $config['row_offset'];

        // nur dev
        $foundCellOutput = 'H6';

        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getSheet($pageIndex - 1);

        // Ziel-Zeile finden
        $foundCell = $this->excelService->findTargetRow($date->format('Y-m-d'), $sheet);
        if (!empty($foundCell)) {
            $row = $foundCell[1] + $rowOffset;
            $sheet->getCell($foundCellOutput)->setValue(implode("", $foundCell));

            $sheet->getTabColor('Bearbeitet')->setRGB(Color::COLOR_GREEN);
            $sheet->getCell($cancelled . $row)->setValue($cancelCount);
            $sheet->getCell($substituted . $row)->setValue($substitutionCount);
            $sheet->getCell($merged . $row)->setValue($mergeCount);
            $sheet->getCell($sum . $row)->setValue($maxCount);
        } else {
            $sheet->getCell($foundCellOutput)->setValue($date->format('d.m.Y') . " nicht gefunden");
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($monthFile);
    }

}
