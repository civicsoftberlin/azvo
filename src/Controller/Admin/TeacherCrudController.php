<?php

namespace App\Controller\Admin;

use App\Entity\Teacher;
use App\Service\ExcelExportService;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TeacherCrudController extends AbstractCrudController
{
    private ExcelExportService $exportService;

    public function __construct(private readonly AdminUrlGenerator $urlGenerator, ExcelExportService $exportService)
    {
        $this->exportService = $exportService;
    }

    public static function getEntityFqcn(): string
    {
        return Teacher::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setDefaultSort(['shortName' => 'ASC'])
            ->overrideTemplate('crud/detail', 'admin/teacher/show.html.twig')
            ->setPageTitle(Crud::PAGE_DETAIL, fn (Teacher $teacher) => 'Lehrkraft: ' . ($teacher->getShortName() ?? 'Unbekannt'))
            ->showEntityActionsInlined()
            ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $showOvertimes = Action::new('showOvertimes', 'OvertimeEntry', 'ti ti-clock-hour-4')
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
            ->add(Crud::PAGE_INDEX, $showOvertimes)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_DETAIL, $exportExcelAction)

            //->add(Crud::PAGE_DETAIL, $showOvertimes)
            ->remove(Crud::PAGE_INDEX, Action::EDIT)
            ->add(Crud::PAGE_EDIT, Action::SAVE_AND_ADD_ANOTHER)
            ;
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('shortName'),
            AssociationField::new('overtimeEntries')
                ->setTemplatePath('admin/teacher/overtime_entries.html.twig')
                ->onlyOnDetail(),
            AssociationField::new('teacherDebutats')
                ->setLabel("Schuljahr")
                ->setTemplatePath('admin/teacher/debutats.html.twig')
                ->onlyOnDetail(),
        ];
    }

    #[AdminRoute(path: '/export-excel', name: 'export_excel')]
    public function exportExcel(AdminContext $context, Request $request): StreamedResponse
    {
        $columns = [
            ['header' => 'Lehrkraft', 'align' => 'left'],
            ['header' => 'Datum', 'align' => 'left'],
            ['header' => 'Klasse', 'align' => 'left'],
            ['header' => 'Schulstunde', 'align' => 'right'],
            //['header' => 'Fach', 'align' => 'left'],
            //['header' => 'Raum', 'align' => 'left'],
            //['header' => 'Units-ID', 'align' => 'left'],
            ['header' => 'Grund', 'align' => 'left'],
            ['header' => 'Differenz', 'align' => 'right', 'sum' => true],
            ['header' => 'Systemkommentar', 'align' => 'left'],
            ['header' => 'Freizeitausgleich bis', 'align' => 'right'],

            //['header' => 'ausgeglichen durch', 'align' => 'right'],
        ];

        return $this->exportService->createXlsxResponse($columns, [], "test.xlsx");
    }

}
