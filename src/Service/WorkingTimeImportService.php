<?php

namespace App\Service;

use App\Entity\Teacher;
use App\Entity\OvertimeEntry;
use App\Enum\GpuSubstitutionFlag;
use App\Enum\GpuSubstitutionType;
use DateMalformedStringException;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class WorkingTimeImportService
{
    private array $errors = [];
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    /**
     * @throws DateMalformedStringException
     */
    public function importFromCsv(File $file, UserInterface $currentUser, bool $autoCreate): array
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
            $message = trim($data[16]);
            $bit = trim($data[17]);
            $type = trim($data[19]);
            $gpuBit = GpuSubstitutionFlag::getActiveFlags($bit);
            $gpuBit = implode(', ', $gpuBit);
            $gpuType = GpuSubstitutionType::tryFromChar($type)?->value;
            $gpuCount = trim($data[21]);

            // 2. Logik bestimmen
            $isSubstitution = (trim($data[19]) === 'S' || (trim($data[19]) === '' and $updatedTeacher !== '') || trim($data[19]) === '~') ||
                trim($data[19]) === 'A' && ($plannedTeacher !== '' and $updatedTeacher !== '' and $plannedTeacher !== $updatedTeacher and $updatedSubject !== ''); // Substitution
            $isCancellation = trim($data[19]) === 'C' || (trim($data[19]) === '' and $updatedTeacher === '');; // Cancellation
            $isPropagation  = trim($data[19]) === 'P'; // Propagation

            // $isRoomChangeOnly = ($plannedTeacher === $updatedTeacher) && ($plannedRoom !== $updatedRoom);

            // 3. Einträge erzeugen
            if ($isSubstitution || $isPropagation) {
                // A) Ursprünglicher Lehrer verliert die Stunde (-1)
                $this->createEntry(
                    $untisId . '_orig',
                    $plannedTeacher,
                    $dateString,
                    $courseHour,
                    -1,
                    "Ausfall (vertr. d. {$updatedTeacher})",
                    $plannedSubject, $classes, $plannedRoom, $currentUser, $autoCreate, $message, $gpuBit, $gpuType, $gpuCount
                );
                $amount = 1;
                if ($gpuCount != '' and !str_contains($gpuCount, '+') or $gpuType == GpuSubstitutionType::Supervision->value) {
                    $amount = 0;
                }

                // B) Vertretungslehrer bekommt die Stunde (+1)
                $this->createEntry(
                    $untisId . '_vert',
                    $updatedTeacher,
                    $dateString,
                    $courseHour,
                    $amount,
                    "Vertretung für {$plannedTeacher}",
                    $updatedSubject ?: $plannedSubject, $classes, $updatedRoom ?: $plannedRoom, $currentUser, $autoCreate, $message, $gpuBit, $gpuType, $gpuCount
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
                    $plannedSubject, $classes, $plannedRoom, $currentUser, $autoCreate, $message, $gpuBit, $gpuType, $gpuCount
                );
                $importCount++;
            }
            // Raumänderungen ($isRoomChangeOnly) werden für das Arbeitszeitkonto ignoriert.
        }

        fclose($handle);
        $this->em->flush();
        $this->errors['success'] = $importCount;
        return $this->errors;
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
        ?UserInterface $currentUser,
        bool $autoCreate,
        ?string $message,
        ?string $gpuBit,
        ?string $gpuType,
        ?string $gpuCount
    ): void {
        // 1. Lehrer finden
        $teacher = $this->em->getRepository(Teacher::class)->findOneBy(['shortName' => $teacherKuerzel]);
        if (!$teacher) {
            $this->errors['missing'][$teacherKuerzel] = $teacherKuerzel;
            if (!$autoCreate) {
                return;
            }
            $teacher = new Teacher();
            $teacher->setShortName($teacherKuerzel);
            $this->errors['created'][$teacherKuerzel] = $teacherKuerzel;
            $this->em->persist($teacher);
            $this->em->flush();
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
        $entry->setUntisBit($gpuBit);
        $entry->setUntisType($gpuType);
        $entry->setUntiCount($gpuCount);
        $entry->setTeacher($teacher);
        $entry->setTeacherShortname($teacherKuerzel);
        $entry->setIncidentDate(\DateTime::createFromFormat('Ymd', $dateString));
        $entry->setCourseHour($courseHour);
        $entry->setAmount($amount);
        $entry->setReason($reason);
        $entry->setSubject($subject);
        $entry->setClass($className);
        $entry->setRoom($room);
        $entry->setUntisComment($message);
        $entry->setCreatedBy($currentUser); // Falls du den aktuellen User injizierst

        // 4. Verfallsdatum nur bei Mehrarbeit (+1) berechnen
        if ($amount != 0) {
            $date = \DateTime::createFromFormat('Ymd', $dateString);
            $startOfNextMonth = $date->modify('first day of next month');
            $expiryDate = $startOfNextMonth->modify('+12 months')->modify('-1 day');
            if ($amount > 0) {
                $entry->setExpiresAt($expiryDate);
            } else {
                $entry->setExpiresAt(null);
            }

        }
        $this->em->persist($entry);
    }
}
