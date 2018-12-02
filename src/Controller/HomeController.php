<?php

namespace App\Controller;

use App\Entity\Church;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomeController extends AbstractController
{
    public function index()
    {
        $nbChurches = $this->getDoctrine()->getRepository(Church::class)->count([]);

        return $this->render('home/index.html.twig', [
            'nbChurches' => $nbChurches,
        ]);
    }
}
