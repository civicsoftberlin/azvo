<?php

namespace App\Entity;

use App\Repository\OvertimeEntryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: OvertimeEntryRepository::class)]
#[ORM\UniqueConstraint(name: 'unique_untis_teacher', columns: ['untis_id', 'teacher_id'])]
class OvertimeEntry
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $untisId = null;

    #[ORM\ManyToOne(inversedBy: 'overtimeEntries')]
    private ?Teacher $teacher = null;

    #[ORM\Column(length: 255)]
    private ?string $teacherShortname = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $incidentDate = null;

    #[ORM\Column]
    private ?int $courseHour = null;

    #[ORM\Column]
    private ?int $amount = null;

    #[ORM\Column(length: 255)]
    private ?string $reason = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $subject = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $class = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $room = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $expiresAt = null;

    #[ORM\OneToOne(targetEntity: self::class, cascade: ['persist', 'remove'])]
    private ?self $invalidatedBy = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'overtimeEntries')]
    private ?User $createdBy = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $comment = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $hook = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUntisId(): ?string
    {
        return $this->untisId;
    }

    public function setUntisId(?string $untisId): static
    {
        $this->untisId = $untisId;

        return $this;
    }

    public function getTeacher(): ?Teacher
    {
        return $this->teacher;
    }

    public function setTeacher(?Teacher $teacher): static
    {
        $this->teacher = $teacher;

        return $this;
    }

    public function getTeacherShortname(): ?string
    {
        return $this->teacherShortname;
    }

    public function setTeacherShortname(string $teacherShortname): static
    {
        $this->teacherShortname = $teacherShortname;

        return $this;
    }

    public function getIncidentDate(): ?\DateTime
    {
        return $this->incidentDate;
    }

    public function setIncidentDate(\DateTime $incidentDate): static
    {
        $this->incidentDate = $incidentDate;

        return $this;
    }

    public function getCourseHour(): ?int
    {
        return $this->courseHour;
    }

    public function setCourseHour(int $courseHour): static
    {
        $this->courseHour = $courseHour;

        return $this;
    }

    public function getAmount(): ?int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(string $reason): static
    {
        $this->reason = $reason;

        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    public function getClass(): ?string
    {
        return $this->class;
    }

    public function setClass(?string $class): static
    {
        $this->class = $class;

        return $this;
    }

    public function getRoom(): ?string
    {
        return $this->room;
    }

    public function setRoom(?string $room): static
    {
        $this->room = $room;

        return $this;
    }

    public function getExpiresAt(): ?\DateTime
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(\DateTime $expiresAt): static
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    public function getInvalidatedBy(): ?self
    {
        return $this->invalidatedBy;
    }

    public function setInvalidatedBy(?self $invalidatedBy): static
    {
        $this->invalidatedBy = $invalidatedBy;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }

    public function getHook(): ?string
    {
        return $this->hook;
    }

    public function setHook(?string $hook): static
    {
        $this->hook = $hook;

        return $this;
    }
}
