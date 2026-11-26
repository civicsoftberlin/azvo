<?php




namespace App\Command;

use App\Entity\DailyReport;
use App\Repository\DailyReportRepository;
use App\Repository\DailyReportTypeRepository;
use App\Repository\OvertimeEntryRepository;
use App\Service\ExcelService;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ERROR | E_PARSE);

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

    protected function configure(): void
    {
        $this
            ->addArgument(
                'period',
                InputArgument::OPTIONAL,
                'Zeitraum im Format JJJJ-MM-TT (z.B. 2026-09-30). Standard ist der aktuelle Tag'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        try {
            $now = new DateTimeImmutable("now");

            $periodInput = $input->getArgument('period');
            if ($periodInput === null) {
                $fromDate = new DateTime("now");
            } else {
                $fromDate = DateTime::createFromFormat("Y-m-d", $periodInput);
                if ($fromDate === false) {
                    $io->error(sprintf(
                        'Ungültiges Format "%s". Erwartet wird JJJJ-MM-TT (z.B. 2026-09-30).',
                        $periodInput
                    ));
                    return Command::FAILURE;
                }
            }
            $count = 0;
            while ($fromDate->setTime(0,0) <= $now->setTime(0,0)) {
                $this->runEach(DateTimeImmutable::createFromInterface($fromDate));
                $fromDate->modify('+1 day');
                // TODO: Cache hier leeren und dann weiter?
                $count++;
                if ($count % 10 === 0) {
                    gc_collect_cycles();
                }
            }

        } catch (Exception $e) {
            $this->logger->notice($e->getMessage());
        }
        $io->success("Tagesberichte wurden erfolgreich erzeugt.");
        return Command::SUCCESS;
    }

    protected function runEach(DateTimeInterface $date): int
    {
        try {
            $reportData = $this->overtimeEntryRepository->countCancellationForToday($date);
            if (!$reportData) {
                throw new Exception("Keine Daten gefunden.");
            }

            // Hilfsvariablen
            $date = $reportData['DATE'];
            $cancelled = $reportData['CANCELLED'];
            $ill = $reportData['ILL'];
            $event = $reportData['EVENT'];
            $training = $reportData['TRAINING'];
            $substituted = $reportData['SUBSTITUTED'];
            $merged = $reportData['MERGED'];
            $additional = $reportData['ADDITIONAL'];
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
            $eventType = $this->reportTypeRepository->findOneBy(['label' => 'Veranstaltung']);
            $trainingType = $this->reportTypeRepository->findOneBy(['label' => 'Fortbildung']);
            $substitutionType = $this->reportTypeRepository->findOneBy(['label' => 'Vertretung']);
            $mergeType = $this->reportTypeRepository->findOneBy(['label' => 'Zusammenlegung']);
            $additionalType = $this->reportTypeRepository->findOneBy(['label' => 'Mehrarbeit']);

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
            if (!$eventType) {
                throw new Exception("Typ 'Fortbildung' existiert nicht.");
            }
            if (!$trainingType) {
                throw new Exception("Typ 'Fortbildung' existiert nicht.");
            }
            if (!$additionalType) {
                throw new Exception("Typ 'Mehrarbeit' existiert nicht.");
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

            // Mehrarbeit
            $report = new DailyReport();
            $report->setDate($date);
            $report->setType($additionalType);
            $report->setAmount($additional);
            $report->setMax($count);
            $this->entityManager->persist($report);

            // Zusammenlegung
            $report = new DailyReport();
            $report->setDate($date);
            $report->setType($mergeType);
            $report->setAmount($merged);
            $report->setMax($count);
            $this->entityManager->persist($report);

            // Veranstaltung
            $report = new DailyReport();
            $report->setDate($date);
            $report->setType($eventType);
            $report->setAmount($event);
            $report->setMax($count);
            $this->entityManager->persist($report);

            // Fortbildung
            $report = new DailyReport();
            $report->setDate($date);
            $report->setType($trainingType);
            $report->setAmount($training);
            $report->setMax($count);
            $this->entityManager->persist($report);

            // Persistieren
            $this->entityManager->flush();

            // Datei nur updaten, wenn heute Freitag ist.
            if ($date->format('N') != "5") {
                return Command::SUCCESS;
            }

            $this->updateExcelFile($date->format('d.m.Y'));
        } catch (Exception $e) {
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
                'additional' => 'M',
                'merged' => 'K',
                'cancelled' => 'E',
                'not_ill' => 'I',
                'total' => 'Z',
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
        $reportData = $this->reportRepository->findAllGroupedByRange($date);

        $cancelCount = 0;
        $substitutionCount = 0;
        $additionalCount = 0;
        $mergeCount = 0;
        $illnessCount = 0;
        $eventCount = 0;
        $trainingCount = 0;

        if (!empty($reportData)) {
            foreach ($reportData as $row) {
                $cancelCount += $row['CANCELLED'] ?? 0;
                $substitutionCount += $row['SUBSTITUTED'] ?? 0;
                $additionalCount += $row['ADDITIONAL'] ?? 0;
                $mergeCount += $row['MERGED'] ?? 0;
                $illnessCount += $row['ILL'] ?? 0;
                $eventCount += $row['EVENT'] ?? 0;
                $trainingCount += $row['TRAINING'] ?? 0;
            }
        } else {
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
        $additional =  $config['additional'];
        $event =  $config['event'];
        $training =  $config['training'];
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
            $sheet->getCell($event . $row)->setValue($eventCount);
            $sheet->getCell($training . $row)->setValue($trainingCount);
            $sheet->getCell($substituted . $row)->setValue($substitutionCount);
            $sheet->getCell($additional . $row)->setValue($additionalCount);
            $sheet->getCell($merged . $row)->setValue($mergeCount);
        }
        $writer = new Xlsx($spreadsheet);
        $writer->save($monthFile);
    }

}
