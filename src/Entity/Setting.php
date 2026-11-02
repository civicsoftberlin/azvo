<?php

namespace App\Entity;

use App\Repository\SettingRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SettingRepository::class)]
class Setting
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?bool $autoImportActive = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $autoImportUrl = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastImportDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lastImportMessage = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isAutoImportActive(): ?bool
    {
        return $this->autoImportActive;
    }

    public function setAutoImportActive(bool $autoImportActive): static
    {
        $this->autoImportActive = $autoImportActive;

        return $this;
    }

    public function getAutoImportUrl(): ?string
    {
        return $this->autoImportUrl;
    }

    public function setAutoImportUrl(?string $autoImportUrl): static
    {
        $this->autoImportUrl = $autoImportUrl;

        return $this;
    }

    public function getLastImportDate(): ?\DateTimeImmutable
    {
        return $this->lastImportDate;
    }

    public function setLastImportDate(?\DateTimeImmutable $lastImportDate): static
    {
        $this->lastImportDate = $lastImportDate;

        return $this;
    }

    public function getLastImportMessage(): ?string
    {
        return $this->lastImportMessage;
    }

    public function setLastImportMessage(?string $lastImportMessage): static
    {
        $this->lastImportMessage = $lastImportMessage;

        return $this;
    }
}
