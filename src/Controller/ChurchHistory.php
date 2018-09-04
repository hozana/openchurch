<?php

namespace App\Controller;

use App\Entity\Church;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

class ChurchHistory
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function __invoke(Church $data)
    {
        $repo = $this->entityManager->getRepository('Gedmo\Loggable\Entity\LogEntry');
        $church = $repo->find('App\Entity\Church', $data->getId());
        $logs = $repo->getLogEntries($church);

        return $logs;
    }
}
