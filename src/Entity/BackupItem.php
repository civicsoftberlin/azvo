<?php

namespace App\Entity;

use App\Repository\BackupItemRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: BackupItemRepository::class)]
class BackupItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?int $substitutionId = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $incidentDate = null;

    #[ORM\Column(nullable: true)]
    private ?int $hour = null;

    #[ORM\Column]
    private ?int $absenceId = null;

    #[ORM\Column]
    private ?int $classId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $absentTeacher = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $substitutionTeacher = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $absentCourse = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $substitutionCourse = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $absentCourseStatistics = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $substitutionCourseStatistics = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $absentRoom = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $substitutionRoom = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $statistics = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $absentClasses = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $absenceReason = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $absenceComment = null;

    #[ORM\Column(nullable: true)]
    private ?int $bit = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $substitutionClasses = null;

    #[ORM\Column(length: 1, nullable: true)]
    private ?string $substitutionType = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(length: 5, nullable: true)]
    private ?string $absenceCount = null;

    #[ORM\ManyToOne(inversedBy: 'backupItems')]
    private ?BackupBag $backupBag = null;

    public function __toString(): string
    {
        return $this->createdAt->format('d.m.Y');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getSubstitutionId(): ?int
    {
        return $this->substitutionId;
    }

    public function setSubstitutionId(int $substitutionId): static
    {
        $this->substitutionId = $substitutionId;

        return $this;
    }

    public function getIncidentDate(): ?\DateTimeImmutable
    {
        return $this->incidentDate;
    }

    public function setIncidentDate(\DateTimeImmutable $incidentDate): static
    {
        $this->incidentDate = $incidentDate;

        return $this;
    }

    public function getHour(): ?int
    {
        return $this->hour;
    }

    public function setHour(?int $hour): static
    {
        $this->hour = $hour;

        return $this;
    }

    public function getAbsenceId(): ?int
    {
        return $this->absenceId;
    }

    public function setAbsenceId(int $absenceId): static
    {
        $this->absenceId = $absenceId;

        return $this;
    }

    public function getClassId(): ?int
    {
        return $this->classId;
    }

    public function setClassId(int $classId): static
    {
        $this->classId = $classId;

        return $this;
    }

    public function getAbsentTeacher(): ?string
    {
        return $this->absentTeacher;
    }

    public function setAbsentTeacher(?string $absentTeacher): static
    {
        $this->absentTeacher = $absentTeacher;

        return $this;
    }

    public function getSubstitutionTeacher(): ?string
    {
        return $this->substitutionTeacher;
    }

    public function setSubstitutionTeacher(?string $substitutionTeacher): static
    {
        $this->substitutionTeacher = $substitutionTeacher;

        return $this;
    }

    public function getAbsentCourse(): ?string
    {
        return $this->absentCourse;
    }

    public function setAbsentCourse(?string $absentCourse): static
    {
        $this->absentCourse = $absentCourse;

        return $this;
    }

    public function getSubstitutionCourse(): ?string
    {
        return $this->substitutionCourse;
    }

    public function setSubstitutionCourse(?string $substitutionCourse): static
    {
        $this->substitutionCourse = $substitutionCourse;

        return $this;
    }

    public function getAbsentCourseStatistics(): ?string
    {
        return $this->absentCourseStatistics;
    }

    public function setAbsentCourseStatistics(?string $absentCourseStatistics): static
    {
        $this->absentCourseStatistics = $absentCourseStatistics;

        return $this;
    }

    public function getSubstitutionCourseStatistics(): ?string
    {
        return $this->substitutionCourseStatistics;
    }

    public function setSubstitutionCourseStatistics(?string $substitutionCourseStatistics): static
    {
        $this->substitutionCourseStatistics = $substitutionCourseStatistics;

        return $this;
    }

    public function getAbsentRoom(): ?string
    {
        return $this->absentRoom;
    }

    public function setAbsentRoom(?string $absentRoom): static
    {
        $this->absentRoom = $absentRoom;

        return $this;
    }

    public function getSubstitutionRoom(): ?string
    {
        return $this->substitutionRoom;
    }

    public function setSubstitutionRoom(?string $substitutionRoom): static
    {
        $this->substitutionRoom = $substitutionRoom;

        return $this;
    }

    public function getStatistics(): ?string
    {
        return $this->statistics;
    }

    public function setStatistics(?string $statistics): static
    {
        $this->statistics = $statistics;

        return $this;
    }

    public function getAbsentClasses(): ?string
    {
        return $this->absentClasses;
    }

    public function setAbsentClasses(?string $absentClasses): static
    {
        $this->absentClasses = $absentClasses;

        return $this;
    }

    public function getAbsenceReason(): ?string
    {
        return $this->absenceReason;
    }

    public function setAbsenceReason(?string $absenceReason): static
    {
        $this->absenceReason = $absenceReason;

        return $this;
    }

    public function getAbsenceComment(): ?string
    {
        return $this->absenceComment;
    }

    public function setAbsenceComment(?string $absenceComment): static
    {
        $this->absenceComment = $absenceComment;

        return $this;
    }

    public function getBit(): ?int
    {
        return $this->bit;
    }

    public function setBit(?int $bit): static
    {
        $this->bit = $bit;

        return $this;
    }

    public function getSubstitutionClasses(): ?string
    {
        return $this->substitutionClasses;
    }

    public function setSubstitutionClasses(?string $substitutionClasses): static
    {
        $this->substitutionClasses = $substitutionClasses;

        return $this;
    }

    public function getSubstitutionType(): ?string
    {
        return $this->substitutionType;
    }

    public function setSubstitutionType(?string $substitutionType): static
    {
        $this->substitutionType = $substitutionType;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getAbsenceCount(): ?string
    {
        return $this->absenceCount;
    }

    public function setAbsenceCount(?string $absenceCount): static
    {
        $this->absenceCount = $absenceCount;

        return $this;
    }

    public function getBackupBag(): ?BackupBag
    {
        return $this->backupBag;
    }

    public function setBackupBag(?BackupBag $backupBag): static
    {
        $this->backupBag = $backupBag;

        return $this;
    }
}
