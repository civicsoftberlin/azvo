<?php

namespace App\Service;

use App\Entity\BackupBag;
use App\Entity\BackupItem;
use App\Entity\Teacher;
use App\Entity\OvertimeEntry;
use App\Enum\GpuSubstitutionFlag;
use App\Enum\GpuSubstitutionType;
use App\Repository\OvertimeEntryRepository;
use Btinet\Untis\Model\UntisSubstitutionModel;
use DateMalformedStringException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Security\Core\User\UserInterface;

class WorkingTimeImportService
{
    private array $errors = [];
    private UntisSubstitutionModel $substitutionModel;
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly OvertimeEntryRepository $overtimeEntryRepository,
    ) {
        $this->substitutionModel = new UntisSubstitutionModel();
    }

    /**
     * @throws DateMalformedStringException
     */
    public function importFromCsv(File $file, ?UserInterface $currentUser, bool $autoCreate, bool $makeBackup): array
    {
        $handle  = fopen($file->getPathname(), "r");
        if ($handle === false) {
            throw new \RuntimeException('CSV-Datei konnte nicht geöffnet werden.');
        }

        $entries = $this->overtimeEntryRepository->findAllFromToday();
        foreach ($entries as $entry) {
            $this->em->remove($entry);
        }
        $this->em->flush();

        $importCount = 0;
        while (($data = fgetcsv($handle, 10000, ',', '"', "")) !== false) {
            $this->substitutionModel->setData($data);
            $rows = $this->substitutionModel->getSplitter()->getSubstitutionRows();

            foreach ($rows as $row) {
                $classes = implode(", ", $row->getClasses());
                $bitArray = array_map(fn($item) => $item->getGermanLabel(), $row->getBits());
                $bits = implode(", ", $bitArray);
                $counts = implode(", ", $row->getCount());

                $this->createEntry(
                    uniqueUntisRef: $row->getUntisId(), teacherKuerzel: $row->getTeacher(),
                    dateString: $row->getDate()->format('Ymd'), courseHour: $row->getCourseHour(),
                    amount: $row->getAmount(), reason: $row->getReason(), subject: $row->getSubject(),
                    className: $classes, room: $row->getRoom(), currentUser: $currentUser, autoCreate: $autoCreate,
                    message: $row->getMessage(), gpuBit: $bits, gpuType: $row->getType()?->value, gpuCount: $counts,
                );
                $importCount++;
            }
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
        ?string $reason,
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
