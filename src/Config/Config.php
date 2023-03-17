<?php

declare(strict_types=1);

namespace KnpU\OAuth2ClientBundle\Config;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Config\Client\FacebookClientFactory;
use KnpU\OAuth2ClientBundle\Config\Provider\FacebookProviderFactory;
use Psr\Container\ContainerInterface;
use function DI\add;
use function DI\env;
use function DI\factory;

return [
    ClientRegistry::class => function (ContainerInterface $c) {
        return new ClientRegistry($c, $c->get('psr.oauth2.clients'));
    },
    'facebook.options' => add([
            'clientId' => env('OAUTH_FACEBOOK_ID'),
            'clientSecret' => env('OAUTH_FACEBOOK_SECRET'),
            'redirectUri' => 'https://localhost:8000/connect/facebook/check',
            'redirectParams' => [],
            'graphApiVersion' => 'v2.12',
        ]
    ),
    'facebook.provider' => factory(FacebookProviderFactory::class),
    'psr.oauth2.clients' => add([
        'facebook' => factory(FacebookClientFactory::class),
    ]),
];