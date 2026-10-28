<?php

namespace App\Controller\Admin;

use App\Service\DocumentationRenderer;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Menu\SubMenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Exception\CommonMarkException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Twig\Error\LoaderError;
use Twig\Error\SyntaxError;

#[AdminDashboard(routePath: '/admin/help', routeName: 'admin_help')]
class HelpDashboardController extends AbstractDashboardController
{
    private string $projectDir;
    public function __construct(
        private readonly RequestStack $requestStack,
        private DocumentationRenderer $docRenderer
    ) {
    }

    private function getDocsPath(): string
    {
        return $this->projectDir . '/docs';
    }

    /**
     * @throws CommonMarkException
     */
    #[Route('/admin/help/{seite?index}', name: 'admin_help_index')]
    public function index(): Response
    {
        $this->projectDir = $this->getParameter('kernel.project_dir');
        $page = $this->requestStack->getCurrentRequest()->query->get('page', 'index');

        try {
            $htmlContent = $this->docRenderer->render($this->getFileName("/docs/$page"), [

            ]);
            return $this->render('admin/help/index.html.twig', [
                'currentPage' => $page,
                'content'     => $htmlContent,
            ]);
        }
        catch (CommonMarkException|LoaderError|SyntaxError $e) {

        }
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('<div class="d-flex align-items-center gap-2"><svg class="text-primary" height="18" width="18">
  <use xlink:href="#app-icon"></use>
</svg><span class="fw-bold tk-text-primary">Hilfe-Center</span></div><div class="small text-muted">LehrZeitManagement</div>')
            ->setFaviconPath('logo.svg')
            ->setTranslationDomain('help_center')
            ;
    }

    public function configureAssets(): Assets
    {
        return Assets::new()
            ->addAssetMapperEntry('admin')
            ;
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToRoute('Zurück zum LZM', 'ti ti-home', 'admin_dashboard');
        yield MenuItem::section('Dokumentation');

        $query = $this->requestStack->getCurrentRequest()->query->get('page', 'index');

        // Starteintrag / Index
        $item = MenuItem::linkToRoute(
            'index',
            'ti ti-file',
            'admin_help',
            ['page' => 'index']
        );
        if ($query == 'index') {
            $item->setCssClass('parent-active');
        }
        yield $item;

        // Dynamische MenuItems aus docs-Struktur
        foreach ($this->getDocsStructure() as $dir => $subDir) {
            if (is_array($subDir)) {
                $subItems = [];
                foreach ($subDir as $file) {
                    $subItem = MenuItem::linkToRoute(
                        "$dir/$file",
                        'ti ti-file',
                        'admin_help',
                    )->setQueryParameter('page', "$dir/$file");
                    if ($query == "$dir/$file") {
                        $subItem->setCssClass('parent-active');
                    }
                    $subItems[] = $subItem;
                }
                yield MenuItem::subMenu(
                    $dir,
                    'ti ti-folder-open'
                )->setSubItems($subItems)->keepOpen();
            }
        }
    }

    private function getDocsStructure(): array
    {
        $structure = [];
        $docsPath = $this->getDocsPath();

        if (!is_dir($docsPath)) {
            return $structure;
        }
        $items = scandir($docsPath);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $docsPath . '/' . $item;
            if (is_dir($path)) {
                // Unterordner
                $files = array_filter(scandir($path), fn($f) =>
                    is_file($path . '/' . $f) && str_ends_with($f, '.md')
                );
                $structure[$item] = array_map(fn($f) => str_replace('.md', '', $f), $files);
            } elseif (str_ends_with($item, '.md')) {
                $structure[str_replace('.md', '', $item)] = null;
            }
        }
        ksort($structure);
        foreach ($structure as $key => $value) {
            if (is_array($value)) {
                sort($value);
            }
        }
        return $structure;
    }

    private function getFileName(string $page): string
    {
        return $page . '.md';
    }
}
