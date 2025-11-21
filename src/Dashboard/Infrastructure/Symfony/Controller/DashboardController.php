<?php

declare(strict_types=1);

namespace App\Dashboard\Infrastructure\Symfony\Controller;

use App\Core\Infrastructure\Redis\RedisClient;
use App\FieldHolder\Community\Domain\Repository\CommunityRepositoryInterface;
use App\FieldHolder\Place\Domain\Repository\PlaceRepositoryInterface;
use DateTime;
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
        $types = [
            'diocese' => ['repository' => $this->communityRepository, 'type' => 'diocese'],
            'parish' => ['repository' => $this->communityRepository, 'type' => 'parish'],
            'church' => ['repository' => $this->placeRepository, 'type' => null],
        ];

        $input = [];

        foreach ($types as $key => $config) {
            $count = ($config['type'] !== null)
                ? $config['repository']->withType($config['type'])->count()
                : $config['repository']->count();

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

    /**
     * @param array<mixed> $data
     */
    private function calculateProgress(array $data): float
    {
        if (array_key_exists('currentBatch', $data)) {
            return ceil($data['currentBatch'] / $data['batchCount']) * 100;
        }

        return 0;
    }
}
