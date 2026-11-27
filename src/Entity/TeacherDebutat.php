<?php

namespace App\Entity;

use App\Repository\TeacherDebutatRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TeacherDebutatRepository::class)]
class TeacherDebutat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'teacherDebutats')]
    private ?Teacher $teacher = null;

    #[ORM\Column]
    private ?int $debutat = null;

    #[ORM\Column(nullable: true)]
    private ?int $discount = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $validFrom = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $invalidFrom = null;

    #[ORM\Column]
    private ?int $threshold = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $discountValidFrom = null;

    #[ORM\Column(nullable: true)]
    private ?bool $autoAppend = null;

    #[ORM\Column(nullable: true)]
    private ?bool $leaves = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDebutat(): ?int
    {
        return $this->debutat;
    }

    public function setDebutat(int $debutat): static
    {
        $this->debutat = $debutat;

        return $this;
    }

    public function getDiscount(): ?int
    {
        return $this->discount;
    }

    public function setDiscount(?int $discount): static
    {
        $this->discount = $discount;

        return $this;
    }

    public function getValidFrom(): ?\DateTimeImmutable
    {
        return $this->validFrom;
    }

    public function setValidFrom(\DateTimeImmutable $validFrom): static
    {
        $this->validFrom = $validFrom;

        return $this;
    }

    public function getInvalidFrom(): ?\DateTimeImmutable
    {
        return $this->invalidFrom;
    }

    public function setInvalidFrom(?\DateTimeImmutable $invalidFrom): static
    {
        $this->invalidFrom = $invalidFrom;

        return $this;
    }

    public function getThreshold(): ?int
    {
        return $this->threshold;
    }

    public function setThreshold(int $threshold): static
    {
        $this->threshold = $threshold;

        return $this;
    }

    public function getDiscountValidFrom(): ?\DateTimeImmutable
    {
        return $this->discountValidFrom;
    }

    public function setDiscountValidFrom(?\DateTimeImmutable $discountValidFrom): static
    {
        $this->discountValidFrom = $discountValidFrom;

        return $this;
    }

    public function isAutoAppend(): ?bool
    {
        return $this->autoAppend;
    }

    public function setAutoAppend(?bool $autoAppend): static
    {
        $this->autoAppend = $autoAppend;

        return $this;
    }

    public function isLeaves(): ?bool
    {
        return $this->leaves;
    }

    public function setLeaves(?bool $leaves): static
    {
        $this->leaves = $leaves;

        return $this;
    }
}
