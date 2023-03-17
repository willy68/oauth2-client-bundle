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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Get a new AccessToken from a refresh token., passing options to the underlying provider
 * @method AccessToken refreshAccessToken(string $refreshToken, array $options = [])
 */
interface OAuth2ClientInterface
{
    /**
     * Call this to avoid using and checking "state".
     */
    public function setAsStateless();

    /**
     * Creates a RedirectResponse that will send the user to the
     * OAuth2 server (e.g. send them to Facebook).
     *
     * @param ServerRequestInterface $request
     * @param array $scopes The scopes you want (leave empty to use default)
     * @param array $options Extra options to pass to the Provider's getAuthorizationUrl()
     *                       method. For example, <code>scope</code> is a common option.
     *                       Generally, these become query parameters when redirecting.
     * @return ResponseInterface
     */
    public function redirect(ServerRequestInterface $request ,array $scopes, array $options): ResponseInterface;

    /**
     * Call this after the user is redirected back to get the access token.
     *
     * @param ServerRequestInterface $request
     * @param array $options Additional options that should be passed to the getAccessToken() of the underlying provider
     *
     * @return AccessToken
     *
     * @throws InvalidStateException
     * @throws MissingAuthorizationCodeException
     * @throws IdentityProviderException If token cannot be fetched
     */
    public function getAccessToken(ServerRequestInterface $request ,array $options = []): AccessToken;

    /**
     * Returns the "User" information (called a resource owner).
     *
     * @param AccessToken $accessToken
     * @return ResourceOwnerInterface
     */
    public function fetchUserFromToken(AccessToken $accessToken);

    /**
     * Shortcut to fetch the access token and user all at once.
     *
     * Only use this if you don't need the access token, but only
     * need the user.
     *
     * @param ServerRequestInterface $request
     * @return ResourceOwnerInterface
     */
    public function fetchUser(ServerRequestInterface $request);

    /**
     * Returns the underlying OAuth2 provider.
     *
     * @return AbstractProvider
     */
    public function getOAuth2Provider(): AbstractProvider;
}
