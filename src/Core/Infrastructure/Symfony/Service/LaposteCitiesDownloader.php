<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Symfony\Service;

use App\Core\Domain\Search\Service\CitiesDownloaderInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Filesystem\Filesystem;

class LaposteCitiesDownloader implements CitiesDownloaderInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly Filesystem $filesystem,
        private readonly string $citiesDownloadUrl,
        private readonly string $targetPath
    ) {
    }

    public function getCities(): array {
        if (!file_exists($this->targetPath) || !is_readable($this->targetPath)) {
            $this->downloadFile();
        }

        $data = [];

        if (($handle = fopen($this->targetPath, 'r')) === false) {
            throw new \RuntimeException(sprintf('Impossible d’ouvrir le fichier CSV : %s', $this->targetPath));
        }

        if (($header = fgetcsv($handle, 0, ';')) === false) {
            fclose($handle);
            throw new \RuntimeException('Impossible de lire la ligne d’en-têtes du CSV.');
        }

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            // Ligne vide ou incomplète
            if (count($row) !== count($header)) {
                continue;
            }

            $data[] = array_combine(['name', 'postCode'], [$row[1], $row[2]]);
        }

        fclose($handle);

        return $data;
    }

    private function downloadFile(): void
    {
        $this->filesystem->mkdir(dirname($this->targetPath));

        $response = $this->httpClient->request('GET', $this->citiesDownloadUrl, [
            'timeout' => 300,
        ]);

        if (200 !== $response->getStatusCode()) {
            throw new \RuntimeException(sprintf(
                'Download failure (HTTP %d).',
                $response->getStatusCode()
            ));
        }

        // 3. Ouverture du fichier en écriture binaire
        $fileHandler = fopen($this->targetPath, 'wb');
        if (false === $fileHandler) {
            throw new \RuntimeException('Unable to create local file.');
        }

        // 4. Écriture du flux dans le fichier
        foreach ($this->httpClient->stream($response) as $chunk) {
            fwrite($fileHandler, $chunk->getContent());
        }

        fclose($fileHandler);
    }
}
