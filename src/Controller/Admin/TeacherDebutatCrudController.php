<?php

namespace App\Controller\Admin;

use App\Constants\Debutat;
use App\Entity\TeacherDebutat;
use App\Filter\MonthFilter;
use App\Filter\YearFilter;
use App\Repository\TeacherRepository;
use DateMalformedStringException;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;

class TeacherDebutatCrudController extends AbstractCrudController
{
    private TeacherRepository $teacherRepository;

    public function __construct(TeacherRepository $teacherRepository)
    {
        $this->teacherRepository = $teacherRepository;
    }

    public static function getEntityFqcn(): string
    {
        return TeacherDebutat::class;
    }

    public function configureActions(Actions $actions): Actions
    {


        return $actions;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->showEntityActionsInlined()
            ->setPaginatorPageSize(100)
            ->overrideTemplates([
                'crud/index' => 'admin/deputat/index.html.twig',
            ])
            ->setDefaultSort(['teacher' => 'ASC', 'validFrom' => 'ASC'])
            ;
    }

    public function configureResponseParameters(KeyValueStore $responseParameters): KeyValueStore
    {
        // Daten nur auf der Index-Seite laden
        if (Crud::PAGE_INDEX === $responseParameters->get('pageName')) {
            $responseParameters->set('teachers', $this->teacherRepository->findAll());
        }

        return $responseParameters;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            // Filter nach Lehrkraft (Dropdown mit allen Lehrern)
            ->add(EntityFilter::new('teacher', 'Lehrkraft'))
            ;
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('ID')->hideOnForm(),
            AssociationField::new('teacher')->setRequired(true),
            NumberField::new('debutat'),
            NumberField::new('discount')->hideOnForm(),
            DateField::new('discountValidFrom')->hideOnForm(),
            NumberField::new('threshold')->hideOnForm(),
            DateField::new('validFrom')->setHelp("Beginn des Schuljahres oder späterer Dienstantritt"),
            DateField::new('invalidFrom')->setHelp("Ohne Angabe wird das Ende des Schuljahres eingetragen"),
        ];
    }

    /**
     * @throws DateMalformedStringException
     */
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->calculateThreshold($entityInstance);
        parent::persistEntity($entityManager, $entityInstance);
    }

    /**
     * @throws DateMalformedStringException
     */
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->calculateThreshold($entityInstance);
        parent::updateEntity($entityManager, $entityInstance);
    }

    /**
     * @throws DateMalformedStringException
     */
    private function calculateThreshold($entity): void
    {
        $debutat = $entity->getDebutat() ?? 0;
        $validFrom = $entity->getValidFrom();

        $age = $this->getAgeAtLastJuly31Before($entity->getTeacher()->getBirthdate(), $validFrom);

        $ratio = ($debutat > 0) ? $debutat / Debutat::DEFAULT_DEBUTAT : 0;

        $discount = 0;
        if ($ratio >= 0.666 && $age >= 61) {
            $discount = 2;
        } elseif (($ratio >= 0.666 && $age >= 58) || ($ratio >= 0.5 && $age >= 60)) {
            $discount = 1;
        }

        $entity->setDiscount($discount);

        if ($discount > 0 && $validFrom instanceof \DateTimeInterface) {
            $targetYear = (int)$validFrom->format('Y');
            $discountStart = new \DateTime("$targetYear-08-01");
            $entity->setDiscountValidFrom($discountStart);
        }

        // 4. Threshold berechnen (unabhängig vom Alter)
        $amount = 1;
        if ($debutat > Debutat::PARTIAL_TIME_LOW) {
            $amount++;
        }
        if ($debutat > Debutat::PARTIAL_TIME_MEDIUM) {
            $amount++;
        }
        $entity->setThreshold($amount);

        if (null == $entity->getInvalidFrom()) {
            $entity->setInvalidFrom($entity->getValidFrom()->modify('+1 year'));
        }
    }

    /**
     * Berechnet das Alter der Person am letzten 31.07. vor dem gegebenen Referenzdatum.
     *
     * @throws DateMalformedStringException
     */
    private function getAgeAtLastJuly31Before(\DateTimeInterface $birthdate, \DateTimeInterface $referenceDate): int
    {
        $targetYear = (int)$referenceDate->format('Y');

        // Erstelle den 31.07. des Jahres, in dem das Referenzdatum liegt
        $referenceJuly31 = new \DateTime("$targetYear-07-31");

        // Wenn dieser 31.07. NACH dem Referenzdatum liegt (z.B. Referenz ist 01.02.2024),
        // dann war der letzte relevante 31.07. im Vorjahr (also 2023).
        if ($referenceJuly31 > $referenceDate) {
            $referenceJuly31->modify('-1 year');
        }

        // Alter in vollen Jahren (y) zurückgeben
        return $birthdate->diff($referenceJuly31)->y;
    }

}
