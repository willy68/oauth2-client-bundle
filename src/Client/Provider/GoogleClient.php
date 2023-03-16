<?php

/*
 * OAuth2 Client Bundle
 * Copyright (c) KnpUniversity <http://knpuniversity.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KnpU\OAuth2ClientBundle\Client\Provider;

use KnpU\OAuth2ClientBundle\Client\OAuth2Client;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\GoogleUser;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\ServerRequestInterface;

class GoogleClient extends OAuth2Client
{
    /**
     * @return GoogleUser|ResourceOwnerInterface
     */
    public function fetchUserFromToken(AccessToken $accessToken): GoogleUser|ResourceOwnerInterface
    {
        return parent::fetchUserFromToken($accessToken);
    }

    /**
     * @return GoogleUser|ResourceOwnerInterface
     * @throws IdentityProviderException
     */
    public function fetchUser(ServerRequestInterface $request): GoogleUser|ResourceOwnerInterface
    {
        return parent::fetchUser($request);
    }
}
