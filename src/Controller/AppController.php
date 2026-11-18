<?php

namespace App\Controller;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

final class AppController extends AbstractController
{
    #[Route('/', name: 'app_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->redirectToRoute('admin_dashboard');
    }

    #[Route('/admin/export/report/{date}', name: 'admin_export_report', methods: ['GET'])]
    public function exportReport(string $date): StreamedResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Export');

        $filename = "testdatei.xlsx";

        $response = new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename);
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }
}
