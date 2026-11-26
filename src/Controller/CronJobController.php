<?php

namespace App\Controller;

use App\Console\StreamHtmlOutput;
use DateTime;
use Exception;
use SensioLabs\AnsiConverter\AnsiToHtmlConverter;
use SensioLabs\AnsiConverter\Theme\SolarizedTheme;
use SensioLabs\AnsiConverter\Theme\SolarizedXTermTheme;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/cron', name: 'admin_cron_')]
#[IsGranted('ROLE_ADMIN')]
class CronJobController extends AbstractController
{

    #[Route('/', name: 'index')]
    public function index(): Response
    {
        return $this->render('cron/index.html.twig', [
            'page_title' => 'Cron Jobs',
            'icon' => 'function'
        ]);
    }

    /**
     * @throws Exception
     */
    #[Route('/do/test', name: 'test', methods: ['GET','POST'])]
    public function test(KernelInterface $kernel): Response
    {
        $application = new Application($kernel);
        $application->setAutoExit(false);
        $input = new ArrayInput([
            'command' => 'app:gpu:import',
        ]);
        $output = new BufferedOutput(
            OutputInterface::VERBOSITY_NORMAL,
            false
        );
        $application->run($input, $output);
        $theme = new SolarizedXTermTheme();
        $converter = new AnsiToHtmlConverter($theme);
        $content = $output->fetch();
        return new Response($converter->convert($content));
    }

    /**
     * @throws Exception
     */
    #[Route('/truncate/{table}', name: 'truncate_table', methods: ['POST'])]
    public function truncateTable(string $table, KernelInterface $kernel): Response
    {
        $application = new Application($kernel);
        $application->setAutoExit(false);
        $input = new ArrayInput([
            'command' => 'app:table:truncate',
            'table' => $table,
        ]);
        $output = new BufferedOutput(
            OutputInterface::VERBOSITY_NORMAL,
            false
        );
        $application->run($input, $output);
        $theme = new SolarizedXTermTheme();
        $converter = new AnsiToHtmlConverter($theme);
        $content = $output->fetch();
        return new Response($converter->convert($content));
    }

    /**
     * @throws Exception
     */
    #[Route('/reports/create', name: 'reports_create', methods: ['POST'])]
    public function createReports(KernelInterface $kernel): Response
    {
        $now = new DateTime("now");
        $now->setTime(0, 0);
        $currentYear = (int)$now->format('Y');
        $currentMonth = (int)$now->format('m');
        $targetYear = $currentMonth < 8 ? $currentYear - 1 : $currentYear;
        $now->setDate($targetYear, 8, 1);


        $application = new Application($kernel);
        $application->setAutoExit(false);
        $input = new ArrayInput([
            'command' => 'app:report:daily',
            'period' => $now->format('Y-m-d'),
        ]);
        $output = new BufferedOutput(
            OutputInterface::VERBOSITY_QUIET,
            false
        );

        $application->run($input, $output);
        $content = $output->fetch();
        unset($output);
        gc_collect_cycles();
        $theme = new SolarizedXTermTheme();
        $converter = new AnsiToHtmlConverter($theme);
        return new Response($converter->convert($content));
    }

    #[Route('/cache/clear', name: 'cache_clear', methods: ['POST'])]
    public function cacheClear( KernelInterface $kernel): Response
    {
        return new StreamedResponse(function () use ($kernel) {
            $application = new Application($kernel);
            $application->setAutoExit(false);

            $input = new ArrayInput([
                'command' => 'cache:clear',
            ]);

            $output = new StreamHtmlOutput();

            $application->run($input, $output);

        }, 200, [
            'Content-Type'  => 'text/html; charset=UTF-8',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

}
