<?php

namespace App\Controller;

use App\Entity\Churches;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomeController extends AbstractController
{
    public function index()
    {
        $nbChurches = $this->getDoctrine()->getRepository(Churches::class)->count([]);

        return $this->render('home/index.html.twig', [
            'nbChurches' => $nbChurches,
        ]);
    }
}
