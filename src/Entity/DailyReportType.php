<?php

namespace App\Entity;

use App\Repository\DailyReportTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DailyReportTypeRepository::class)]
class DailyReportType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $label = null;

    /**
     * @var Collection<int, DailyReport>
     */
    #[ORM\OneToMany(targetEntity: DailyReport::class, mappedBy: 'type')]
    private Collection $dailyReports;

    public function __construct()
    {
        $this->dailyReports = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->label;
    }

    public function getId(): ?int
    {
        return $this->id;
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

    /**
     * @return Collection<int, DailyReport>
     */
    public function getDailyReports(): Collection
    {
        return $this->dailyReports;
    }

    public function addDailyReport(DailyReport $dailyReport): static
    {
        if (!$this->dailyReports->contains($dailyReport)) {
            $this->dailyReports->add($dailyReport);
            $dailyReport->setType($this);
        }

        return $this;
    }

    public function removeDailyReport(DailyReport $dailyReport): static
    {
        if ($this->dailyReports->removeElement($dailyReport)) {
            // set the owning side to null (unless already changed)
            if ($dailyReport->getType() === $this) {
                $dailyReport->setType(null);
            }
        }

        return $this;
    }
}
