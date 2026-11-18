<?php

namespace App\Controller;

use App\Repository\DailyReportRepository;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class AppController extends AbstractController
{

    public function __construct(
        #[Autowire('%kernel.project_dir%')] private string $projectDir,
        #[Autowire(env: 'DAILY_REPORT_FILE')] private string $dailyReportTemplateFile,
    )
    {
    }

    #[Route('/', name: 'app_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->redirectToRoute('admin_dashboard');
    }

    #[Route('/admin/export/report/{date}', name: 'admin_export_report', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function exportReport(string $date, DailyReportRepository $reportRepository): StreamedResponse
    {
        $date = \DateTimeImmutable::createFromFormat('d.m.Y', $date);
        if ($date === false) {
            throw new \InvalidArgumentException('Ungültiges Datumsformat, erwartet: TT.MM.JJJJ');
        }

        $templatePath = rtrim($this->projectDir, '/') . '/' . ltrim($this->dailyReportTemplateFile, '/');

        if (!file_exists($templatePath)) {
            throw new \RuntimeException(sprintf('Excel-Vorlage nicht gefunden unter: %s', $templatePath));
        }

        $report = $reportRepository->findBy(['date' => $date->format('Y-m-d')]);
        if (!$report) {
            throw new \RuntimeException("Kein Report gefunden.");
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

        // Zellen
        $pageIndex = 1;
        $cancelled = 'F5';
        $substituted = 'B5';
        $merged = 'D5';
        $sum = 'H5';

        $spreadsheet = IOFactory::load($templatePath);

        $sheet = $spreadsheet->getSheet($pageIndex);
        $sheet->setTitle('Bearbeitet');
        $sheet->getCell($cancelled)->setValue($cancelCount);
        $sheet->getCell($substituted)->setValue($substitutionCount);
        $sheet->getCell($merged)->setValue($mergeCount);
        $sheet->getCell($sum)->setValue($maxCount);

        $filename = "exportierte_datei.xlsx";

        $response = new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }
}
