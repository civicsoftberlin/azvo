<?php

namespace App\Service;

use App\Entity\TeacherDebutat;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExcelExportService
{
    public function createSheetContent(Worksheet $sheet, array $columns, array $rows, TeacherDebutat $teacherDebutat, int $sum): bool
    {
        $hasOvertime = false;
        $alignmentMap = [
            'left'   => Alignment::HORIZONTAL_LEFT,
            'center' => Alignment::HORIZONTAL_CENTER,
            'right'  => Alignment::HORIZONTAL_RIGHT,
        ];

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
            if ($index === 5 && $hasRows) {
                // Beschriftung in der ersten Spalte
                $sheet->setCellValue($cellAddress, 'Zwischensumme:');
            }

            // B) Styling-Array aufbauen
            $styleArray = [
                'borders' => [
                    'top' => [
                        'borderStyle' => Border::BORDER_MEDIUM, // Dicke obere Trennlinie
                        'color' => ['argb' => 'FF000000'],
                    ],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_RIGHT
                ]
            ];

            // C) Hintergrund / Muster für die Summenzeile
            if ($hasRows && empty($colConfig['sum']) && $index !== 0) {
                // Diagonales Kreuzmuster (Cross-Hatch) für leere Spalten
                $styleArray['fill'] = [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFE0E0E0'],
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

        // ==========================================
        // B) DIE NEUE "SOLL (DEPUTAT)"-ZEILE
        // ==========================================
        if ($teacherDebutat !== null && $hasRows) {
            $deputatRow = $sumRow + 1; // Direkt unter der GESAMT-Zeile

            foreach ($columns as $index => $colConfig) {
                $colIndex = $index + 1;
                $columnLetter = Coordinate::stringFromColumnIndex($colIndex);
                $cellAddress = $columnLetter . $deputatRow;

                $value = '';
                $styleArray = [
                    'alignment' => ['horizontal' => $alignmentMap[$colConfig['align'] ?? 'left']],
                    'color' => ['argb' => 'FF000000'],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFE0E0E0'],
                    ],
                ];

                if ($index === 5) {
                    $value = 'Pflichtstunden:';
                    $styleArray = [
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                        'color' => ['argb' => 'FF000000'],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['argb' => 'FFE0E0E0'],
                        ],
                    ];
                }

                if ($index === 6) {
                    // HIER KANNST DU DEINE PRO-RATA-LOGIK EINBAUEN!
                    // Beispiel: Basis-Stunden minus Ermäßigung
                    $sollStunden = $teacherDebutat->getThreshold();
                    $value = $sollStunden;
                    $styleArray = [
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]
                    ];
                }

                $sheet->setCellValue($cellAddress, $value);
                $sheet->getStyle($cellAddress)->applyFromArray($styleArray);
            }

            // weitere Zeile
            $deputatRow = $sumRow + 2;

            foreach ($columns as $index => $colConfig) {
                $colIndex = $index + 1;
                $columnLetter = Coordinate::stringFromColumnIndex($colIndex);
                $cellAddress = $columnLetter . $deputatRow;

                $value = '';
                $styleArray = [
                    'alignment' => ['horizontal' => $alignmentMap[$colConfig['align'] ?? 'left']],
                    'color' => ['argb' => 'FF000000'],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFE0E0E0'],
                    ],
                ];

                if ($index === 0) {
                    $value = 'Deputat:';
                    $styleArray = [
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['argb' => 'FFE0E0E0'],
                        ],
                    ];
                }
                // 2. Ziel-Spalte: Hier z.B. die "Differenz"-Spalte (Index 6 in deinem vorherigen Array)
                // ODER eine extra Spalte, die du dafür vorgesehen hast.
                if ($index === 1) {
                    // HIER KANNST DU DEINE PRO-RATA-LOGIK EINBAUEN!
                    // Beispiel: Basis-Stunden minus Ermäßigung
                    $sollStunden = $teacherDebutat->getDebutat() - $teacherDebutat->getDiscount();
                    $value = $sollStunden;
                    $styleArray = [
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]
                    ];
                }

                if ($index === 5) {
                    $value = 'Summe:';
                    $styleArray = [
                        'font' => ['bold' => true],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                        'color' => ['argb' => 'FF000000'],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['argb' => 'FFE0E0E0'],
                        ],
                    ];
                }

                if ($index === 6) {
                    // HIER KANNST DU DEINE PRO-RATA-LOGIK EINBAUEN!
                    // Beispiel: Basis-Stunden minus Ermäßigung
                    $letter = Coordinate::stringFromColumnIndex(7);
                    $formula = sprintf('=IF(%s%d>=%s%d, %s%d-%s%d, 0)',
                        $letter, $sumRow,
                        $letter, $sumRow + 1,
                        $letter, $sumRow,
                        $letter, $sumRow + 1
                    );
                    $value = $formula;

                    $styleArray = [
                        'font' => ['bold' => true],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]
                    ];
                    $result = $sum - $teacherDebutat->getThreshold();
                    if ($result > 0) {
                        $styleArray = [
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['argb' => 'ffcdd2'],
                            ],
                        ];
                        $hasOvertime = true;
                    }
                }
                $sheet->setCellValue($cellAddress, $value);
                $sheet->getStyle($cellAddress)->applyFromArray($styleArray);
            }

            // Optional: Eine dünne Trennlinie unter der gesamten Deputat-Zeile ziehen
            $sheet->getStyle("A{$deputatRow}:{$columnLetter}{$deputatRow}")->applyFromArray([
                'borders' => [
                    'bottom' => ['borderStyle' => Border::BORDER_DOUBLE, 'color' => ['argb' => 'FF2C3E50']],
                    'top' => [
                        'borderStyle' => Border::BORDER_THIN, // Dicke obere Trennlinie
                        'color' => ['argb' => 'FF2C3E50'],
                    ],
                ]
            ]);
        }

        // 4. Nützliche Excel-Features aktivieren
        // Kopfzeile einfrieren (Zeile 1 bleibt beim Scrollen fixiert)
        $sheet->freezePane('A2');

        // Auto-Filter NUR über die Datenzeilen (nicht über die GESAMT-Zeile)
        if ($hasRows) {
            $lastColLetter = Coordinate::stringFromColumnIndex(count($columns));
            $sheet->setAutoFilter("A1:{$lastColLetter}{$lastDataRow}");
        }
        return $hasOvertime;
    }
    /**
     * Erstellt einen XLSX-Download-Stream mit Summenzeile und Formatierung.
     *
     * @param array  $columns  Konfigurations-Array für die Spalten. Struktur: ['header' => 'Name', 'align' => 'left|center|right', 'sum' => true/false]
     * @param array  $rows     2D-Array mit den Daten
     * @param string $filename Dateiname ohne Endung
     * @return StreamedResponse
     */
    public function createXlsxResponse(array $columns, array $rows, string $filename, int $sum, int $threshold, int $soll): StreamedResponse
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
            if ($index === 5 && $hasRows) {
                // Beschriftung in der ersten Spalte
                $sheet->setCellValue($cellAddress, 'Zwischensumme:');
            }

            // B) Styling-Array aufbauen
            $styleArray = [
                'borders' => [
                    'top' => [
                        'borderStyle' => Border::BORDER_MEDIUM, // Dicke obere Trennlinie
                        'color' => ['argb' => 'FF000000'],
                    ],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_RIGHT
                ]
            ];

            // C) Hintergrund / Muster für die Summenzeile
            if ($hasRows && empty($colConfig['sum']) && $index !== 0) {
                // Diagonales Kreuzmuster (Cross-Hatch) für leere Spalten
                $styleArray['fill'] = [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFE0E0E0'],
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

        // vorletze

        $deputatRow = $sumRow + 1; // Direkt unter der GESAMT-Zeile

        foreach ($columns as $index => $colConfig) {
            $colIndex = $index + 1;
            $columnLetter = Coordinate::stringFromColumnIndex($colIndex);
            $cellAddress = $columnLetter . $deputatRow;

            $value = '';
            $styleArray = [
                'alignment' => ['horizontal' => $alignmentMap[$colConfig['align'] ?? 'left']],
                'color' => ['argb' => 'FF000000'],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFE0E0E0'],
                ],
            ];

            if ($index === 5) {
                $value = 'Pflichtstunden:';
                $styleArray = [
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                    'color' => ['argb' => 'FF000000'],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFE0E0E0'],
                    ],
                ];
            }

            if ($index === 6) {
                // HIER KANNST DU DEINE PRO-RATA-LOGIK EINBAUEN!
                // Beispiel: Basis-Stunden minus Ermäßigung
                $sollStunden = $threshold;
                $value = $sollStunden;
                $styleArray = [
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]
                ];
            }

            $sheet->setCellValue($cellAddress, $value);
            $sheet->getStyle($cellAddress)->applyFromArray($styleArray);
        }

        // letzte Zeile

        $deputatRow = $sumRow + 2;

        foreach ($columns as $index => $colConfig) {
            $colIndex = $index + 1;
            $columnLetter = Coordinate::stringFromColumnIndex($colIndex);
            $cellAddress = $columnLetter . $deputatRow;

            $value = '';
            $styleArray = [
                'alignment' => ['horizontal' => $alignmentMap[$colConfig['align'] ?? 'left']],
                'color' => ['argb' => 'FF000000'],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFE0E0E0'],
                ],
            ];

            if ($index === 0) {
                $value = 'Deputat:';
                $styleArray = [
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFE0E0E0'],
                    ],
                ];
            }
            // 2. Ziel-Spalte: Hier z.B. die "Differenz"-Spalte (Index 6 in deinem vorherigen Array)
            // ODER eine extra Spalte, die du dafür vorgesehen hast.
            if ($index === 1) {
                // HIER KANNST DU DEINE PRO-RATA-LOGIK EINBAUEN!
                // Beispiel: Basis-Stunden minus Ermäßigung
                $sollStunden = $soll;
                $value = $sollStunden;
                $styleArray = [
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]
                ];
            }

            if ($index === 5) {
                $value = 'Summe:';
                $styleArray = [
                    'font' => ['bold' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                    'color' => ['argb' => 'FF000000'],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFE0E0E0'],
                    ],
                ];
            }

            if ($index === 6) {
                // HIER KANNST DU DEINE PRO-RATA-LOGIK EINBAUEN!
                // Beispiel: Basis-Stunden minus Ermäßigung
                $letter = Coordinate::stringFromColumnIndex(7);
                $formula = sprintf('=IF(%s%d>=%s%d, %s%d-%s%d, 0)',
                    $letter, $sumRow,
                    $letter, $sumRow + 1,
                    $letter, $sumRow,
                    $letter, $sumRow + 1
                );
                $value = $formula;

                $styleArray = [
                    'font' => ['bold' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]
                ];
                $result = $sum - $threshold;
                if ($result > 0) {
                    $styleArray = [
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['argb' => 'ffcdd2'],
                        ],
                    ];
                    $hasOvertime = true;
                }
            }
            $sheet->setCellValue($cellAddress, $value);
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
