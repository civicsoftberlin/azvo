<?php

namespace App\Entity;

use App\Repository\AzVoRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AzVoRepository::class)]
class AzVo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $debutat = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $validFrom = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $invalidFrom = null;

    public function getId(): ?int
    {
        return $this->id;
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
}
