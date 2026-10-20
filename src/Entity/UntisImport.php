<?php

namespace App\Entity;

use App\Repository\UntisImportRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Entity\File as EmbeddedFile;
use Vich\UploaderBundle\Mapping\Attribute as Vich;

#[ORM\Entity(repositoryClass: UntisImportRepository::class)]
#[Vich\Uploadable]
class UntisImport
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Vich\UploadableField(mapping: 'gpu', fileNameProperty: 'upload', size: 'size')]
    private ?File $uploadFile = null;

    #[ORM\Transient] // Sagt Doctrine: "Ignorier mich, keine DB-Spalte!"
    private bool $autoCreate = false;

    #[ORM\Embedded(class: 'Vich\UploaderBundle\Entity\File')]
    private ?EmbeddedFile $upload = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __toString(): string
    {
        return $this->getFilename() ?? "kein Dateiname vorhanden";
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUploadFile(): ?File
    {
        return $this->uploadFile;
    }

    public function setUploadFile(?File $uploadFile): static
    {
        $this->uploadFile = $uploadFile;

        if (null !== $uploadFile) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function getUpload(): ?EmbeddedFile
    {
        return $this->upload;
    }

    public function setUpload(?EmbeddedFile $upload): void
    {
        $this->upload = $upload;
    }

    public function getFilename(): ?string
    {
        return $this->upload?->getName();
    }



    public function getInfo(): string
    {
        return <<<DATA
        Klassen/Kurse, die den Schlüsselwörtern der Ausschlussliste
        entsprechen, werden beim Import ignoriert.
        DATA;
    }

    public function setInfo(string $info): static
    {
        return $this;
    }

    public function isAutoCreate(): bool { return $this->autoCreate; }
    public function setAutoCreate(bool $autoCreate): self { $this->autoCreate = $autoCreate; return $this; }

}
