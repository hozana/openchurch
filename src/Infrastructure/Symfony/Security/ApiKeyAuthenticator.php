<?php

namespace App\Infrastructure\Symfony\Security;

use App\Agent\Infrastructure\Doctrine\DoctrineAgentRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use function Symfony\Component\String\s;

class ApiKeyAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly DoctrineAgentRepository $agentRepo,
    ) {
    }

    /**
     * Called on every request to decide if this authenticator should be
     * used for the request. Returning `false` will cause this authenticator
     * to be skipped.
     */
    public function supports(Request $request): ?bool
    {
        return $request->headers->has('authorization');
    }

    public function authenticate(Request $request): Passport
    {
        $authorizationHeader = $request->headers->get('authorization');
        // "Bearer {apiKey}" -> "{apiKey}"
        $apiKey = s($authorizationHeader)->trimPrefix('Bearer ')->toString() ?: null;
        if (null === $apiKey) {
            // The token header was empty, authentication fails with HTTP Status
            // Code 401 "Unauthorized"
            throw new CustomUserMessageAuthenticationException('No API key provided');
        }

        // implement your own logic to get the user identifier from `$apiToken`
        // e.g. by looking up a user in the database using its API key
        $userIdentifier = $this->agentRepo->findAgentNameByApiKey($apiKey);
        if ($userIdentifier === null) {
            throw new CustomUserMessageAuthenticationException('Invalid API key provided');
        }

        return new SelfValidatingPassport(new UserBadge($userIdentifier));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // on success, let the request continue
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $data = [
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData())
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }
}
