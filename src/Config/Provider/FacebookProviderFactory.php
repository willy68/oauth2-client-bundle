<?php

declare(strict_types=1);

/*
OAuth2 Client Bundle
Copyright (c) KnpUniversity <http://knpuniversity.com/>

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
*/

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
