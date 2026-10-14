<?php

namespace App\Service;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExcelExportService
{
    /**
     * Erstellt einen XLSX-Download-Stream mit Summenzeile und Formatierung.
     *
     * @param array  $columns  Konfigurations-Array für die Spalten. Struktur: ['header' => 'Name', 'align' => 'left|center|right', 'sum' => true/false]
     * @param array  $rows     2D-Array mit den Daten
     * @param string $filename Dateiname ohne Endung
     * @return StreamedResponse
     */
    public function createXlsxResponse(array $columns, array $rows, string $filename): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Export');

        // Mapping für die Alignment-Strings zu PhpSpreadsheet Konstanten
        $alignmentMap = [
            'left'   => Alignment::HORIZONTAL_LEFT,
            'center' => Alignment::HORIZONTAL_CENTER,
            'right'  => Alignment::HORIZONTAL_RIGHT,
        ];

        // 1. Header schreiben und formatieren
        foreach ($columns as $index => $colConfig) {
            $colIndex = $index + 1;
            $columnLetter = Coordinate::stringFromColumnIndex($colIndex);

            $sheet->setCellValue($columnLetter . '1', $colConfig['header']);

            $align = $alignmentMap[$colConfig['align'] ?? 'left'] ?? Alignment::HORIZONTAL_LEFT;

            // Styling: Fett, spezifisches Alignment und heller grauer Hintergrund
            $sheet->getStyle($columnLetter . '1')->applyFromArray([
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => $align],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFE0E0E0'],
                ],
            ]);

            // Spaltenbreite automatisch anpassen
            $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
        }

        // 2. Daten schreiben
        $rowIndex = 2;
        foreach ($rows as $row) {
            foreach ($columns as $index => $colConfig) {
                $columnLetter = Coordinate::stringFromColumnIndex($index + 1);
                $value = $row[$index] ?? '';

                $sheet->setCellValue($columnLetter . $rowIndex, $value);

                $align = $alignmentMap[$colConfig['align'] ?? 'left'] ?? Alignment::HORIZONTAL_LEFT;
                $sheet->getStyle($columnLetter . $rowIndex)->getAlignment()->setHorizontal($align);
            }
            $rowIndex++;
        }

        // 3. Summenzeile mit Formeln, "GESAMT" und Kreuzmuster hinzufügen
        $hasRows = count($rows) > 0;
        $firstDataRow = 2;
        $lastDataRow = $hasRows ? (count($rows) + 1) : 1;
        $sumRow = $lastDataRow + 1; // Die Zeile unter den Daten

        foreach ($columns as $index => $colConfig) {
            $colIndex = $index + 1;
            $columnLetter = Coordinate::stringFromColumnIndex($colIndex);
            $cellAddress = $columnLetter . $sumRow;

            // A) Wert für die Zelle bestimmen
            if (!empty($colConfig['sum']) && $hasRows) {
                // Automatische SUMME-Formel (z.B. =SUM(D2:D10))
                $formula = sprintf('=SUM(%s%d:%s%d)', $columnLetter, $firstDataRow, $columnLetter, $lastDataRow);
                $sheet->setCellValue($cellAddress, $formula);
            }
            elseif ($index === 0 && $hasRows) {
                // Beschriftung in der ersten Spalte
                $sheet->setCellValue($cellAddress, 'GESAMT');
            }

            // B) Styling-Array aufbauen
            $styleArray = [
                'font' => ['bold' => true],
                'borders' => [
                    'top' => [
                        'borderStyle' => Border::BORDER_MEDIUM, // Dicke obere Trennlinie
                        'color' => ['argb' => 'FF000000'],
                    ],
                ],
                'alignment' => [
                    'horizontal' => $alignmentMap[$colConfig['align'] ?? 'left']
                ]
            ];

            // C) Hintergrund / Muster für die Summenzeile
            if ($hasRows && empty($colConfig['sum']) && $index !== 0) {
                // Diagonales Kreuzmuster (Cross-Hatch) für leere Spalten
                $styleArray['fill'] = [
                    'fillType' => Fill::FILL_PATTERN_DARKVERTICAL, // Echtes Kreuzmuster (Gitter)
                    'startColor' => ['argb' => 'FFE0E0E0'],    // Farbe der Linien (Mittelgrau)
                    'endColor' => ['argb' => 'FFE0E0E0'],      // Hintergrund (Weiß)
                ];
            }
            elseif ($index === 0 && $hasRows) {
                // Solider grauer Hintergrund für das Wort "GESAMT"
                $styleArray['fill'] = [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFE0E0E0'],
                ];
            }

            // Styling auf die Zelle anwenden
            $sheet->getStyle($cellAddress)->applyFromArray($styleArray);
        }

        // 4. Nützliche Excel-Features aktivieren
        // Kopfzeile einfrieren (Zeile 1 bleibt beim Scrollen fixiert)
        $sheet->freezePane('A2');

        // Auto-Filter NUR über die Datenzeilen (nicht über die GESAMT-Zeile)
        if ($hasRows) {
            $lastColLetter = Coordinate::stringFromColumnIndex(count($columns));
            $sheet->setAutoFilter("A1:{$lastColLetter}{$lastDataRow}");
        }

        // 5. StreamedResponse für den direkten Download erstellen
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
