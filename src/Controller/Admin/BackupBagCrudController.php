<?php

namespace App\Controller\Admin;

use App\Entity\BackupBag;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\SortOrder;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;

class BackupBagCrudController extends AbstractCrudController
{
    private AdminUrlGenerator $adminUrlGenerator;

    public function __construct(
        AdminUrlGenerator $adminUrlGenerator
    )
    {
        $this->adminUrlGenerator = $adminUrlGenerator;
    }

    public static function getEntityFqcn(): string
    {
        return BackupBag::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        $restoreBackupAction = Action::new('restore', 'Restore Backup', 'ti ti-restore')
            ->linkToCrudAction('restoreBackupAction');
        // Disable Actions
        $actions->remove(Crud::PAGE_INDEX, Action::NEW);
        $actions->remove(Crud::PAGE_INDEX, Action::EDIT);
        // Add Actions
        $actions->add(Crud::PAGE_INDEX, $restoreBackupAction);
        return $actions;
    }

    public function configureCrud(Crud $crud): Crud
    {
        $crud->showEntityActionsInlined();
        $crud->setDefaultSort(['createdAt' => SortOrder::DESC]);
        return $crud;
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            DateTimeField::new('createdAt'),
            IntegerField::new('backupItems.count')->setLabel('Elements'),
            DateField::new('startDate'),
            DateField::new('endDate'),
        ];
    }

    public function configureResponseParameters(KeyValueStore $responseParameters): KeyValueStore
    {
        $responseParameters->set('icon', 'ti ti-restore');
        $responseParameters->set('page_title', 'Backup wiederherstellen');
        return $responseParameters;
    }

    #[AdminRoute(path: '/{id}/restore', name: 'restore-backup')]
    public function restoreBackupAction(BackupBag $backupBag): RedirectResponse
    {
        $this->addFlash("success", "Backup restored (simulation).");
        $redirectUrl = $this->adminUrlGenerator
            ->setDashboard(DashboardController::class)
            ->setController(BackupBagCrudController::class)
            ->setAction(Action::INDEX)
            ->generateUrl();
        return $this->redirect($redirectUrl);
    }

}
