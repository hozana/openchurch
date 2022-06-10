<?php

namespace App\Controller;

use App\Repository\ChurchRepository;
use App\Repository\DioceseRepository;
use App\Repository\ParishRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class HomeController extends AbstractController
{
    public function index(ChurchRepository $churchRepo, DioceseRepository $dioceseRepo, ParishRepository $parishRepo): Response
    {
        return $this->render('home/index.html.twig', [
            'nbChurches' => $churchRepo->countAll(),
            'nbDioceses' => $dioceseRepo->countAll(),
            'nbParishes' => $parishRepo->countAll(),
        ]);
    }
}
