<?php

namespace App\Entity;

use App\Repository\TeacherRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $birthdate = null;

    /**
     * @var Collection<int, TeacherDebutat>
     */
    #[ORM\OneToMany(targetEntity: TeacherDebutat::class, mappedBy: 'teacher')]
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
