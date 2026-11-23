<?php

namespace App\Controller\Admin;

use App\Entity\EntryType;
use App\Entity\UntisImport;
use App\Repository\DailyReportRepository;
use App\Repository\DailyReportTypeRepository;
use App\Repository\OvertimeEntryRepository;
use App\Repository\SettingRepository;
use App\Repository\TeacherRepository;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\Font;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\FontFeature;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\GrayScale;
use EasyCorp\Bundle\EasyAdminBundle\Config\Theme;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use PSpell\Config;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

#[AdminDashboard(routePath: '/admin', routeName: 'admin_dashboard')]
class DashboardController extends AbstractDashboardController
{
    private TeacherRepository $teacherRepository;
    private SettingRepository $settingRepository;
    private OvertimeEntryRepository $overtimeEntryRepository;

    public function __construct(TeacherRepository $repository, SettingRepository $settingRepository, OvertimeEntryRepository $overtimeEntryRepository)
    {
        $this->teacherRepository = $repository;
        $this->settingRepository = $settingRepository;
        $this->overtimeEntryRepository = $overtimeEntryRepository;
    }

    /**
     * @throws \DateMalformedStringException
     */
    public function index(): Response
    {

        $now = new \DateTimeImmutable('first day of this month');
        $endDate = (clone $now)->modify('-1 day');
        $startDate = (clone $now)->modify('-13 months');

        $monthsDE = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mär', 4 => 'Apr',
            5 => 'Mai', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
            9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Dez'
        ];

        $array_with_ticks = [];
        $chartDecayValues = [];
        $chartCarryoverValues = [];
        $currentDate = clone $startDate;

        $statDecays = $this->overtimeEntryRepository->sumDecaysForPeriod($startDate, $endDate);
        $statCarryOvers = $this->overtimeEntryRepository->sumCarryOversForPeriod($startDate, $endDate, true);
        $realDecays = $this->overtimeEntryRepository->sumRealDecays();
        $realCarryOvers = $this->overtimeEntryRepository->sumRealCarryOvers();
        $heatMapData = []; //$this->overtimeEntryRepository->getWeeklyHourDistribution();
        $dailyReport = $this->overtimeEntryRepository->countCancellationForToday();



        $maxValue = $heatMapData ? max(array_column($heatMapData, 2)) : 0;

        $monthlyDecays = [];
        foreach ($statDecays as $dayKey => $value) {
            $monthKey = substr($dayKey, 0, 7);
            $monthlyDecays[$monthKey] = ($monthlyDecays[$monthKey] ?? 0) + abs($value);
        }

        $monthlyCarryOvers = [];
        foreach ($statCarryOvers as $dayKey => $value) {
            $monthKey = substr($dayKey, 0, 7);
            $monthlyCarryOvers[$monthKey] = ($monthlyCarryOvers[$monthKey] ?? 0) + abs($value);
        }

        while ($currentDate <= $endDate) {
            $monthKey = $currentDate->format('Y-m');
            $monthName = $monthsDE[(int)$currentDate->format('n')];
            $yearShort = $currentDate->format('y');
            $array_with_ticks[] = $monthName . ' ' . $yearShort;
            $chartDecayValues[] = $monthlyDecays[$monthKey] ?? 0;
            $chartCarryoverValues[] = $monthlyCarryOvers[$monthKey] ?? 0;
            $currentDate = $currentDate->modify('+1 month');
        }

        $teachers = $this->teacherRepository->findAll();

        return $this->render('admin/dashboard.html.twig',[
            'icon' => "clock",
            'teachers' => $teachers,
            'stat_decays' => $chartDecayValues,
            'stat_carryovers' => $chartCarryoverValues,
            'stat_ticks' => $array_with_ticks,
            'stat_real_decays' => $realDecays,
            'stat_real_carryovers' => $realCarryOvers,
            'stat_heatmap' => $heatMapData,
            'stat_heatmap_max' => $maxValue,
            'daily_report' => $dailyReport,
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
            ->setTitle('<div class="d-flex align-items-center gap-2"><svg class="text-primary" height="18" width="18">
  <use xlink:href="#app-icon"></use>
</svg><span class="fw-bold tk-text-primary">LZM</span></div><div class="small text-muted">LehrZeitManagement</div>')
            ->setFaviconPath('logo.svg')
            ->setTheme(Theme::new()
                ->primaryColor('#2171b5')
                ->grays(GrayScale::SLATE, dark: GrayScale::NEUTRAL)
                ->radius('lg')
                ->spacing('sm')
                ->font(base: Font::Inter, baseWeight: 400, features: [FontFeature::TabularNumbers])
            )
            ->renderContentMaximized()
            ;
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'ti ti-home');
        yield MenuItem::linkToRoute('Hilfe-Center', 'fa fa-question-circle', 'admin_help');

        yield MenuItem::section('Import', 'ti ti-clock-hour-4');
        yield MenuItem::linkTo(UntisImportCrudController::class, 'UntisImport', 'ti ti-file-type-csv')
            ->setAction('new');

        $setting = $this->settingRepository->findOneBy([]);
        if ($setting) {
            yield MenuItem::linkTo(SettingCrudController::class, 'Auto Import', 'ti ti-table-import')
                ->setAction('edit')->setEntityId($setting->getId());
        } else {
            yield MenuItem::linkTo(SettingCrudController::class, 'Auto Import', 'ti ti-table-import')
                ->setAction('new');
        }


        yield MenuItem::linkTo(BackupBagCrudController::class, 'Backups', 'ti ti-restore');
        //yield MenuItem::linkTo(BackupItemCrudController::class, 'BackupItems <span class="ms-1 badge badge-danger text-uppercase">in Planung</span>', 'ti ti-database-export');

        yield MenuItem::section('Accounts', 'ti ti-clock-hour-4');
        yield MenuItem::linkTo(TeacherCrudController::class, 'Teachers', 'ti ti-school');
        yield MenuItem::linkTo(OvertimeEntryCrudController::class, 'OvertimeEntry', 'ti ti-calendar');
        yield MenuItem::linkTo(TeacherDebutatCrudController::class, 'Debutat', 'ti ti-calendar');

        yield MenuItem::section('Statistik', 'ti ti-chart-bar');
        yield MenuItem::linkToRoute('Daily Report', 'ti ti-list', 'admin_report_index');
        yield MenuItem::linkTo(DailyReportTypeCrudController::class, 'Report Type', 'ti ti-list');
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
