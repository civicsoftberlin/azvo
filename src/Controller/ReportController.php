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
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints\LessThan;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\Regex;

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

        $templateFile = [];
        // Vorlage entfernen
        if (array_key_exists($this->template, $fileList)) {
            $templateFile = $fileList[$this->template];
            unset($fileList[$this->template]);
        }

        $configFile = $this->getParameter('kernel.project_dir') . '/config/report.json';
        $config = file_exists($configFile)
            ? json_decode(file_get_contents($configFile), true)
            : [
                'substituted' => 'A',
                'merged' => 'C',
                'cancelled' => 'E',
                'total' => 'G',
                'row_offset' => 2,
            ];

        $configForm = $this->createFormBuilder($config)
            ->add('cancelled', TextType::class, [
                'label' => 'Ausfall',
                'constraints' => [new Regex('/^[A-Z]+$/')],
                'help' => 'Spalte, in der Ausfälle eingetragen werden sollen.',
                'attr' => [
                    'class' => 'form-control-sm rounded-5 '
                ],
            ])
            ->add('substituted', TextType::class, [
                'label' => 'Vertretung',
                'constraints' => [new Regex('/^[A-Z]+$/')],
                'help' => 'Spalte, in der Vertretungen eingetragen werden sollen.',
                'attr' => [
                    'class' => 'form-control-sm rounded-5 '
                ],
            ])
            ->add('merged', TextType::class, [
                'label' => 'Zusammenlegung',
                'constraints' => [new Regex('/^[A-Z]+$/')],
                'help' => 'Spalte, in der Zusammenlegungen eingetragen werden sollen.',
                'attr' => [
                    'class' => 'form-control-sm rounded-5 '
                ],
            ])
            ->add('total', TextType::class, [
                'label' => 'Gesamt',
                'constraints' => [new Regex('/^[A-Z]+$/')],
                'help' => 'Spalte, in der die Summe eingetragen werden soll.',
                'attr' => [
                    'class' => 'form-control-sm rounded-5'
                ],
            ])
            ->add('page_index', IntegerType::class, [
                'label' => 'Blatt-Index',
                'help' => 'Das erste Excel-Blatt hat den Index 1.',
                'constraints' => [new Positive(), new LessThan(10)],
                'attr' => [
                    'class' => 'form-control-sm rounded-5 '
                ],
            ])
            ->add('row_offset', IntegerType::class, [
                'label' => 'Zeilen-Offset',
                'constraints' => [new Positive(), new LessThan(10)],
                'help' => 'Nach wie viel Zeilen nach dem Datum folgen die Eingaben?',
                'attr' => [
                    'class' => 'form-control-sm rounded-5 '
                ],
            ])
            ->getForm();

        $form = $this->createForm(ReportTemplateUploadType::class);

        return $this->render('report/index.html.twig', [
            'icon' => "chart-bar",
            'files' => $fileList,
            'template_file' => $templateFile,
            'reports' => $reportList,
            'form' => $form->createView(),
            'config_form' => $configForm,
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

    #[Route('/update', name: 'settings_update', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function updateSettings(Request $request, AdminUrlGeneratorInterface $adminUrlGenerator): Response
    {
        $projectDir = $this->getParameter('kernel.project_dir');
        $filePath = $projectDir . '/config/report.json';

        // 1. Bestehende Daten als Fallback laden
        $defaultData = [
            'substituted' => 'A',
            'merged'      => 'C',
            'cancelled'   => 'E',
            'total'       => 'G',
            'row_offset'  => 2,
        ];

        if (file_exists($filePath)) {
            $existingData = json_decode(file_get_contents($filePath), true);
            if (is_array($existingData)) {
                $defaultData = array_merge($defaultData, $existingData);
            }
        }

        foreach (['substituted', 'merged', 'cancelled', 'total'] as $key) {
            if (isset($defaultData[$key])) {
                $defaultData[$key] = strtoupper($defaultData[$key]);
            }
        }

        // 2. Formular zur sicheren Validierung erstellen
        $form = $this->createFormBuilder($defaultData)
            ->add('substituted', TextType::class, [
                'label' => 'Spalte Vertretung',
                'constraints' => [new Regex('/^[a-z]+$/i')]
            ])
            ->add('merged', TextType::class, [
                'label' => 'Spalte Zusammenlegung',
                'constraints' => [new Regex('/^[a-z]+$/i')]
            ])
            ->add('cancelled', TextType::class, [
                'label' => 'Spalte Ausfall',
                'constraints' => [new Regex('/^[a-z]+$/i')]
            ])
            ->add('total', TextType::class, [
                'label' => 'Spalte Gesamt',
                'constraints' => [new Regex('/^[a-z]+$/i')]
            ])
            ->add('page_index', IntegerType::class, [
                'label' => 'Blatt-Index',
                'constraints' => [new Positive(), new LessThan(10)],
                'attr' => [
                    'class' => 'form-control-sm rounded-5 '
                ],
            ])
            ->add('row_offset', IntegerType::class, [
                'label' => 'Zeilen-Offset',
                'constraints' => [new Positive(), new LessThan(10)]
            ])
            ->getForm();

        $form->handleRequest($request);

        // 3. Validieren und Speichern
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            // ✅ HIER: Die Nutzereingabe jetzt endgültig in Großbuchstaben umwandeln
            foreach (['substituted', 'merged', 'cancelled', 'total'] as $key) {
                $data[$key] = strtoupper($data[$key]);
            }

            // JSON schreiben (PRETTY_PRINT macht es für manuelle Checks lesbar)
            $jsonContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            if (file_put_contents($filePath, $jsonContent) !== false) {
                $this->addFlash('success', 'Die Report-Konfiguration wurde erfolgreich gespeichert.');
            } else {
                $this->addFlash('danger', 'Fehler beim Speichern: Keine Schreibrechte für /config/report.json');
            }
        } else {
            // Falls Validierung fehlschlägt (z.B. "123" statt "A" eingegeben)
            $this->addFlash('danger', 'Ungültige Eingaben. Bitte prüfe die Spaltenbuchstaben (nur A-Z) und den Offset sowie Spalten-Index (größer/gleich 0).');
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
