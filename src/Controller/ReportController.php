<?php

namespace App\Controller;

use App\Controller\Admin\DashboardController;
use App\Repository\DailyReportRepository;
use App\Service\DirectoryService;
use App\Type\ReportTemplateUploadType;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGeneratorInterface;
use Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/report', name: 'admin_report_')]
#[IsGranted('ROLE_ADMIN')]
class ReportController extends AbstractController
{
    private string $reportDirPath;
    private string $template;

    public function __construct(
        #[Autowire('%kernel.project_dir%')] private string                 $projectDir,
        #[Autowire(env: 'DAILY_REPORT_DIR_NAME')] private string           $dailyReportDir,
        #[Autowire(env: 'DAILY_REPORT_FILE_NAME')] private readonly string $fileName,
        #[Autowire(env: 'DAILY_REPORT_FILE_EXT')] private string           $fileExtension,
    )
    {
        $this->reportDirPath = rtrim($projectDir, '/') . '/' . ltrim($dailyReportDir, '/');
        $this->template = ltrim($this->fileName, "files/") . trim($this->fileExtension);
    }

    #[Route('/', name: 'index')]
    public function index(DirectoryService $directoryService, DailyReportRepository $reportRepository): Response
    {
        $fileList = $directoryService->readDirectoryContents($this->reportDirPath);
        $reportList = $reportRepository->findAllGroupedByDate();

        // Vorlage entfernen
        $templateFile = $fileList[$this->template];
        unset($fileList[$this->template]);

        $form = $this->createForm(ReportTemplateUploadType::class);

        return $this->render('report/index.html.twig', [
            'icon' => "chart-bar",
            'files' => $fileList,
            'template_file' => $templateFile,
            'reports' => $reportList,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/upload', name: 'upload', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function uploadTemplate(Request $request, AdminUrlGeneratorInterface $adminUrlGenerator): Response
    {
        $form = $this->createForm(ReportTemplateUploadType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $file */
            $file = $form->get('templateFile')->getData();

            // Namen aus der .env holen DAILY_REPORT_DIR_NAME
            $fileName = $this->getParameter('app.daily_report_file_name') . $this->getParameter('app.daily_report_file_ext');
            $targetDir = $this->getParameter('kernel.project_dir') . "/" . trim($this->getParameter('app.daily_report_dir_name'), '/') . "/";

            // Datei speichern (überschreibt automatisch die alte)
            $file->move($targetDir, $fileName);
            $this->addFlash('success', 'Die Vorlage wurde erfolgreich aktualisiert.');
        } else {
            $this->addFlash('danger', 'Der Upload ist fehlgeschlagen. Bitte prüfen Sie die Datei.');
        }

        $url = $adminUrlGenerator
            ->setDashboard(DashboardController::class)
            ->setRoute('admin_report_index')
            ->generateUrl();

        return $this->redirect($url);
    }

    /**
     * @throws Exception
     */
    #[Route('/{file}/export', name: 'export', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function exportReport(string $file): BinaryFileResponse
    {
        $safeFilename = basename($file);
        $filePath = $this->reportDirPath . '/' . $safeFilename;
        $realReportDir = realpath($this->reportDirPath);
        $realFilePath = realpath($filePath);

        if ($realFilePath === false || !str_starts_with($realFilePath, $realReportDir)) {
            throw new \RuntimeException(sprintf('Datei nicht gefunden oder Zugriff verweigert: %s', $safeFilename));
        }

        return $this->file($filePath, $safeFilename);
    }
}
