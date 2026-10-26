<?php

namespace App\Command;

use App\Entity\TeacherDebutat;
use App\Repository\TeacherDebutatRepository;
use App\Repository\TeacherRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:deputat:copy',
    description: 'Erstellt neues Jahresdeputat für alle Lehrkräfte.',
)]
class DebutatCopyCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private TeacherRepository $teacherRepository;
    private TeacherDebutatRepository $teacherDebutatRepository;

    public function __construct(EntityManagerInterface $entityManager, TeacherRepository $teacherRepository, TeacherDebutatRepository $teacherDebutatRepository)
    {
        $this->entityManager = $entityManager;
        $this->teacherRepository = $teacherRepository;
        $this->teacherDebutatRepository = $teacherDebutatRepository;
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $teachers = $this->teacherRepository->findAll();
            foreach ($teachers as $teacher) {
                /** @var TeacherDebutat $deputat */
                $deputat = $teacher->getLastDeputat();
                if (!$deputat) {
                    continue;
                }
                if ($deputat->isLeaves()) {
                    continue;
                }
                if (!$deputat->isAutoAppend()) {
                    continue;
                }
                // cron immer am 01.08. eines Jahres!
                $today = new \DateTime();
                if ($deputat->getInvalidFrom()->format('Y-m-d') !== $today->format('Y-m-d')) {
                    continue;
                }
                $existingEntry = $this->teacherDebutatRepository->findOneBy([
                    'teacher' => $teacher,
                    'validFrom' => $deputat->getInvalidFrom()
                ]);
                if ($existingEntry) {
                    continue;
                }
                $newDeputat = new TeacherDebutat();
                $newInvalidFrom = (clone $deputat->getInvalidFrom())->modify('+1 year');
                $newDeputat->setTeacher($deputat->getTeacher());
                $newDeputat->setDebutat($deputat->getDebutat());
                $newDeputat->setDiscount($deputat->getDiscount());
                $newDeputat->setThreshold($deputat->getThreshold());
                $newDeputat->setValidFrom($deputat->getInvalidFrom());
                $newDeputat->setInvalidFrom($newInvalidFrom);
                $newDeputat->setDiscountValidFrom($deputat->getInvalidFrom());
                $newDeputat->setAutoAppend($deputat->isAutoAppend());
                $deputat->setAutoAppend(false);
                $newDeputat->setLeaves($deputat->isLeaves());
                $this->entityManager->persist($newDeputat);
                $this->entityManager->persist($deputat);
            }
            $this->entityManager->flush();
            return Command::SUCCESS;
        } catch (\Exception $ignored) {
            return Command::FAILURE;
        }
    }
}
