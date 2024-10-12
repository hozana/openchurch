<?php

namespace App\Controller\Api;

use App\Entity\Agent;
use App\Entity\Community;
use App\Entity\Place;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

class ObjectController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('/createObject', methods: ['POST'])]
    public function createObject()
    {
        $place = new Place();
        $community = new Community();
        $agent = new Agent();
        $agent->name = 'Rosario';
        $agent->apiKey = 'letmein';
        $this->em->persist($place);
        $this->em->persist($community);
        $this->em->persist($agent);
        $this->em->flush();

        return $this->json([]);
    }
}
