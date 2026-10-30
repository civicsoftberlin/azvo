<?php

namespace App\Service;

use App\Entity\BackupBag;
use App\Entity\BackupItem;
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
    public function importFromCsv(File $file, UserInterface $currentUser, bool $autoCreate, bool $makeBackup): array
    {
        $handle  = fopen($file->getPathname(), "r");
        if ($handle === false) {
            throw new \RuntimeException('CSV-Datei konnte nicht geöffnet werden.');
        }

        $importCount = 0;

        if ($makeBackup) {
            $backupBag = new BackupBag();
            $this->em->persist($backupBag);
        }


        $batchSize = 500; // Alle 500 Zeilen speichern und Speicher freigeben
        $count = 0;

        while (($data = fgetcsv($handle, 10000, ',')) !== false) {
            // 1. Felder exakt nach deinem Beispiel mappen
            $untisId = trim($data[0]);
            $dateString = trim($data[1]); // Format: "20260914"
            $courseHour = (int)trim($data[2]);
            $absenceId = (int)trim($data[3]);
            $courseId = (int)trim($data[4]);
            $plannedTeacher = trim($data[5]);
            $updatedTeacher = trim($data[6]);
            $plannedSubject = trim($data[7]);
            $plannedSubjectComment = trim($data[8]);
            $updatedSubject = trim($data[9]);
            $updatedSubjectComment = trim($data[10]);
            $plannedRoom = str_replace('~', ', ', trim($data[11]));
            $updatedRoom = str_replace('~', ', ', trim($data[12]));
            $statistics = trim($data[13]);
            $classes = str_replace('~', ', ', trim($data[14]));
            $absenceReason = trim($data[15]);
            $message = trim($data[16]);
            $bit = trim($data[17]);
            $updatedClasses = trim($data[18]);
            $type = trim($data[19]);
            $updatedAt  = trim($data[20]);
            $gpuBit = GpuSubstitutionFlag::getActiveFlags($bit);
            $gpuBit = implode(', ', $gpuBit);
            $gpuType = GpuSubstitutionType::tryFromChar($type)?->value;
            $gpuCount = trim($data[21]);

            // BackupItem anlegen
            if ($makeBackup) {
                $backupItem = new BackupItem();
                $backupItem
                    ->setSubstitutionId($untisId)
                    ->setIncidentDate(\DateTimeImmutable::createFromFormat('Ymd', $dateString))
                    ->setHour($courseHour)
                    ->setAbsenceId($absenceId)
                    ->setClassId($courseId)
                    ->setAbsentTeacher($plannedTeacher)
                    ->setSubstitutionTeacher($updatedTeacher)
                    ->setAbsentCourse($plannedSubject)
                    ->setAbsentCourseStatistics($plannedSubjectComment)
                    ->setSubstitutionCourse($updatedSubject)
                    ->setSubstitutionCourseStatistics($updatedSubjectComment)
                    ->setAbsentRoom($plannedRoom)
                    ->setSubstitutionRoom($plannedRoom)
                    ->setStatistics($statistics)
                    ->setAbsentClasses($classes)
                    ->setAbsenceReason($absenceReason)
                    ->setAbsenceComment($message)
                    ->setBit((int)$bit)
                    ->setSubstitutionClasses($updatedClasses)
                    ->setSubstitutionType($gpuType)
                    ->setUpdatedAt(\DateTimeImmutable::createFromFormat('YmdHi', $updatedAt))
                    ->setAbsenceCount($gpuCount)
                    ->setBackupBag($backupBag);
                $this->em->persist($backupItem);
            }

            // 2. Logik bestimmen
            $isSubstitution = (trim($data[19]) === 'S' || (trim($data[19]) === '' and $updatedTeacher !== '') || trim($data[19]) === '~') ||
                trim($data[19]) === 'A' && ($plannedTeacher !== '' and $updatedTeacher !== '' and $plannedTeacher !== $updatedTeacher and $updatedSubject !== ''); // Substitution
            $isCancellation = trim($data[19]) === 'C' || (trim($data[19]) === '' and $updatedTeacher === '');; // Cancellation
            $isPropagation  = trim($data[19]) === 'P'; // Propagation

            // $isRoomChangeOnly = ($plannedTeacher === $updatedTeacher) && ($plannedRoom !== $updatedRoom);
            $amount = 0;
            if (str_contains($gpuCount, '-')) {
                $amount = -1;
            }
            // 3. Einträge erzeugen
            if ($isSubstitution || $isPropagation) {
                // A) Ursprünglicher Lehrer verliert die Stunde (-1)
                $this->createEntry(
                    $untisId . '_orig',
                    $plannedTeacher,
                    $dateString,
                    $courseHour,
                    $amount,
                    "Ausfall (vertr. d. {$updatedTeacher})",
                    $plannedSubject, $classes, $plannedRoom, $currentUser, $autoCreate, $message, $gpuBit, $gpuType, $gpuCount
                );


                $amount = 0;
                if (str_contains($gpuCount, '+')) {
                    $amount = 1;
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

                $amount = 0;
                if (str_contains($gpuCount, '-')) {
                    $amount = -1;
                }
                // C) Reiner Ausfall ohne Vertretung (-1)
                $this->createEntry(
                    $untisId,
                    $plannedTeacher,
                    $dateString,
                    $courseHour,
                    $amount,
                    "Ausfall",
                    $plannedSubject, $classes, $plannedRoom, $currentUser, $autoCreate, $message, $gpuBit, $gpuType, $gpuCount
                );
                $importCount++;
            }

            $count++;
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
