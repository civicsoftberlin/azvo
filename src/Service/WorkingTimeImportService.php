<?php

namespace App\Service;

use App\Entity\Teacher;
use App\Entity\OvertimeEntry;
use DateMalformedStringException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Security\Core\User\UserInterface;

class WorkingTimeImportService
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    /**
     * @throws DateMalformedStringException
     */
    public function importFromCsv(File $file, UserInterface $currentUser): int
    {
        $handle  = fopen($file->getPathname(), "r");
        if ($handle === false) {
            throw new \RuntimeException('CSV-Datei konnte nicht geöffnet werden.');
        }

        $importCount = 0;

        while (($data = fgetcsv($handle, 10000, ',')) !== false) {
            // 1. Felder exakt nach deinem Beispiel mappen
            $untisId = trim($data[0]);
            $dateString = trim($data[1]); // Format: "20260914"
            $courseHour = (int)trim($data[2]);

            $plannedTeacher = trim($data[5]);
            $updatedTeacher = trim($data[6]);

            $plannedSubject = trim($data[7]);
            $updatedSubject = trim($data[9]);

            $plannedRoom = str_replace('~', ', ', trim($data[11]));
            $updatedRoom = str_replace('~', ', ', trim($data[12]));

            $classes = str_replace('~', ', ', trim($data[14]));
            $message = trim($data[16]); // Ggf. hier noch dein iconv für ANSI einbauen

            // 2. Logik bestimmen
            $isSubstitution = !empty($updatedTeacher) && ($updatedTeacher !== $plannedTeacher);
            $isCancellation = stripos($message, 'ausfall') !== false || stripos($message, 'entfall') !== false;
            $isRoomChangeOnly = ($plannedTeacher === $updatedTeacher) && ($plannedRoom !== $updatedRoom);

            // 3. Einträge erzeugen
            if ($isSubstitution) {
                // A) Ursprünglicher Lehrer verliert die Stunde (-1)
                $this->createEntry(
                    $untisId . '_orig',
                    $plannedTeacher,
                    $dateString,
                    $courseHour,
                    -1,
                    "Ausfall (vertreten durch {$updatedTeacher})",
                    $plannedSubject, $classes, $plannedRoom, $currentUser
                );

                // B) Vertretungslehrer bekommt die Stunde (+1)
                $this->createEntry(
                    $untisId . '_vert',
                    $updatedTeacher,
                    $dateString,
                    $courseHour,
                    1,
                    "Vertretung für {$plannedTeacher}",
                    $updatedSubject ?: $plannedSubject, $classes, $updatedRoom ?: $plannedRoom, $currentUser
                );
                $importCount += 2;
            }
            elseif ($isCancellation) {
                // C) Reiner Ausfall ohne Vertretung (-1)
                $this->createEntry(
                    $untisId,
                    $plannedTeacher,
                    $dateString,
                    $courseHour,
                    -1,
                    "Ausfall",
                    $plannedSubject, $classes, $plannedRoom, $currentUser
                );
                $importCount++;
            }
            // Raumänderungen ($isRoomChangeOnly) werden für das Arbeitszeitkonto ignoriert.
        }

        fclose($handle);
        $this->em->flush();

        return $importCount;
    }

    /**
     * Helper-Methode zum sicheren Erstellen eines Eintrags
     * @throws DateMalformedStringException
     */
    private function createEntry(
        string $uniqueUntisRef,
        string $teacherKuerzel,
        string $dateString,
        int $courseHour,
        int $amount,
        string $reason,
        ?string $subject,
        ?string $className,
        ?string $room,
        ?UserInterface $currentUser
    ): void {
        // 1. Lehrer finden
        $teacher = $this->em->getRepository(Teacher::class)->findOneBy(['shortName' => $teacherKuerzel]);
        if (!$teacher) {
            // Optional: Fehler loggen, aber Import nicht abbrechen
            return;
        }

        // 2. Doppelbuchung verhindern (Idempotenz)
        $existing = $this->em->getRepository(OvertimeEntry::class)->findOneBy([
            'untisId' => $uniqueUntisRef,
            'teacher' => $teacher
        ]);

        if ($existing) {
            return; // Bereits vorhanden, überspringen
        }

        // 3. Neuen Eintrag bauen
        $entry = new OvertimeEntry();
        $entry->setUntisId($uniqueUntisRef);
        $entry->setTeacher($teacher);
        $entry->setTeacherShortname($teacherKuerzel);
        $entry->setIncidentDate(\DateTime::createFromFormat('Ymd', $dateString));
        $entry->setCourseHour($courseHour);
        $entry->setAmount($amount);
        $entry->setReason($reason);
        $entry->setSubject($subject);
        $entry->setClass($className);
        $entry->setRoom($room);
        $entry->setCreatedBy($currentUser); // Falls du den aktuellen User injizierst

        // 4. Verfallsdatum nur bei Mehrarbeit (+1) berechnen
        if ($amount != 0) {
            $date = \DateTime::createFromFormat('Ymd', $dateString);
            $startOfNextMonth = $date->modify('first day of next month');
            $expiryDate = $startOfNextMonth->modify('+12 months')->modify('-1 day');
            if ($amount > 0) {
                $entry->setExpiresAt($expiryDate);
            } else {
                $entry->setExpiresAt($expiryDate);
            }

        }
        $this->em->persist($entry);
    }
}
