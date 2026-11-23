<?php

namespace App\Service;

use Exception;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExcelService
{

    /**
     * @param string $needle string to search for in dedicated worksheet
     * @param Worksheet $sheet worksheet to be searched in
     * @return array [columnLetter,rowNumber] or [] if needle not found
     * @throws Exception
     */
    public function findTargetRow(string $needle, Worksheet $sheet): array
    {
        $foundRow = null;
        $foundColLetter = null;

        foreach ($sheet->getRowIterator() as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);

            foreach ($cellIterator as $cell) {
                if (Date::isDateTime($cell)) {
                    $cellDate = Date::excelToDateTimeObject($cell->getValue());

                    if ($cellDate->format('d.m.') === $needle) {
                        $foundRow = $cell->getRow();
                        $foundColLetter = $cell->getColumn();
                        break 2;
                    }
                }
                elseif (str_contains($cell->getCalculatedValue(), $needle)) {
                    $foundRow = $cell->getRow();
                    $foundColLetter = $cell->getColumn();
                    break 2;
                }
            }
        }
        return ($foundRow && $foundColLetter) ? [$foundColLetter, $foundRow] : [];
    }

}
