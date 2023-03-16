<?php

declare(strict_types=1);

namespace KnpU\OAuth2ClientBundle\Config\Provider;

use GuzzleHttp\Psr7\ServerRequest;
use League\OAuth2\Client\Provider\Facebook;
use Mezzio\Router\FastRouteRouter;
use Mezzio\Router\RouterInterface;
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
            /** @var RouterInterface $router */
            $router = $c->get(FastRouteRouter::class);
            $redirectUri = $router->generateUri($options['redirectUri']);
            $request = new ServerRequest('GET', $redirectUri);
            $url = sprintf(
                '%s://%s%s%s',
                $request->getUri()->getScheme(),
                $request->getUri()->getHost(),
                $request->getUri()->getPort() ? ':' . $request->getUri()->getPort() : '',
                $redirectUri
            );
            $options['redirectUri'] = $url;
        }
        return new Facebook($options);
    }

    protected  function getUrl(ContainerInterface $c, string $redirectUri): string
    {
        /** @var RouterInterface $router */
        $router = $c->get(FastRouteRouter::class);

        $scheme = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';

        $redirectUri = $router->generateUri($redirectUri);
        $port = '';
        $host = '';
        $hasPort = false;
        if (isset($_SERVER['HTTP_HOST'])) {
            [$host, $port] = self::extractHostAndPortFromAuthority($_SERVER['HTTP_HOST']);

            if ($port !== null) {
                $hasPort = true;
            }
        } elseif (isset($_SERVER['SERVER_NAME'])) {
            $host = $_SERVER['SERVER_NAME'];
        } elseif (isset($_SERVER['SERVER_ADDR'])) {
            $host = $_SERVER['SERVER_ADDR'];
        }

        if (!$hasPort && isset($_SERVER['SERVER_PORT'])) {
            $port = $_SERVER['SERVER_PORT'];
        }
        return $scheme . '://' . $host . ':' . $port . $redirectUri;
    }
    private static function extractHostAndPortFromAuthority(string $authority): array
    {
        $uri = 'https://' . $authority;
        $parts = parse_url($uri);
        if (false === $parts) {
            return [null, null];
        }

        $host = $parts['host'] ?? null;
        $port = $parts['port'] ?? null;

        return [$host, $port];
    }
}