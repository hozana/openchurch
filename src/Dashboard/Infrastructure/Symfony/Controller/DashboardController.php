<?php

declare(strict_types=1);

namespace App\Dashboard\Infrastructure\Symfony\Controller;

use App\Core\Infrastructure\Redis\RedisClient;
use App\FieldHolder\Community\Domain\Repository\CommunityRepositoryInterface;
use App\FieldHolder\Place\Domain\Repository\PlaceRepositoryInterface;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
        $counts = [
            'diocese' => $this->communityRepository->withType('diocese')->count(),
            'parish' => $this->communityRepository->withType('parish')->count(),
            'church' => $this->placeRepository->count(),
        ];

        $input = [];

        foreach ($counts as $key => $count) {
            $redisData = $this->redisClient->getHash($key);

            $input[$key] = [
                'count' => $count,
                'status' => $redisData['status'] ?? 'undefined',
                'progress' => $this->calculateProgress($redisData),
                'startDate' => array_key_exists('startDate', $redisData) ? new DateTime($redisData['startDate'])->format('Y-m-d H:i:s') : 'undefined',
                'endDate' => array_key_exists('endDate', $redisData) ? new DateTime($redisData['endDate'])->format('Y-m-d H:i:s') : 'undefined',
            ];
        }

        return $this->render('@dashboard/index.html.twig', ['input' => $input]);
    }

    #[Route('/dashboard/{type}', name: 'dashboard_detail', requirements: ['type' => 'diocese|parish|church'])]
    public function diocese(string $type): Response
    {
        $result = [];
        $dioceseData = $this->redisClient->getHash($type);
        $batchSize = (int) ($dioceseData['batchSize'] ?? 100);
        $batchCount = (int) ($dioceseData['batchCount'] ?? 0);

        for ($i = 0; $i < $batchCount; ++$i) {
            $key = "{$type}".'_'.($i * $batchSize).'-'.(($i + 1) * $batchSize);
            $keyData = $this->redisClient->getHash($key);

            $result[$key]['status'] = $keyData['status'] ?? 'undefined';
            $result[$key]['successCount'] = $keyData['successCount'] ?? 'undefined';
            $result[$key]['failureCount'] = $keyData['failureCount'] ?? 'undefined';
            $result[$key]['updatedAt'] = $keyData['updatedAt'] ?? 'undefined';
        }

        return $this->render('@dashboard/detail.html.twig', ['input' => $result]);
    }

    /**
     * @param array<string, string> $data
     */
    private function calculateProgress(array $data): float
    {
        $batchCount = (int) ($data['batchCount'] ?? 0);
        if (array_key_exists('currentBatch', $data) && 0 !== $batchCount) {
            return round((int) $data['currentBatch'] / $batchCount, 2) * 100;
        }

        return 0;
    }
}
