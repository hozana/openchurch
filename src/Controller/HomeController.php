<?php

namespace App\Controller;

use App\Repository\ChurchRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomeController extends AbstractController
{
    public function index(ChurchRepository $churchRepo)
    {
        return $this->render('home/index.html.twig', [
            'nbChurches' => $churchRepo->countAll(),
        ]);
    }
}
