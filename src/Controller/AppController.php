<?php

namespace App\Controller;

use App\Repository\DailyReportRepository;
use App\Service\ExcelService;
use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
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
        #[Autowire(env: 'DAILY_REPORT_FILE_NAME')] private string $dailyReportTemplateFile,
        #[Autowire(env: 'DAILY_REPORT_FILE_EXT')] private string $dailyReportTemplateFileExtension,
    )
    {
    }

    #[Route('/', name: 'app_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->redirectToRoute('admin_dashboard');
    }

}
