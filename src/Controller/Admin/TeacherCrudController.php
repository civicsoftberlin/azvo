<?php

namespace App\Controller\Admin;

use App\Entity\Teacher;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;

class TeacherCrudController extends AbstractCrudController
{

    public function __construct(private readonly AdminUrlGenerator $urlGenerator)
    {
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

        return $actions
            ->add(Crud::PAGE_INDEX, $showOvertimes)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)

            ->add(Crud::PAGE_DETAIL, $showOvertimes)
            ->remove(Crud::PAGE_INDEX, Action::EDIT)
            ->add(Crud::PAGE_EDIT, Action::SAVE_AND_ADD_ANOTHER)
            ;
    }

    /*
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            TextField::new('title'),
            TextEditorField::new('description'),
        ];
    }
    */
}
