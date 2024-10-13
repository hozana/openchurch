<?php

namespace App\EventListener\Response;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use function Symfony\Component\String\s;

class ExceptionListener
{
    public function __construct(
        private readonly string $env,
        private readonly LoggerInterface $logger
    ) {
    }

    /** @noinspection PhpUnused */
    public function onKernelException(ExceptionEvent $event): ?Response
    {
        $exception = $event->getThrowable();
        $response = new JsonResponse();

        if ($exception instanceof AccessDeniedException) { // Common SF exception
            $response
                ->setStatusCode($exception->getCode())
                ->setData([
                    'success' => false,
                    'error' => 'access-denied',
                ]);
        } elseif ($exception instanceof HttpExceptionInterface) { // HTTP exception
            $response->setStatusCode($exception->getStatusCode());
            $response->headers->replace($exception->getHeaders());

            $error = Response::$statusTexts[$exception->getStatusCode()] ?? null;
            // quick & dirty "slugify"
            if ($error !== null) {
                $error = s($error)->lower()->replace(' ', '-')->toString();
            }

            $response->setData([
                'success' => false,
                'error' => $error,
                'message' => $exception->getMessage(),
            ]);
        } else { // Unhandled exception
            // Write to logs
            $this->logger->error($exception->getMessage(), [$exception->getTrace()]);

            // Display pretty Symfony errors on dev environment
            if ($this->env === 'dev') {
                return $event->getResponse();
            }

            $response
                ->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                ->setData([
                    'success' => false,
                ]);
        }

        $event->setResponse($response);

        return $response;
    }
}
