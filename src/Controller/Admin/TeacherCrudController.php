<?php

namespace App\Controller\Admin;

use App\Entity\OvertimeEntry;
use App\Entity\Teacher;
use App\Repository\OvertimeEntryRepository;
use App\Repository\TeacherDebutatRepository;
use App\Repository\TeacherRepository;
use App\Service\ExcelExportService;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TeacherCrudController extends AbstractCrudController
{
    private ExcelExportService $exportService;
    private OvertimeEntryRepository $entryRepository;
    private TeacherDebutatRepository $debutatRepository;

    public function __construct(private readonly AdminUrlGenerator $urlGenerator,
                                ExcelExportService $exportService,
                                OvertimeEntryRepository $entryRepository,
                                TeacherDebutatRepository $teacherRepository,
    )
    {
        $this->exportService = $exportService;
        $this->entryRepository = $entryRepository;
        $this->debutatRepository = $teacherRepository;
    }

    public static function getEntityFqcn(): string
    {
        return Teacher::class;
    }

    public function configureResponseParameters(KeyValueStore $responseParameters): KeyValueStore
    {
        $responseParameters->set('icon', 'school');
        return $responseParameters;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPaginatorPageSize(50)
            ->setDefaultSort(['shortName' => 'ASC'])
            ->overrideTemplate('crud/detail', 'admin/teacher/show.html.twig')
            ->setPageTitle(Crud::PAGE_DETAIL, fn (Teacher $teacher) => 'Details für <span class="ms-1 tk-text-primary">' . ($teacher->getShortName() ?? 'Unbekannt').'</span>')
            ->showEntityActionsInlined()
            ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $showOvertimes = Action::new('showOvertimes', 'OvertimeEntry', 'ti ti-table')->setCssClass('icon-primary')
            ->linkToUrl(function (Teacher $teacher) {
                return $this->urlGenerator
                    ->setController(OvertimeEntryCrudController::class)
                    ->setAction('index')
                    ->set('filters', [
                        'teacher' => ['comparison' => '=', 'value' => $teacher->getId()],
                        //'incidentDateMonth'   =>  date('m'),
                        //'incidentDateYear'    => date('Y'),
                    ])
                    ->generateUrl();
            });

        $exportExcelAction = Action::new('export')
            ->linkToCrudAction('exportExcel')
            ->setLabel('Excel Export') // Text für den Button (falls er sichtbar sein soll)
            ->setIcon('fas fa-file-excel')
            ->addCssClass('btn btn-success')
            ->setHtmlAttributes([
                'style' => 'display:none;'
            ]);

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $showOvertimes)

            ->add(Crud::PAGE_DETAIL, $exportExcelAction)
            ->remove(Crud::PAGE_INDEX, Action::BATCH_DELETE)
            ->remove(Crud::PAGE_INDEX, Action::DELETE)
            ->remove(Crud::PAGE_DETAIL, Action::DELETE)
            ->remove(Crud::PAGE_DETAIL, Action::EDIT)

            ->update(Crud::PAGE_INDEX, Action::DETAIL, function (Action $action) {
                return $action->setLabel('Details')->setIcon(('ti ti-school'))->setCssClass('icon-primary');
            })


            //->add(Crud::PAGE_DETAIL, $showOvertimes)
            ->remove(Crud::PAGE_INDEX, Action::EDIT)
            ->add(Crud::PAGE_EDIT, Action::SAVE_AND_ADD_ANOTHER)
            ;
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),

            FormField::addFieldset("Mehr-/Minderstunden", "ti ti-clock")->onlyOnDetail(),
            TextField::new('shortName')->hideOnDetail(),
            Field::new('countCurrentMonthOvertimeEntries','')
                ->setTemplatePath('admin/teacher/current_overtime_entries.html.twig')
                ->onlyOnDetail()->setLabel(false),
            Field::new('countLastMonthOvertimeEntries','')
                ->setTemplatePath('admin/teacher/last_overtime_entries.html.twig')
                ->onlyOnDetail()->setLabel(false),

            FormField::addFieldset("Diesen Monat ablaufende Mehrstunden", "ti ti-alert-triangle")->onlyOnDetail()->setCssClass("icon-warning"),
            Field::new('countCurrentExpiringEntries','')
                ->setTemplatePath('admin/teacher/current_expiring_entries.html.twig')
                ->onlyOnDetail()->setLabel(false),

            FormField::addFieldset("erweiterter Export", "ti ti-table-export")->onlyOnDetail(),
            AssociationField::new('teacherDebutats')
                ->setLabel("")
                ->setTemplatePath('admin/teacher/debutats.html.twig')
                ->onlyOnDetail()->setLabel(false),
        ];
    }

    /**
     * @throws \DateMalformedStringException
     */
    #[AdminRoute(path: '/export-excel', name: 'export_excel')]
    public function exportExcel(AdminContext $context, Request $request): Response
    {
        $teacher = $context->getEntity()->getInstance();
        $debutatId = (int) $request->request->get('debutat');
        $deputat = $this->debutatRepository->findOneBy(['id' => $debutatId]);

        if (!$deputat) {
            throw $this->createNotFoundException($debutatId);
        }
        $validFrom = $deputat->getValidFrom();
        $invalidFrom = $deputat->getInvalidFrom();
        $entries = $this->entryRepository->createQueryBuilder('e')
            ->where('e.teacher = :teacher')
            ->andWhere('e.incidentDate >= :validFrom')
            ->andWhere('e.incidentDate < :invalidFrom')
            ->andWhere('e.comment != :carryover OR e.comment IS NULL')
            ->setParameter('teacher', $teacher)
            ->setParameter('validFrom', $validFrom->format('Y-m-d'))
            ->setParameter('invalidFrom', $invalidFrom->format('Y-m-d'))
            ->setParameter('carryover', 'CARRYOVER')
            ->orderBy('e.incidentDate', 'ASC')
            ->getQuery()
            ->getResult()
        ;
        $columns = [
            ['header' => 'Lehrkraft', 'align' => 'left'],
            ['header' => 'Datum', 'align' => 'left'],
            ['header' => 'Klasse', 'align' => 'left'],
            ['header' => 'Schulstunde', 'align' => 'right'],
            ['header' => 'Grund', 'align' => 'left'],
            ['header' => 'Untis-Kommentar', 'align' => 'left'],
            ['header' => 'Differenz', 'align' => 'right', 'sum' => true],
            ['header' => 'Systemkommentar', 'align' => 'left'],
            ['header' => 'ID', 'align' => 'left'],
            ['header' => 'ausgeglichen durch ID', 'align' => 'right'],
            ['header' => 'auszugleichen bis', 'align' => 'right'],
            ['header' => 'ausgeglichen am', 'align' => 'right'],
        ];
        $rows = [];
        $teacherShortname = "";

        if (empty($entries)) {
            $this->addFlash('warning', sprintf('Keine Einträge für den Zeitraum %s bis %s gefunden.', $validFrom->format('d.m.Y'), $invalidFrom->format('d.m.Y')));
            $referer = $request->headers->get('referer');

            if ($referer) {
                return $this->redirect($referer);
            }
        }
        foreach ($entries as $entity) {
            /** @var OvertimeEntry $entity */
            $rows[] = [
                $entity->getTeacherShortname(),
                $entity->getIncidentDate() ? $entity->getIncidentDate()->format('d.m.Y') : '',
                $entity->getClass() ?: '',
                $entity->getCourseHour() ?: '',
                $entity->getReason() ?: '',
                $entity->getUntisComment() ?: '',
                $entity->getAmount() ?: '0',
                $entity->getComment() ?: '',
                $entity->getId(),
                $entity->getInvalidatedBy() ?: '',
                $entity->getExpiresAt() ? $entity->getExpiresAt()->format('d.m.Y') : '',
                $entity->getInvalidatedAt() ? $entity->getInvalidatedAt()->format('d.m.Y') : '',
            ];
            $teacherShortname = $entity->getTeacherShortname();
        }
        $filename = date('ymd'). "-$teacherShortname-Schuljahr-" . $validFrom->format("m-Y") . "-" . $invalidFrom->modify("-1 day")->format("m-Y")  . '.xlsx';
        return $this->exportService->createXlsxResponse($columns, $rows, $filename);
    }
}
