<?php

declare(strict_types=1);

namespace KnpU\OAuth2ClientBundle\Config\Client;

use GuzzleHttp\Psr7\HttpFactory;
use KnpU\OAuth2ClientBundle\Client\Provider\FacebookClient;
use Mezzio\Session\SessionInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class FacebookClientFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $c): FacebookClient
    {
        return new FacebookClient(
            $c->get('facebook.provider'),
            $c->get(HttpFactory::class),
            SessionInterface::class
        );
    }
}
