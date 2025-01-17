<?php

declare(strict_types=1);

namespace App\Dashboard\Infrastructure\Symfony\Controller;

use App\FieldHolder\Community\Domain\Repository\CommunityRepositoryInterface;
use App\FieldHolder\Place\Domain\Repository\PlaceRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    public function __construct(
        private readonly CommunityRepositoryInterface $communityRepository,
        private readonly PlaceRepositoryInterface $placeRepository,
    ) {
    }

    #[Route('/dashboard', name: 'dashboard')]
    public function index(): Response
    {
        $diocesesCount = $this->communityRepository->withType('diocese')->count();
        $parishesCount = $this->communityRepository->withType('parish')->count();
        $churchesCount = $this->placeRepository->count();

        // Rendu du template Twig
        return $this->render('@dashboard/index.html.twig', [
            'diocesesCount' => $diocesesCount,
            'parishesCount' => $parishesCount,
            'churchesCount' => $churchesCount,
        ]);
    }
}
