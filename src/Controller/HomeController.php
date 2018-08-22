<?php

namespace App\Controller;

use App\Entity\Church;
use App\Entity\Commune;
use App\Entity\Departement;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomeController extends AbstractController
{
    public function index()
    {
        $nbChurch = $this->getDoctrine()->getRepository(Church::class)->count([]);
        $nbCommune = $this->getDoctrine()->getRepository(Commune::class)->count([]);
        $nbDepartement = $this->getDoctrine()->getRepository(Departement::class)->count([]);

        return $this->render('home/index.html.twig', [
            'nbChurch' => $nbChurch,
            'nbCommune' => $nbCommune,
            'nbDepartement' => $nbDepartement,
        ]);
    }
}
