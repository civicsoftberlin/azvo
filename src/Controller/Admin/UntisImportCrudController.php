<?php

namespace App\Controller\Admin;

use App\Entity\UntisImport;
use App\Service\WorkingTimeImportService;
use DateMalformedStringException;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\FileUploadType;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Validator\Constraints\File;
use Vich\UploaderBundle\Form\Type\VichFileType;

class UntisImportCrudController extends AbstractCrudController
{

    public function __construct(private WorkingTimeImportService $workingTimeImportService)
    {
    }

    public static function getEntityFqcn(): string
    {
        return UntisImport::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            // ...
            // this will forbid to create or delete entities in the backend
            ->disable(Action::SAVE_AND_ADD_ANOTHER, Action::EDIT)
            ->update(Crud::PAGE_NEW, Action::SAVE_AND_RETURN, function (Action $action) {
                return $action->setLabel('Importieren');
            })
            ;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->overrideTemplates([
                'crud/new' => 'admin/pages/edit.html.twig',
            ])
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            FormField::addFieldset("Datei auswählen", "ti ti-file-type-csv"),
            TextField::new('uploadFile', 'GPUxxx.txt aus DIF-Export')
                ->setFormType(VichFileType::class)
                ->setFormTypeOptions([
                    'attr' => [
                        'accept' => '.txt,.csv'
                    ]
                ])
                ->setRequired(true)
                ->setHelp('Bereits vorhandene Buchungen werden ignoriert.'),

            FormField::addFieldset("Optionen", "ti ti-settings"),
            BooleanField::new('autoCreate')
        ];
    }

    /**
     * @throws DateMalformedStringException
     */
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $result = $this->workingTimeImportService->importFromCsv($entityInstance->getUploadFile(), $this->getUser(), $entityInstance->isAutoCreate());
        if(array_key_exists('missing', $result)) {
            $this->addFlash('warning', sprintf("Folgende Lehrkräfte fehlen im System: %s", implode(', ', $result['missing'])));
        }
        if(array_key_exists('created', $result)) {
            $this->addFlash('info', sprintf("Folgende Lehrkräfte wurden erzeugt: %s", implode(', ', $result['missing'])));
        }
        if(array_key_exists('success', $result)) {
            $this->addFlash("success", "Import erfolgreich.");
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function getRedirectResponseAfterSave(AdminContext $context, string $action): RedirectResponse
    {

        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);


        $url = $adminUrlGenerator
            ->setController(OvertimeEntryCrudController::class)
            ->setAction(Action::INDEX)
            ->generateUrl()
        ;

        return $this->redirect($url);
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
