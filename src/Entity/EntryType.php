<?php

namespace App\Entity;

use App\Repository\EntryTypeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EntryTypeRepository::class)]
class EntryType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $untisCode = null;

    #[ORM\Column(length: 255)]
    private ?string $label = null;

    #[ORM\Column]
    private ?bool $effectsTotal = null;

    #[ORM\Column]
    private ?float $factor = null;

    public function __toString(): string
    {
        return $this->label;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUntisCode(): ?string
    {
        return $this->untisCode;
    }

    public function setUntisCode(?string $untisCode): static
    {
        $this->untisCode = $untisCode;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function isEffectsTotal(): ?bool
    {
        return $this->effectsTotal;
    }

    public function setEffectsTotal(bool $effectsTotal): static
    {
        $this->effectsTotal = $effectsTotal;

        return $this;
    }

    public function getFactor(): ?float
    {
        return $this->factor;
    }

    public function setFactor(float $factor): static
    {
        $this->factor = $factor;

        return $this;
    }
}
