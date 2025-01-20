<?php

declare(strict_types=1);

namespace App\Dashboard\Infrastructure\Symfony\Controller;

use App\Core\Infrastructure\Redis\RedisClient;
use App\FieldHolder\Community\Domain\Repository\CommunityRepositoryInterface;
use App\FieldHolder\Place\Domain\Repository\PlaceRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    public function __construct(
        private readonly CommunityRepositoryInterface $communityRepository,
        private readonly PlaceRepositoryInterface $placeRepository,
        private readonly RedisClient $redisClient,
    ) {
    }

    #[Route('/dashboard', name: 'dashboard')]
    public function index(): Response
    {
        $diocesesCount = $this->communityRepository->withType('diocese')->count();
        $parishesCount = $this->communityRepository->withType('parish')->count();
        $churchesCount = $this->placeRepository->count();

        $dioceseData = $this->redisClient->getHash('diocese');
        $parishData = $this->redisClient->getHash('parish');
        $churchData = $this->redisClient->getHash('church');

        // Rendu du template Twig
        return $this->render('@dashboard/index.html.twig', [
            'input' => [
                'diocese' => [
                    'count' => $diocesesCount,
                    'status' => $dioceseData['status'] ?? 'undefined',
                    'progress' => key_exists('currentBatch', $dioceseData) ? ceil(($dioceseData['currentBatch'] / $dioceseData['batchCount']) * 100) : 'undefined',
                    'startDate' => key_exists('startDate', $dioceseData) ? (new \DateTime($dioceseData['startDate']))->format('Y-m-d H:i:s') : 'undefined',
                    'endDate' => key_exists('endDate', $dioceseData) ? (new \DateTime($dioceseData['endDate']))->format('Y-m-d H:i:s') : 'undefined',
                ],
                'parish' => [
                    'count' => $parishesCount,
                    'status' => $parishData['status'] ?? 'undefined',
                    'progress' => key_exists('currentBatch', $parishData) ? ceil(($parishData['currentBatch'] / $parishData['batchCount']) * 100) : 'undefined',
                    'startDate' => key_exists('startDate', $parishData) ? (new \DateTime($parishData['startDate']))->format('Y-m-d H:i:s') : 'undefined',
                    'endDate' => key_exists('endDate', $parishData) ? (new \DateTime($parishData['endDate']))->format('Y-m-d H:i:s') : 'undefined',
                ],
                'church' => [
                    'count' => $churchesCount,
                    'status' => $churchData['status'] ?? 'undefined',
                    'progress' => key_exists('currentBatch', $churchData) ? ceil(($churchData['currentBatch'] / $churchData['batchCount']) * 100) : 'undefined',
                    'startDate' => key_exists('startDate', $churchData) ? (new \DateTime($churchData['startDate']))->format('Y-m-d H:i:s') : 'undefined',
                    'endDate' => key_exists('endDate', $churchData) ? (new \DateTime($churchData['endDate']))->format('Y-m-d H:i:s') : 'undefined',
                ],
            ],
        ]);
    }

    #[Route('/dashboard/{type}', name: 'dashboard_detail', requirements: ['type' => 'diocese|parish|church'])]
    public function diocese(Request $request): Response
    {
        $type = $request->get('type');

        $result = [];
        $dioceseData = $this->redisClient->getHash($type);
        $batchSize = $dioceseData['batchSize'] ?? 100;
        $batchCount = $dioceseData['batchCount'] ?? 0;

        for ($i = 0; $i < $batchCount; ++$i) {
            $key = "$type".'_'.($i * $batchSize).'-'.(($i + 1) * $batchSize);
            $keyData = $this->redisClient->getHash($key);

            $result[$key]['status'] = $keyData['status'] ?? 'undefined';
            $result[$key]['successCount'] = $keyData['successCount'] ?? 'undefined';
            $result[$key]['failureCount'] = $keyData['failureCount'] ?? 'undefined';
            $result[$key]['updatedAt'] = $keyData['updatedAt'] ?? 'undefined';
        }

        return $this->render('@dashboard/detail.html.twig', ['input' => $result]);
    }
}
