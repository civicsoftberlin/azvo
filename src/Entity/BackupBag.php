<?php

namespace App\Entity;

use App\Repository\BackupBagRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: BackupBagRepository::class)]
class BackupBag
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * @var Collection<int, BackupItem>
     */
    #[ORM\OneToMany(targetEntity: BackupItem::class, mappedBy: 'backupBag', cascade: ['remove'])]
    private Collection $backupItems;

    private ?\DateTimeImmutable $startDate = null;
    private ?\DateTimeImmutable $endDate = null;
    private bool $datesCalculated = false;

    public function __construct()
    {
        $this->backupItems = new ArrayCollection();
    }

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
    public function getStartDate(): ?\DateTimeImmutable
    {
        $this->calculateDateBoundaries();
        return $this->startDate;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        $this->calculateDateBoundaries();
        return $this->endDate;
    }

    /**
     * @return Collection<int, BackupItem>
     */
    public function getBackupItems(): Collection
    {
        return $this->backupItems;
    }

    public function addBackupItem(BackupItem $backupItem): static
    {
        if (!$this->backupItems->contains($backupItem)) {
            $this->backupItems->add($backupItem);
            $backupItem->setBackupBag($this);
        }

        return $this;
    }

    public function removeBackupItem(BackupItem $backupItem): static
    {
        if ($this->backupItems->removeElement($backupItem)) {
            // set the owning side to null (unless already changed)
            if ($backupItem->getBackupBag() === $this) {
                $backupItem->setBackupBag(null);
            }
        }

        return $this;
    }

    private function calculateDateBoundaries(): void
    {
        if ($this->datesCalculated) {
            return;
        }

        foreach ($this->getBackupItems() as $item) {
            $date = $item->getIncidentDate();
            if ($date === null) continue;

            if ($this->startDate === null || $date < $this->startDate) {
                $this->startDate = $date;
            }
            if ($this->endDate === null || $date > $this->endDate) {
                $this->endDate = $date;
            }
        }
        $this->datesCalculated = true;
    }
}
