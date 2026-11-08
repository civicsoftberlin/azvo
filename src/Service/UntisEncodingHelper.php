<?php

namespace App\Service;

class UntisEncodingHelper
{
    /**
     * Konvertiert einen String sicher nach UTF-8.
     * - null/leer → leerer String
     * - bereits UTF-8 → unverändert
     * - Windows-1252 → konvertiert
     * - entfernt UTF-8 BOM
     */
    public static function convert(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        $trimmed = trim($value);

        // UTF-8 BOM entfernen
        if (str_starts_with($trimmed, "\xEF\xBB\xBF")) {
            $trimmed = substr($trimmed, 3);
        }

        // Bereits valides UTF-8?
        if (mb_check_encoding($trimmed, 'UTF-8')) {
            return $trimmed;
        }

        // Sonst von Windows-1252 konvertieren
        return mb_convert_encoding($trimmed, 'UTF-8', 'Windows-1252');
    }
}
