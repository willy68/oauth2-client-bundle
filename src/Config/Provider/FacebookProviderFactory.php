<?php

declare(strict_types=1);

namespace KnpU\OAuth2ClientBundle\Config\Provider;

use League\OAuth2\Client\Provider\Facebook;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class FacebookProviderFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $c): Facebook
    {
        $options = [];
        if ($c->has('facebook.options')) {
            $options = $c->get('facebook.options');
        }
        return new Facebook($options);
    }
}
