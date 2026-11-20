<?php

namespace App\Service;

use Symfony\Component\Finder\SplFileInfo;

class DirectoryService
{

    /**
     * Liest alle Dateien mit bestimmter Endung in einem Verzeichnis.
     *
     * @param string   $directory   Der Pfad zum Verzeichnis
     * @param string[] $extensions  Erlaubte Endungen, z.B. ['xlsx', 'csv']
     * @return array<string, string> Dateiname => Dateiinhalt
     */
    public function readDirectoryContents(
        string $directory,
        array  $extensions = ['xlsx']
    ): array {
        if (!is_dir($directory)) {
            throw new \InvalidArgumentException("Verzeichnis nicht gefunden: {$directory}");
        }

        // Endungen normalisieren: lowercase, Punkt entfernen
        $allowed = array_map(
            static fn(string $ext) => strtolower(ltrim($ext, '.')),
            $extensions
        );

        $baseIterator = new \FilesystemIterator(
            $directory,
            \FilesystemIterator::SKIP_DOTS
        );

        // Filter: nur Dateien mit passender Endung
        $filtered = new \CallbackFilterIterator($baseIterator, function (\SplFileInfo $file) use ($allowed) {
            if (!$file->isFile()) {
                return false;
            }
            // Excel-Lock-Files (~$...) aussortieren
            if (str_starts_with($file->getFilename(), '~$')) {
                return false;
            }
            return in_array(strtolower($file->getExtension()), $allowed, true);
        });

        $contents = [];
        foreach ($filtered as $file) {
            /** @var SplFileInfo $file */
            $contents[$file->getFilename()]['name'] = $file->getFilename();
            $contents[$file->getFilename()]['mtime'] = $file->getMTime();
            $contents[$file->getFilename()]['ctime'] = $file->getCTime();
        }

        return $contents;
    }

}
