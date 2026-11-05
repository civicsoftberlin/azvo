<?php

namespace App\Command;

use App\Repository\SettingRepository;
use App\Service\WorkingTimeImportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Security\Core\User\UserInterface;

#[AsCommand(
    name: 'app:gpu:import',
    description: 'Import gpu files',
)]
class GpuImportCommand extends Command
{

    public function __construct(
        private readonly EntityManagerInterface              $entityManager,
        private readonly SettingRepository                   $settingRepository,
        private readonly WorkingTimeImportService            $workingTimeImportService,
        #[Autowire(env: 'GPU_PATH')] private readonly string $gpuPath,
    )
    {
        // TODO: automatisch mit User 'admin' einloggen
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $importSetting = null;
        $tempFile = tempnam(sys_get_temp_dir(), 'gpu_import_');
        try {
            $importSetting = $this->settingRepository->findOneBy([]);
            if (!$importSetting) {
                throw new \Exception('Import setting not found!');
            }
            if (
                !$importSetting->isAutoImportActive() or
                $importSetting->getAutoImportUrl() == null or
                $importSetting->getAutoImportUrl() == "") {
                throw new \Exception('Import setting not active!');
            }
            $importSetting->setLastImportDate(new \DateTimeImmutable("now"));
            $importUrl = rtrim($importSetting->getAutoImportUrl(), '/');
            $importPath = ltrim($this->gpuPath, '/');
            $serverUrl = $importUrl . "/" . $importPath;
            if (copy($serverUrl, $tempFile) === false) {
                throw new \Exception("Konnte Datei nicht von $importUrl herunterladen.");
            }
            $file = new File($tempFile, true);
            $this->workingTimeImportService->importFromCsv($file, null, true, true);
            $importSetting->setLastImportMessage("Import erfolgreich");

        } catch (\Exception $exception) {
            $importSetting?->setLastImportMessage($exception->getMessage());
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
        if ($this->entityManager->isOpen()) {
            $this->entityManager->persist($importSetting);
            $this->entityManager->flush();
        }
        return Command::SUCCESS;
    }

}
