<?php

namespace App\Controller;

use App\Entity\Church;
use Doctrine\ORM\EntityManagerInterface;

class ChurchHistory
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function __invoke(Church $data)
    {
        $church = $this->entityManager->find('App\Entity\Church', $data->getId());
        $logRepo = $this->entityManager->getRepository('Gedmo\Loggable\Entity\LogEntry');
        $logs = $logRepo->getLogEntries($church);

        return $logs;
    }
}
