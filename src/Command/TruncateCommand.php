<?php

namespace App\Command;

use App\Repository\OvertimeEntryRepository;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:table:truncate',
    description: 'Erstellt täglichen Report.',
)]
class TruncateCommand extends Command
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'table',
                InputArgument::REQUIRED,
                'Tabelle, die geleert werden soll.'
            );
    }

    /**
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        try {
            $table = $input->getArgument('table');
            $this->truncateAll($table);
        } catch (Exception $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
        $io->success("Tabelle $table wurde erfolgreich geleert");
        return Command::SUCCESS;
    }

    /**
     * @throws Exception
     */
    private function truncateAll(string $table): void
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            throw new \InvalidArgumentException('Ungültiger Tabellenname: ' . $table);
        }
        $conn = $this->em->getConnection();
        $sql = "TRUNCATE TABLE $table";
        $conn->executeQuery($sql);
    }
}
