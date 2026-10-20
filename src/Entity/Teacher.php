<?php

namespace App\Entity;

use App\Repository\TeacherRepository;
use DateMalformedStringException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TeacherRepository::class)]
class Teacher
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]

    private ?string $shortName = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $birthdate = null;

    /**
     * @var Collection<int, TeacherDebutat>
     */
    #[ORM\OneToMany(targetEntity: TeacherDebutat::class, mappedBy: 'teacher', cascade: ['remove'])]
    private Collection $teacherDebutats;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $firstname = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lastname = null;

    /**
     * @var Collection<int, OvertimeEntry>
     */
    #[ORM\OneToMany(targetEntity: OvertimeEntry::class, mappedBy: 'teacher')]
    private Collection $overtimeEntries;

    public function __construct()
    {
        $this->teacherDebutats = new ArrayCollection();
        $this->overtimeEntries = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->shortName;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getShortName(): ?string
    {
        return $this->shortName;
    }

    public function setShortName(string $shortName): static
    {
        $this->shortName = $shortName;

        return $this;
    }

    public function getBirthdate(): ?\DateTime
    {
        return $this->birthdate;
    }

    public function setBirthdate(\DateTime $birthdate): static
    {
        $this->birthdate = $birthdate;

        return $this;
    }

    /**
     * @return Collection<int, TeacherDebutat>
     */
    public function getTeacherDebutats(): Collection
    {
        return $this->teacherDebutats;
    }

    public function addTeacherDebutat(TeacherDebutat $teacherDebutat): static
    {
        if (!$this->teacherDebutats->contains($teacherDebutat)) {
            $this->teacherDebutats->add($teacherDebutat);
            $teacherDebutat->setTeacher($this);
        }

        return $this;
    }

    public function removeTeacherDebutat(TeacherDebutat $teacherDebutat): static
    {
        if ($this->teacherDebutats->removeElement($teacherDebutat)) {
            // set the owning side to null (unless already changed)
            if ($teacherDebutat->getTeacher() === $this) {
                $teacherDebutat->setTeacher(null);
            }
        }

        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(?string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(?string $lastname): static
    {
        $this->lastname = $lastname;

        return $this;
    }

    /**
     * @return Collection<int, OvertimeEntry>
     */
    public function getOvertimeEntries(): Collection
    {
        return $this->overtimeEntries;
    }

    /**
     * @throws DateMalformedStringException
     */
    public function getCurrentMonthOvertimeEntries(?\DateTimeInterface $date = null, ?string $filter = null): Collection
    {
        if ($date === null) {
            $startOfMonth = new \DateTime('first day of this month');
            $endOfMonth = new \DateTime('last day of this month 23:59:59');
        } else {
            $startOfMonth = $date;
            $endOfMonth = $date->modify('last day of this month 23:59:59');
        }


        $criteria = Criteria::create()
            ->where(Criteria::expr()->gte('incidentDate', $startOfMonth->format('Y-m-d')))
            ->andWhere(Criteria::expr()->lte('incidentDate', $endOfMonth->format('Y-m-d')));

        if ($filter !== null) {
            $expr = Criteria::expr();
            $criteria->andWhere(
                $expr->orX(
                    $expr->neq('comment', $filter),      // comment != $filter
                    $expr->isNull('comment')             // OR comment IS NULL
                )
            );
        }

        // Filtert die bereits geladene Collection (oder baut eine SQL-Abfrage, wenn noch nicht geladen)
        return $this->overtimeEntries->matching($criteria);
    }

    /**
     * @throws DateMalformedStringException
     */
    public function countCurrentMonthOvertimeEntries(): int
    {
        $entries = $this->getCurrentMonthOvertimeEntries();
        $totalAmount = 0;

        foreach ($entries as $entry) {
            /** @var OvertimeEntry $entry */
            $totalAmount += $entry->getAmount();
        }
        return $totalAmount;
    }

    /**
     * @throws DateMalformedStringException
     */
    public function countLastMonthOvertimeEntries(): int
    {
        $entries = $this->getCurrentMonthOvertimeEntries(
            new \DateTimeImmutable('first day of last month'),
            "DECAY"
        );
        $totalAmount = 0;

        foreach ($entries as $entry) {
            /** @var OvertimeEntry $entry */
            $totalAmount += $entry->getAmount();
        }
        return $totalAmount;
    }

    /**
     * @throws DateMalformedStringException
     */
    public function countCurrentExpiringEntries(): int
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('expiresAt', new \DateTime("last day of this month")->format('Y-m-d')))
            ->andWhere(Criteria::expr()->isNull('invalidatedAt'))
            //->andWhere(Criteria::expr()->neq('comment', "CARRYOVER"))
        ;
        $entries = $this->overtimeEntries->matching($criteria);
        return $entries->count();
    }

    public function addOvertimeEntry(OvertimeEntry $overtimeEntry): static
    {
        if (!$this->overtimeEntries->contains($overtimeEntry)) {
            $this->overtimeEntries->add($overtimeEntry);
            $overtimeEntry->setTeacher($this);
        }

        return $this;
    }

    public function removeOvertimeEntry(OvertimeEntry $overtimeEntry): static
    {
        if ($this->overtimeEntries->removeElement($overtimeEntry)) {
            // set the owning side to null (unless already changed)
            if ($overtimeEntry->getTeacher() === $this) {
                $overtimeEntry->setTeacher(null);
            }
        }

        return $this;
    }
}
