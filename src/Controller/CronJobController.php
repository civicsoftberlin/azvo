<?php

namespace App\Controller;

use SensioLabs\AnsiConverter\AnsiToHtmlConverter;
use SensioLabs\AnsiConverter\Theme\SolarizedTheme;
use SensioLabs\AnsiConverter\Theme\SolarizedXTermTheme;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
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
     * @throws \Exception
     */
    #[Route('/do/test', name: 'test', methods: ['GET','POST'])]
    public function test(KernelInterface $kernel): Response
    {
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput([
            'command' => 'app:gpu:import',
        ]);

        // You can use NullOutput() if you don't need the output
        $output = new BufferedOutput(
            OutputInterface::VERBOSITY_NORMAL,
            true
        );
        $application->run($input, $output);

        // return the output, don't use if you used NullOutput()

        $theme = new SolarizedXTermTheme();
        $converter = new AnsiToHtmlConverter($theme);
        $content = $output->fetch();

        // return new Response(""), if you used NullOutput()
        return new Response($converter->convert($content));
    }

}
