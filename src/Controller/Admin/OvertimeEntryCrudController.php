<?php

namespace App\Controller\Admin;

use App\Entity\OvertimeEntry;
use App\Filter\MonthFilter;
use App\Filter\YearFilter;
use App\Repository\OvertimeEntryRepository;
use App\Repository\TeacherDebutatRepository;
use App\Repository\TeacherRepository;
use App\Service\ExcelExportService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
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
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterCrudActionEvent;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FieldFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FilterFactory;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OvertimeEntryCrudController extends AbstractCrudController
{

    private EntityRepository $eaEntityRepository;
    private TeacherRepository $teacherRepository;
    private OvertimeEntryRepository $overtimeEntryRepository;
    private ExcelExportService $exportService;
    private FieldFactory $fieldFactory;
    private FilterFactory $filterFactory;

    // Service hier injizieren
    public function __construct(EntityRepository $eaEntityRepository, OvertimeEntryRepository $overtimeEntryRepository, TeacherRepository $teacherRepo, FieldFactory $fieldFactory, FilterFactory $filterFactory, ExcelExportService $exportService)
    {
        $this->eaEntityRepository = $eaEntityRepository;
        $this->overtimeEntryRepository = $overtimeEntryRepository;
        $this->teacherRepository = $teacherRepo;
        $this->fieldFactory = $fieldFactory;
        $this->filterFactory = $filterFactory;
        $this->exportService = $exportService;
    }

    public static function getEntityFqcn(): string
    {
        return OvertimeEntry::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        $currentMonth  = function ($monthNames) {
            $request = $this->getContext()->getRequest();
            $filters = $request->query->all('filters') ?? [];
            return $monthNames[($filters['incidentDateMonth'] ?? date('n'))];
        };

        $currentYear   = function () {
            $request = $this->getContext()->getRequest();
            $filters = $request->query->all('filters') ?? [];
            return $filters['incidentDateYear'] ?? date('Y');
        };

        $monthNames = [
            0 => 'Januari',
            1 => 'Januar',
            2 => 'Februar',
            3 => 'März',
            4 => 'April',
            5 => 'Mai',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'August',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Dezember',
        ];
        $int = function () use ($currentMonth, $monthNames, $currentYear) {
        return $currentMonth($monthNames) . " " . $currentYear();
    };


        $buildMonthUrl = function (int $direction) {
            $request = $this->getContext()->getRequest();

            // 1. Aktuelle Filter holen (oder Standard: aktueller Monat/Jahr)
            $filters = $request->query->all('filters') ?? [];
            $currentMonth = (int) ($filters['incidentDateMonth'] ?? date('n'));
            $currentYear = (int) ($filters['incidentDateYear'] ?? date('Y'));

            // 2. Datum berechnen (DateTimeImmutable macht die Jahreswechsel automatisch richtig!)
            $date = new \DateTimeImmutable(sprintf('%04d-%02d-01', $currentYear, $currentMonth));
            $newDate = $date->modify(($direction > 0 ? '+' : '') . $direction . ' month');

            // 3. Neue Filter-Werte setzen
            if ($direction == 0 and
                array_key_exists('incidentDateMonth', $filters) and
                array_key_exists('incidentDateYear', $filters)) {
                unset($filters['incidentDateMonth'], $filters['incidentDateYear']);
            } else {
                $filters['incidentDateMonth'] = (int) $newDate->format('n');
                $filters['incidentDateYear'] = (int) $newDate->format('Y');
            }

            // 4. Query-String neu aufbauen (behält ALLE anderen Filter wie 'teacher' bei!)
            $queryParams = $request->query->all();
            $queryParams['filters'] = $filters;

            $newQueryString = http_build_query($queryParams);

            // 5. Vollständige URL zurückgeben
            return $request->getSchemeAndHttpHost() . $request->getBaseUrl() . $request->getPathInfo() . '?' . $newQueryString;
        };

        $dateActionGroup = ActionGroup::new("dateControl");

        $prevYearAction = Action::new('prevYear', "", 'ti ti-chevrons-left')
            ->linkToUrl(function () use ($buildMonthUrl) {
                return $buildMonthUrl(-12);
            })
            ->createAsGlobalAction();

        $prevMonthAction = Action::new('prevMonth', "", 'ti ti-chevron-left')
            ->linkToUrl(function () use ($buildMonthUrl) {
                return $buildMonthUrl(-1);
            })
            ->createAsGlobalAction();
        $currentMonthAction = Action::new('currentMonth', $int)
            ->linkToUrl(function () use ($buildMonthUrl) {
                return $buildMonthUrl(0);
            })
            ->setTemplatePath('admin/overtime_entries/today_button.html.twig')
            ->createAsGlobalAction();

        $nextMonthAction = Action::new('nextMonth', "", 'ti ti-chevron-right')
            ->linkToUrl(function () use ($buildMonthUrl) {
                return $buildMonthUrl(1);
            })
            ->createAsGlobalAction();

        $nextYearAction = Action::new('nextYear', "", 'ti ti-chevrons-right')
            ->linkToUrl(function () use ($buildMonthUrl) {
                return $buildMonthUrl(12);
            })
            ->createAsGlobalAction();

        $exportAction = Action::new('export', 'Ansicht exportieren', 'ti ti-file-type-xls')
            ->linkToCrudAction('exportExcel')
            ->setCssClass("icon-success")
            ->createAsGlobalAction();

        $teacherAction = Action::new('teacher', 'Teacher', 'fa fa-users')
            ->linkToCrudAction('teacherSelect')
            ->setTemplatePath('admin/actions/teacher_dropdown.html.twig')
            ->createAsGlobalAction();
        ;

        $dateActionGroup
            ->addHeader("Gallo")
            ->addAction($nextYearAction)
            ->addAction($nextMonthAction)
            ->addAction($currentMonthAction)
            ->addAction($prevMonthAction)
            ->addAction($prevYearAction)
            ->createAsGlobalActionGroup()
        ;


        return $actions
            // ...
            // this will forbid to create or delete entities in the backend
            ->disable(Action::SAVE_AND_ADD_ANOTHER, Action::DELETE, Action::NEW)

            ->add(Crud::PAGE_INDEX, $exportAction)
            ->add(Crud::PAGE_INDEX, $nextYearAction)
            ->add(Crud::PAGE_INDEX, $nextMonthAction)
            ->add(Crud::PAGE_INDEX, $currentMonthAction)
            ->add(Crud::PAGE_INDEX, $prevMonthAction)
            ->add(Crud::PAGE_INDEX, $prevYearAction)
            ;
    }

    public function configureResponseParameters(KeyValueStore $responseParameters): KeyValueStore
    {
        // Daten nur auf der Index-Seite laden
        if (Crud::PAGE_INDEX === $responseParameters->get('pageName')) {
            $responseParameters->set('teachers', $this->teacherRepository->findAll());
            $responseParameters->set('page_title', 'Stundenkonto');
            $filters = $this->getContext()->getRequest()->query->all('filters');
            if (!empty($filters['teacher'])) {
                $teacher = $this->teacherRepository->find($filters['teacher']['value']);
                $responseParameters->set('page_title', sprintf('Stundenkonto für %s', $teacher));
            }
        }
        if (Crud::PAGE_DETAIL === $responseParameters->get('pageName')) {
            $responseParameters->set('page_title', sprintf('Stundenkonto für %s', $responseParameters->get('name')));
        }
        $responseParameters->set('icon', 'table');

        return $responseParameters;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            // Filter nach Lehrkraft (Dropdown mit allen Lehrern)
            ->add(EntityFilter::new('teacher', 'Lehrkraft')

            )
            ->add(MonthFilter::new('incidentDateMonth', 'Monat'))
            ->add(YearFilter::new('incidentDateYear', 'Jahr'))
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('ID')->hideOnForm(),
            TextField::new('teacher.shortName', 'Lehrkraft')
                ->setSortable(true) // Sortiert jetzt perfekt A-Z nach dem Kürzel
                ->formatValue(function ($value, $entity) {
                    $teacher = $entity->getTeacher();
                    if (!$teacher) {
                        return $value;
                    }

                    // Generiert den Link zur Detailseite der Lehrkraft
                    $url = $this->container->get(AdminUrlGenerator::class)
                        ->setController(TeacherCrudController::class)
                        ->setAction(Action::DETAIL)
                        ->setEntityId($teacher->getId())
                        ->generateUrl();

                    // Rendert den Link als HTML
                    return sprintf('<a href="%s">%s</a>', $url, $value);
                })
                ->renderAsHtml(),
            DateField::new('incidentDate'),
            TextField::new('class'),
            NumberField::new('courseHour'),
            //TextField::new('subject'),
            //TextField::new('room'),
            //TextField::new('untisId'),
            TextField::new('reason'),
            TextField::new('untisComment'),
            NumberField::new('amount'),
            DateField::new('expiresAt')->formatValue(fn ($value) => $value instanceof \DateTime ? $value->format('d.m.Y') : ''),
            DateField::new('invalidatedAt')->formatValue(fn ($value) => $value instanceof \DateTime ? $value->format('d.m.Y') : ''),
            AssociationField::new('invalidatedBy')->formatValue(fn ($value) => $value ?? ''),

            //DateField::new('createdAt'),
            //AssociationField::new('createdBy'),
            TextField::new('comment')->formatValue(fn ($value) => $value ?? ''),
            BooleanField::new('malus')->formatValue(fn ($value) => $value ?? '0')->setHelp("Zur Meldung an den Administrator."),
        ];
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            // Setzt die Anzahl der Einträge pro Seite auf 50
            ->setPaginatorPageSize(100)
            ->overrideTemplates([
                'crud/index' => 'admin/overtime_entries/index.html.twig',
            ])
            ->setDefaultSort([
                'teacher.shortName' => 'ASC',
                'incidentDate' => 'ASC',
                'class' => 'DESC',
                'courseHour' => 'DESC',
            ])
            ->setHelp('index', 'Export ist für die Monatsansicht optimiert. Zeitspannen (Schuljahre) bitte über das Stundendeputat exportieren.')
            ;
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    #[AdminRoute(path: '/export-excel')]
    public function exportExcel(AdminContext $context): Response
    {
        $searchDto = $context->getSearch();
        $entityDto = $context->getEntity();

        // 1. Fields konfigurieren
        $fieldsConfig = $this->configureFields(Crud::PAGE_INDEX);
        $fields = new FieldCollection($fieldsConfig);
        $this->container->get(FieldFactory::class)->processFields($entityDto, $fields, Crud::PAGE_INDEX);

        // 2. Filters konfigurieren
        $filtersConfigDto = $context->getCrud()->getFiltersConfig();
        $filterCollection = $this->container->get(FilterFactory::class)->create($filtersConfigDto, $fields, $entityDto);

        // 3. QueryBuilder bauen
        $queryBuilder = $this->container->get(EntityRepository::class)->createQueryBuilder(
            $searchDto,
            $entityDto,
            $fields,
            $filterCollection
        );

        // 4. Pagination entfernen
        $queryBuilder->setFirstResult(0);
        $queryBuilder->setMaxResults(null);

        // 5. Abfrage ausführen
        $entities = $queryBuilder->getQuery()->getResult();

        // 6. Header definieren
        $columns = [
            ['header' => 'Lehrkraft', 'align' => 'left'],
            ['header' => 'Datum', 'align' => 'left'],
            ['header' => 'Klasse', 'align' => 'left'],
            ['header' => 'Schulstunde', 'align' => 'right'],
            //['header' => 'Fach', 'align' => 'left'],
            //['header' => 'Raum', 'align' => 'left'],
            //['header' => 'Units-ID', 'align' => 'left'],
            ['header' => 'Grund', 'align' => 'left'],
            ['header' => 'Untis-Kommentar', 'align' => 'left'],
            ['header' => 'Differenz', 'align' => 'right', 'sum' => true],
            ['header' => 'Systemkommentar', 'align' => 'left'],
            ['header' => 'ID', 'align' => 'right'],
            ['header' => 'ausgeglichen durch ID', 'align' => 'right'],
            ['header' => 'auszugleichen bis', 'align' => 'right'],
            ['header' => 'ausgeglichen am', 'align' => 'right'],
        ];

        // 7. Die Entity-Daten in das Rows-Array mappen
        $rows = [];
        $sum = 0;
        $threshold = 0;
        $soll = 0;

        $request = $this->getContext()->getRequest();
        $activeFilters = $request->query->all('filters') ?? [];
        $isTeacherActive = array_key_exists('teacher', $activeFilters);
        $isMonthActive   = array_key_exists('incidentDateMonth', $activeFilters);
        $isYearActive    = array_key_exists('incidentDateYear', $activeFilters);


        $showCarryover = $isMonthActive && $isYearActive;
        $teacherShortname = null;

        if (empty($entities)) {
            $this->addFlash('warning', 'Keine Einträge für den Zeitraum gefunden.');
            $referer = $request->headers->get('referer');

            if ($referer) {
                return $this->redirect($referer);
            }
        }
        $sum = 0;
        foreach ($entities as $entity) {
            /** @var OvertimeEntry $entity */

            if ($entity->getComment() === 'CARRYOVER' && !$showCarryover) {
                continue;
            }

            $rows[] = [
                $entity->getTeacherShortname(),
                $entity->getIncidentDate() ? $entity->getIncidentDate()->format('d.m.Y') : '',
                $entity->getClass() ?: '',
                $entity->getCourseHour() ?: '',
                //$entity->getSubject() ?? '',
                //$entity->getRoom() ? $entity->getRoom() : '',
                //$entity->getUntisId() ? $entity->getUntisId() : '',
                $entity->getReason() ?: '',
                $entity->getUntisComment() ?: '',
                $entity->getAmount() ?: '0',
                $entity->getComment() ?: '',
                $entity->getId(),
                $entity->getInvalidatedBy() ?: '',
                $entity->getExpiresAt() ? $entity->getExpiresAt()->format('d.m.Y') : '',
                $entity->getInvalidatedAt() ? $entity->getInvalidatedAt()->format('d.m.Y') : '',
            ];
            $sum += $entity->getAmount() ?: '0';
            $teacher = $entity->getTeacher();
            if ($teacher) {
                $debutat = $teacher->getLastDeputat();
                $soll = $debutat->getDebutat() - $debutat->getDiscount();
                $threshold = $debutat->getThreshold();
            }
            $teacherShortname = $entity->getTeacherShortname();
        }

        // 8. Export generieren
        $teacherName = $isTeacherActive ? $teacherShortname : "Sammelkonto";
        $forDate = "";
        if($isYearActive) {
            $forDate .= "-" . $activeFilters['incidentDateYear'];
        }
        if($isMonthActive) {
            $forDate .= "-" . $activeFilters['incidentDateMonth'];
        }

        $filename = date('ymd'). "-$teacherName$forDate-Arbeitszeitkonto" . '.xlsx';
        return $this->exportService->createXlsxResponse($columns, $rows, $filename, $sum, $threshold, $soll);
    }


    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $request = $this->getContext()->getRequest();
        $query = $request->query->all();
        $hasMonthFilter = isset($query['filters']['incidentDateMonth']);
        $hasYearFilter = isset($query['filters']['incidentDateYear']);
        if (!$hasMonthFilter && !$hasYearFilter) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->neq('entity.comment', ':carryover'),
                    $qb->expr()->isNull('entity.comment')
                )
            )->setParameter('carryover', 'CARRYOVER');
        }
        return $qb;
    }
}
