<?php

/*
 * OAuth2 Client Bundle
 * Copyright (c) KnpUniversity <http://knpuniversity.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KnpU\OAuth2ClientBundle\Client;

use KnpU\OAuth2ClientBundle\Exception\InvalidStateException;
use KnpU\OAuth2ClientBundle\Exception\MissingAuthorizationCodeException;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;
use LogicException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class OAuth2Client implements OAuth2ClientInterface
{
    public const OAUTH2_SESSION_STATE_KEY = 'knpu.oauth2_client_state';
    private AbstractProvider $provider;
    private bool $isStateless = false;
    private ResponseFactoryInterface $responseFactory;
    private string $sessionClass;

    /**
     * OAuth2Client constructor.
     */
    public function __construct(
        AbstractProvider $provider,
        ResponseFactoryInterface $responseFactory,
        string $sessionClass
    ) {
        $this->provider = $provider;
        $this->responseFactory = $responseFactory;
        $this->sessionClass = $sessionClass;
    }

    /**
     * Call this to avoid using and checking "state".
     */
    public function setAsStateless()
    {
        $this->isStateless = true;
    }

    /**
     * Creates a RedirectResponse that will send the user to the
     * OAuth2 server (e.g. send them to Facebook).
     *
     * @param ServerRequestInterface $request
     * @param array $scopes The scopes you want (leave empty to use default)
     * @param array $options Extra options to pass to the Provider's getAuthorizationUrl()
     *                       method. For example, <code>scope</code> is a common option.
     *                       Generally, these become query parameters when redirecting.
     *
     * @return ResponseInterface
     */
    public function redirect(
        ServerRequestInterface $request,
        array $scopes = [],
        array $options = []
    ): ResponseInterface {
        if (!empty($scopes)) {
            $options['scope'] = $scopes;
        }

        $url = $this->provider->getAuthorizationUrl($options);

        // set the state (unless we're stateless)
        if (!$this->isStateless()) {
            $this->getSession($request)->set(
                self::OAUTH2_SESSION_STATE_KEY,
                $this->provider->getState()
            );
        }

        return ($this->responseFactory->createResponse())
            ->withAddedHeader("Location", $url);
    }

    /**
     * Call this after the user is redirected back to get the access token.
     *
     * @param ServerRequestInterface $request
     * @param array $options Additional options that should be passed to the getAccessToken() of the underlying provider
     *
     * @return AccessTokenInterface
     *
     * @throws IdentityProviderException If token cannot be fetched
     */
    public function getAccessToken(ServerRequestInterface $request, array $options = []): AccessTokenInterface
    {
        if (!$this->isStateless()) {
            $expectedState = $this->getSession($request)->get(self::OAUTH2_SESSION_STATE_KEY);
            $actualState = ($request->getQueryParams()['state']) ?? null;
            if (!$actualState || ($actualState !== $expectedState)) {
                throw new InvalidStateException('Invalid state');
            }
        }

        $code = ($request->getQueryParams()['code']) ?? null;

        if (!$code) {
            throw new MissingAuthorizationCodeException(
                'No "code" parameter was found (usually this is a query parameter)!'
            );
        }

        return $this->provider->getAccessToken(
            'authorization_code',
            array_merge(['code' => $code], $options)
        );
    }

    /**
     * Get a new AccessToken from a refresh token.
     *
     * @param string $refreshToken
     * @param array $options Additional options that should be passed to the getAccessToken() of the underlying provider
     *
     * @return AccessToken|AccessTokenInterface
     *
     * @throws IdentityProviderException If token cannot be fetched
     */
    public function refreshAccessToken(
        string $refreshToken,
        array $options = []
    ): AccessToken|AccessTokenInterface {
        return $this->provider->getAccessToken(
            'refresh_token',
            array_merge(['refresh_token' => $refreshToken], $options)
        );
    }

    /**
     * Returns the "User" information (called a resource owner).
     *
     * @param AccessToken $accessToken
     * @return ResourceOwnerInterface
     */
    public function fetchUserFromToken(AccessToken $accessToken)
    {
        return $this->provider->getResourceOwner($accessToken);
    }

    /**
     * Shortcut to fetch the access token and user all at once.
     *
     * Only use this if you don't need the access token, but only
     * need the user.
     *
     * @param ServerRequestInterface $request
     * @return ResourceOwnerInterface
     * @throws IdentityProviderException
     */
    public function fetchUser(ServerRequestInterface $request)
    {
        /** @var AccessToken $token */
        $token = $this->getAccessToken($request);

        return $this->fetchUserFromToken($token);
    }

    /**
     * Returns the underlying OAuth2 provider.
     *
     * @return AbstractProvider
     */
    public function getOAuth2Provider(): AbstractProvider
    {
        return $this->provider;
    }

    protected function isStateless(): bool
    {
        return $this->isStateless;
    }

    /**
     * Attempt session in attribute
     * @param ServerRequestInterface $request
     * @return object
     */
    private function getSession(ServerRequestInterface $request): object
    {
        if (!($session = $request->getAttribute($this->sessionClass))) {
            throw new LogicException(
                'In order to use "state", you must have a session.' .
                ' Set the OAuth2Client to stateless to avoid state'
            );
        }

        return $session;
    }
}
