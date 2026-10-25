<?php

namespace App\Controller\Admin;

use App\Constants\Debutat;
use App\Entity\OvertimeEntry;
use App\Entity\Teacher;
use App\Entity\TeacherDebutat;
use App\Filter\MonthFilter;
use App\Filter\YearFilter;
use App\Repository\OvertimeEntryRepository;
use App\Repository\TeacherDebutatRepository;
use App\Repository\TeacherRepository;
use App\Service\ExcelExportService;
use DateMalformedStringException;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\ActionGroup;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TeacherDebutatCrudController extends AbstractCrudController
{
    private TeacherRepository $teacherRepository;
    private TeacherDebutatRepository $teacherDebutatRepository;
    private OvertimeEntryRepository $overtimeEntryRepository;
    private AdminUrlGenerator $adminUrlGenerator;
    private ExcelExportService $excelExportService;

    public function __construct(TeacherRepository $teacherRepository, TeacherDebutatRepository $debutatRepository, OvertimeEntryRepository $overtimeEntryRepository, AdminUrlGenerator $adminUrlGenerator, ExcelExportService $excelExportService)
    {
        $this->teacherRepository = $teacherRepository;
        $this->teacherDebutatRepository = $debutatRepository;
        $this->overtimeEntryRepository = $overtimeEntryRepository;
        $this->adminUrlGenerator = $adminUrlGenerator;
        $this->excelExportService = $excelExportService;
    }

    public static function getEntityFqcn(): string
    {
        return TeacherDebutat::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        $dates = $this->teacherDebutatRepository->findDistinctValidFromDates();

        if (!empty($dates)) {
            $exportActionGroup = ActionGroup::new("batchExport","SchoolYear Export", "ti ti-file-type-xls")
                ->addCssClass('icon-success')
                ->createAsGlobalActionGroup();
            foreach ($dates as $date) {
                $year = (int) DateTime::createFromFormat("Y-m-d", $date)->format('Y');
                $url = $this->adminUrlGenerator
                    ->setController(TeacherDebutatCrudController::class)
                    ->setAction('batchExport')
                    ->set('validFrom', $date)
                    ->setDashboard(DashboardController::class)
                    ->generateUrl();
                $exportActionGroup->addAction(
                    Action::new("export_$year", "$year/" . substr($year + 1, 2, 2) % 100)
                        ->linkToUrl($url)
                );
            }
            $teacherSelectAction = Action::new("teacherSelect", "Details", 'ti ti-school')
                ->linkToUrl(function ($entity) {
                    return $this->adminUrlGenerator
                        ->setController(TeacherCrudController::class)
                        ->setAction(Action::DETAIL)
                        ->setEntityId($entity->getTeacher()->getId())
                        ->generateUrl();
                })
            ;

            $actions
                ->add(Crud::PAGE_INDEX, $exportActionGroup)
                ->add(Crud::PAGE_INDEX, $teacherSelectAction)
            ;
        }
        return $actions;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setDefaultRowAction('teacherSelect')
            ->setPaginatorPageSize(100)
            ->setHelp(Crud::PAGE_INDEX, "Export gilt immer für alle Lehrkräfte im zutreffenden Zeitraum.")
            ->overrideTemplates([
                'crud/index' => 'admin/deputat/index.html.twig',
            ])
            ->setDefaultSort(['teacher.shortName' => 'ASC', 'validFrom' => 'ASC'])
            ;
    }


    public function configureResponseParameters(KeyValueStore $responseParameters): KeyValueStore
    {
        // Daten nur auf der Index-Seite laden
        if (Crud::PAGE_INDEX === $responseParameters->get('pageName')) {
            $responseParameters->set('teachers', $this->teacherRepository->findAll());
            $responseParameters->set('page_title', 'Stundendeputat');
            $filters = $this->getContext()->getRequest()->query->all('filters');
            if (!empty($filters['teacher'])) {
                $teacher = $this->teacherRepository->find($filters['teacher']['value']);
                $responseParameters->set('page_title', sprintf('Stundendeputat für %s', $teacher));
            }
        }
        if (Crud::PAGE_DETAIL === $responseParameters->get('pageName')) {
            $teacher = $this->getContext()->getEntity()->getInstance()->getTeacher();
            $responseParameters->set('page_title', sprintf('Details für %s', $teacher));
        }
        if (Crud::PAGE_EDIT === $responseParameters->get('pageName')) {
            $teacher = $this->getContext()->getEntity()->getInstance()->getTeacher();
            $responseParameters->set('page_title', sprintf('Stundendeputat für %s bearbeiten', $teacher));
        }
        if (Crud::PAGE_NEW === $responseParameters->get('pageName')) {
            $responseParameters->set('page_title', 'Neues Stundendeputat erzeugen');
        }
        $responseParameters->set('icon', 'calendar');

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

            FormField::addRow(),
            AssociationField::new('teacher')->setRequired(true)
                ->setSortProperty('shortName')
                ->setQueryBuilder(function (QueryBuilder $queryBuilder) {
                    return $queryBuilder->orderBy('entity.shortName', 'ASC');
                })
                ->setColumns(2),

            FormField::addFieldset('hours')
                ->setIcon('ti ti-clock'),
            FormField::addRow(),
            NumberField::new('debutat')
                ->setColumns(2),

            FormField::addRow(),
            NumberField::new('discount')->setRequired(true)
                ->setColumns(2),

            FormField::addRow(),
            NumberField::new('threshold')
                ->setColumns(2),

            FormField::addFieldset('validRange')
                ->setIcon('ti ti-calendar'),
            FormField::addRow(),
            DateField::new('validFrom')
                ->setColumns(1),
            DateField::new('invalidFrom')
                ->setRequired(true)
                ->setColumns(1),

            FormField::addFieldset('Settings')
                ->setIcon('ti ti-settings'),
            BooleanField::new('leaves')
                ->setColumns(12)
                ->setHelp("Wenn aktiv, wird kein Folgeschuljahr mehr erzeugt. Berechnungen enden zum Ablaufdatum.")
            ,
            BooleanField::new('autoAppend')
                ->setColumns(12)
                ->setHelp("Wird immer am 01.08. des Folgejahres erzeugt. Stundenwerte werden wie zuvor definiert übernommen.<br><b>Solange sich keine Werte ändern, muss so das Schuljahr nur einmalig angelegt werden.</b>"),
        ];
    }

    /**
     * @throws DateMalformedStringException
     */
    public function createEntity(string $entityFqcn): object
    {
        /** @var TeacherDebutat $entity  */
        $entity = new $entityFqcn();
        $year = date('Y');
        $startDate = new DateTimeImmutable("$year-08-1");
        $endDate = $startDate->modify('+1 year');

        $entity->setDebutat(26);
        $entity->setDiscount(0);
        $entity->setThreshold(3);
        $entity->setValidFrom(DateTime::createFromImmutable($startDate));
        $entity->setValidFrom(DateTime::createFromImmutable($startDate));
        $entity->setInvalidFrom(DateTime::createFromImmutable($endDate));
        $entity->setAutoAppend(true);
        $entity->setLeaves(false);
        return $entity;
    }

    /**
     * @throws DateMalformedStringException
     */
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        //$this->calculateThreshold($entityInstance);
        $this->addFlash("success", sprintf("Deputat für %s wurde erstellt.", $entityInstance->getTeacher()->getShortName()));
        parent::persistEntity($entityManager, $entityInstance);
    }

    /**
     * @throws DateMalformedStringException
     */
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        //$this->calculateThreshold($entityInstance);
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
            $discountStart = new DateTime("$targetYear-08-01");
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
        $referenceJuly31 = new DateTime("$targetYear-07-31");

        // Wenn dieser 31.07. NACH dem Referenzdatum liegt (z.B. Referenz ist 01.02.2024),
        // dann war der letzte relevante 31.07. im Vorjahr (also 2023).
        if ($referenceJuly31 > $referenceDate) {
            $referenceJuly31->modify('-1 year');
        }

        // Alter in vollen Jahren (y) zurückgeben
        return $birthdate->diff($referenceJuly31)->y;
    }

    /**
     * @throws DateMalformedStringException
     */
    #[AdminRoute(name: "batchExport")]
    public function batchExport(Request $request): StreamedResponse|RedirectResponse
    {
        $columns = [
            ['header' => 'Lehrkraft', 'align' => 'left'],
            ['header' => 'Datum', 'align' => 'left'],
            ['header' => 'Klasse', 'align' => 'left'],
            ['header' => 'Schulstunde', 'align' => 'right'],
            ['header' => 'Grund', 'align' => 'left'],
            ['header' => 'Untis-Kommentar', 'align' => 'left'],
            ['header' => 'Differenz', 'align' => 'right', 'sum' => true],
            ['header' => 'Systemkommentar', 'align' => 'left'],
            ['header' => 'ID', 'align' => 'right'],
            ['header' => 'ausgeglichen durch ID', 'align' => 'right'],
            ['header' => 'auszugleichen bis', 'align' => 'right'],
            ['header' => 'ausgeglichen am', 'align' => 'right'],
        ];
        $validFromString = $request->query->get("validFrom");
        $validFrom = new DateTime($validFromString);
        $validTo = new DateTime($validFromString)->modify('+1 year');
        $teachers = $this->teacherRepository->findDistinctByTeacherDebutatValidFrom($validFrom);
        if (empty($teachers)) {
            $this->addFlash('warning', sprintf('Keine Einträge für den Zeitraum ab %s gefunden.', $validFrom->format('d.m.Y')));
            $referer = $request->headers->get('referer');

            if ($referer) {
                return $this->redirect($referer);
            }
        }
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);
        foreach ($teachers as $teacher) {
            /** @var Teacher $teacher */
            $rows = [];
            $teacherDebutat = $this->teacherDebutatRepository->findOneBy(['validFrom' => $validFromString, 'teacher' => $teacher]);
            if (!$teacherDebutat) {
                continue;
            }
            $overtimeEntries = $this->overtimeEntryRepository->getEntriesWithinPeriod(
                $teacher,
                $validFromString,
                $validTo->format('Y-m-d'),
            );
            foreach ($overtimeEntries as $entry) {
                if ($entry->getComment() === 'CARRYOVER') {
                    continue;
                }
                $rows[] = [
                    $entry->getTeacherShortname(),
                    $entry->getIncidentDate() ? $entry->getIncidentDate()->format('d.m.Y') : '',
                    $entry->getClass() ?: '',
                    $entry->getCourseHour() ?: '',
                    $entry->getReason() ?: '',
                    $entry->getUntisComment() ?: '',
                    $entry->getAmount() ?: '0',
                    $entry->getComment() ?: '',
                    $entry->getId(),
                    $entry->getInvalidatedBy() ?: '',
                    $entry->getExpiresAt() ? $entry->getExpiresAt()->format('d.m.Y') : '',
                    $entry->getInvalidatedAt() ? $entry->getInvalidatedAt()->format('d.m.Y') : '',
                ];
            }
            $sheet = $spreadsheet->createSheet()->setTitle(strtoupper($teacher->getShortName()));
            $sum = $this->overtimeEntryRepository->sumAmountByPeriod($validFrom, $validTo, $teacher);
            $result = $this->excelExportService->createSheetContent($sheet, $columns, $rows, $teacherDebutat, $sum);
            if ($result) {
                $sheet->getTabColor()->setRGB('ffcdd2');
            }
        }
        $spreadsheet->setActiveSheetIndex(0);
        $response = new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });

        $year = $validFrom->format('Y');
        $nextYear = (int)$year + 1;
        $filename = sprintf('%s-Deputat_Export_Schuljahr_%s-%s.xlsx', date("ymd"), $year, $nextYear);

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename);
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }

}
