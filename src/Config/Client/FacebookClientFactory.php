<?php

declare(strict_types=1);

/*
OAuth2 Client Bundle
Copyright (c) KnpUniversity <http://knpuniversity.com/>

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
*/

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
