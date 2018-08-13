<?php

namespace App\Listener;

use FOS\OAuthServerBundle\Storage\OAuthStorage;
use Gedmo\Loggable\LoggableListener;
use Stof\DoctrineExtensionsBundle\EventListener\LoggerListener;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ApiLoggerListener extends LoggerListener
{
    private $authorizationChecker;
    private $tokenStorage;
    private $loggableListener;
    private $OAuthStorage;

    public function __construct(LoggableListener $loggableListener,
                                TokenStorageInterface $tokenStorage = null,
                                AuthorizationCheckerInterface $authorizationChecker = null,
                                OAuthStorage $OAuthStorage = null)
    {
        $this->OAuthStorage = $OAuthStorage;
        parent::__construct($loggableListener, $tokenStorage, $authorizationChecker);
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }

        if (null === $this->tokenStorage || null === $this->authorizationChecker) {
            return;
        }

        $token = $this->tokenStorage->getToken();

        if (null !== $token && $this->authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            $this->loggableListener->setUsername($token);
        }

        $this->loggableListener->setUsername($this->OAuthStorage->getAccessToken($token->getToken())->getClient()->getUser()->getUsername());
    }
}
