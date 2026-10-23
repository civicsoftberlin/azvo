<?php

namespace App\Controller\Admin;

use App\Entity\EntryType;
use App\Entity\UntisImport;
use App\Repository\TeacherRepository;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

#[AdminDashboard(routePath: '/admin', routeName: 'admin_dashboard')]
class DashboardController extends AbstractDashboardController
{
    private TeacherRepository $teacherRepository;

    public function __construct(TeacherRepository $repository)
    {
        $this->teacherRepository = $repository;
    }

    public function index(): Response
    {
        $teachers = $this->teacherRepository->findAll();
        return $this->render('admin/dashboard.html.twig',[
            'icon' => "clock",
            'teachers' => $teachers,
        ]);
    }

    public function configureUserMenu(UserInterface $user): UserMenu
    {
        $userMenu = parent::configureUserMenu($user)->setGravatarEmail($user->getUserIdentifier());

        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            $subMenu = MenuItem::section('Benutzerimitation');
            $users = [];


            foreach ($users as $targetUser) {
                if ($targetUser->getUserIdentifier() !== $user->getUserIdentifier() and count($targetUser->getRoles()) > 1 and !in_array('ROLE_ADMIN', $targetUser->getRoles())) {
                    $userMenu->addMenuItems([
                        MenuItem::linkToUrl(
                            $targetUser->getFullname(),
                            'fa fa-exchange',
                            '/admin?_switch_user=' . $targetUser->getUserIdentifier()
                        )
                    ]);
                }
            }
            //$userMenu->addMenuItems([$subMenu]);
        }

        return $userMenu;
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('<div class="d-flex align-items-center gap-2"><img src="/logo.svg" height="18px" alt="Logo"><span class="fw-bold tk-text-primary">LZM</span></div><div class="small text-muted">LehrZeitManagement</div>')
            ->setFaviconPath('logo.svg')
            ;
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'ti ti-home');

        yield MenuItem::section('Import', 'ti ti-clock-hour-4');
        yield MenuItem::linkTo(UntisImportCrudController::class, 'UntisImport', 'ti ti-file-type-csv')->setAction('new');

        yield MenuItem::section('Accounts', 'ti ti-clock-hour-4');
        yield MenuItem::linkTo(TeacherCrudController::class, 'Teachers', 'ti ti-school');
        yield MenuItem::linkTo(OvertimeEntryCrudController::class, 'OvertimeEntry', 'ti ti-calendar');
        yield MenuItem::linkTo(TeacherDebutatCrudController::class, 'Debutat', 'ti ti-calendar');

        //yield MenuItem::section('Settings', 'ti ti-settings');

        //yield MenuItem::linkTo(EntryTypeCrudController::class, 'Entry Types', 'ti ti-tag');
        //yield MenuItem::linkTo(UserCrudController::class, 'Users', 'ti ti-user');
        //yield MenuItem::linkTo(AzVoCrudController::class, 'AzVo', 'ti ti-section-sign');

    }

    public function configureAssets(): Assets
    {
        return Assets::new()
            ->addAssetMapperEntry('admin')
            ;
    }
}
