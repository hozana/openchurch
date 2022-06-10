<?php

namespace App\Listener;

use App\Entity\AccessToken;
use FOS\OAuthServerBundle\Security\Authentication\Token\OAuthToken;
use FOS\OAuthServerBundle\Storage\OAuthStorage;
use Gedmo\Loggable\LoggableListener;
use Stof\DoctrineExtensionsBundle\EventListener\LoggerListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ApiLoggerListener extends LoggerListener
{
    private AuthorizationCheckerInterface $authorizationChecker;
    private LoggableListener $loggableListener;
    private OAuthStorage $oAuthStorage;
    private TokenStorageInterface $tokenStorage;

    public function __construct(
        LoggableListener $loggableListener,
        TokenStorageInterface $tokenStorage = null,
        AuthorizationCheckerInterface $authorizationChecker = null,
        OAuthStorage $oAuthStorage = null
    ) {
        $this->loggableListener = $loggableListener;
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
        $this->oAuthStorage = $oAuthStorage;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }

        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            return;
        }

        if ($this->authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            $this->loggableListener->setUsername($token);
        }

        if ($token instanceof AnonymousToken) {
            return;
        }

        if (null !== $this->oAuthStorage && $token instanceof OAuthToken) {
            /** @var AccessToken $accessToken */
            $accessToken = $this->oAuthStorage->getAccessToken($token->getToken());
            $user = $accessToken->getUser();
            if (null !== $user) {
                $username = $user->getUsername();
                $this->loggableListener->setUsername($username);
            }
        }
    }
}
