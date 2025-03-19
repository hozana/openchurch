<?php

namespace App\Tests\Helper;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class AcceptanceTestHelper extends ApiTestCase
{
    protected Client $client;
    protected EntityManagerInterface $em;
    public static Request $lastRequest;

    protected function setUp(): void
    {
        parent::setUp();
        parent::$alwaysBootKernel = false;

        $this->client = static::createClient();
        $this->em = static::getContainer()->get('doctrine.orm.entity_manager');
    }

    protected function tearDown(): void
    {
        $this->em->clear();
        $this->em->getConnection()->close();
        $this->em->close();

        parent::tearDown();
    }

    /**
     * @param array<string, mixed>  $body
     * @param array<string, string> $headers
     */
    protected function post(string $endpoint, ?string $apikey = null, ?array $body = null, array $headers = []): Response
    {
        return $this->request(Request::METHOD_POST, $endpoint, $apikey, $body, $headers);
    }

    /**
     * @param array<string, mixed>  $body
     * @param array<string, string> $headers
     */
    protected function put(string $endpoint, ?string $apikey = null, ?array $body = null, array $headers = []): Response
    {
        return $this->request(Request::METHOD_PUT, $endpoint, $apikey, $body, $headers);
    }

    /**
     * @param array<string, mixed>  $body
     * @param array<string, string> $headers
     */
    protected function patch(string $endpoint, ?string $apikey = null, ?array $body = null, array $headers = []): Response
    {
        return $this->request(Request::METHOD_PATCH, $endpoint, $apikey, $body, $headers);
    }

    /**
     * @param array<string, mixed> $headers
     * @param array<string, mixed> $querystring
     */
    protected function get(string $endpoint, ?string $apikey = null, array $headers = [], array $querystring = []): Response
    {
        if (count($querystring)) {
            if (str_contains($endpoint, '?')) {
                throw new InvalidArgumentException("$endpoint already contains a querystring, don't use both \$querystring argument and a hardcoded one!");
            }
            $endpoint .= '?'.http_build_query($querystring);
        }

        return $this->request(Request::METHOD_GET, $endpoint, $apikey, null, $headers);
    }

    /**
     * @param array<string, mixed>  $body
     * @param array<string, string> $headers
     */
    protected function delete(string $endpoint, ?string $apikey = null, $body = null, array $headers = []): Response
    {
        return $this->request(Request::METHOD_DELETE, $endpoint, $apikey, $body, $headers);
    }

    /**
     * @param array<string, mixed>  $body
     * @param array<string, string> $headers
     */
    public function request(string $method, string $endpoint, ?string $apikey, ?array $body, array $headers = []): Response
    {
        $apiHost = $this->getParameter('host_api');
        $endpoint = ltrim($endpoint, '/');
        $url = "$apiHost/$endpoint";
        $content = null !== $body ? json_encode($body, JSON_THROW_ON_ERROR) : '';

        $server = [
            'CONTENT_TYPE' => 'application/json',
        ];
        if (null !== $apikey) {
            $headers['Authorization'] = "Bearer $apikey";
        }
        foreach ($headers as $key => $value) {
            $server["HTTP_$key"] = $value;
        }

        $this->client->getKernelBrowser()->request($method, $url, [], [], $server, $content);
        static::$lastRequest = $this->client->getKernelBrowser()->getRequest();

        return $this->client->getKernelBrowser()->getResponse();
    }

    /**
     * Asserts that a status code matches expected one.
     * On error, the status text will be displayed alongside with its code.
     */
    protected static function assertStatusCode(int $expected, Response $response, ?string $messagePrefix = null): void
    {
        $actual = $response->getStatusCode();
        $expectedStatusText = Response::$statusTexts[$expected];
        $actualStatusText = Response::$statusTexts[$actual];
        $message = "Failed asserting that status code $actual ($actualStatusText) matches expected $expected ($expectedStatusText)";

        // Include response to ease debugging
        if ('application/json' === $response->headers->get('Content-Type')) {
            $message .= ': '.$response->getContent();
        }

        if (null !== $messagePrefix) {
            $message = "$messagePrefix $message";
        }

        self::assertEquals($expectedStatusText, $actualStatusText, $message);
    }

    /**
     * @return array<mixed>|null
     */
    protected static function assertResponse(
        Response $response, int $expectedStatusCode = Response::HTTP_OK,
    ): ?array {
        self::assertStatusCode($expectedStatusCode, $response);

        // Ensure that the request response is valid JSON
        $content = $response->getContent();
        self::assertJson($content, 'response body must be valid JSON');
        $decodedContent = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        return $decodedContent;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected static function assertErrorResponse(Response $response, int $expectedStatusCode, ?string $expectedError): ?array
    {
        $decodedResponse = self::assertResponse($response, $expectedStatusCode);

        self::assertSame($expectedError, $decodedResponse['detail']);

        return $decodedResponse;
    }

    protected function getParameter(string $parameter): mixed
    {
        return static::getContainer()->getParameter($parameter);
    }

    protected function cleanTable(string $entityClassname): void
    {
        $tableName = $this->em
            ->getClassMetadata($entityClassname)
            ->getTableName();

        $this->cleanTableByName($tableName);
    }

    protected function cleanTableByName(string $tableName): void
    {
        $connection = $this->em->getConnection();
        // Execute individual statements. Executing all of them at once won't throw any error (on missing table for instance)
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        $connection->executeStatement("DELETE FROM `$tableName`");
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
    }

    /**
     * Runs a command and returns its output.
     */
    protected function runCommand(string $command): string
    {
        $bufferedOutput = new BufferedOutput();
        $returnCode = $this->getApplication()->run(new StringInput($command), $bufferedOutput);

        $output = $bufferedOutput->fetch();

        $this->assertSame(0, $returnCode, "Command $command exited with a non-zero return code. $output");

        return $output;
    }

    protected function getApplication(): Application
    {
        $application = new Application(self::getContainer()->get('kernel'));
        $application->setAutoExit(false);

        return $application;
    }
}
