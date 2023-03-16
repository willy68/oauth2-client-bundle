<?php

/*
 * OAuth2 Client Bundle
 * Copyright (c) KnpUniversity <http://knpuniversity.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KnpU\OAuth2ClientBundle\Tests\Client;

use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\ServerRequest;
use KnpU\OAuth2ClientBundle\Client\OAuth2Client;
use KnpU\OAuth2ClientBundle\Exception\InvalidStateException;
use KnpU\OAuth2ClientBundle\Exception\MissingAuthorizationCodeException;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\FacebookUser;
use League\OAuth2\Client\Token\AccessToken;
use Mezzio\Session\Session;
use Mezzio\Session\SessionInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class OAuth2ClientTest extends TestCase
{
    private $serverRequest;
    private $session;
    private $provider;
    private HttpFactory $httpFactory;

    public function setup(): void
    {
        $this->provider = $this->createMock(AbstractProvider::class);
        $this->session = new Session([],'1');
        $this->serverRequest = $this->createMock(ServerRequest::class);
        $this->httpFactory = new HttpFactory();
    }


    public function testRedirectWithState()
    {
        $this->provider->method('getAuthorizationUrl')
            ->with(['scope' => ['scope1', 'scope2']])
            ->willReturn('https://coolOAuthServer.com/authorize');
        $this->provider->method('getState')
            ->willReturn('SOME_RANDOM_STATE');
        $this->serverRequest->method('getAttribute')
            ->with(SessionInterface::class)
            ->willReturn($this->session);

        $client = new OAuth2Client(
            $this->provider,
            $this->httpFactory
        );


        $response = $client->redirect($this->serverRequest ,['scope1', 'scope2']);
        $this->assertInstanceOf(
            \Psr\Http\Message\ResponseInterface::class,
            $response
        );
        $this->assertEquals(
            'https://coolOAuthServer.com/authorize',
            $response->getHeaderLine('Location')
        );
        $this->assertSame('SOME_RANDOM_STATE', $this->session->get(OAuth2Client::OAUTH2_SESSION_STATE_KEY));
    }

    public function testRedirectWithoutState()
    {
        $this->provider->method('getAuthorizationUrl')
            ->with([])
            ->willReturn('https://example.com');
        $this->serverRequest->method('getAttribute')
            ->with(SessionInterface::class)
            ->willReturn($this->session);

        $client = new OAuth2Client(
            $this->provider,
            $this->httpFactory
        );
        $client->setAsStateless();

        $response = $client->redirect($this->serverRequest);
        // don't need other checks - the fact that it didn't fail
        // by asking for the request and session is enough
        $this->assertInstanceOf(
            ResponseInterface::class,
            $response
        );
    }

    public function testRedirectWithOptions()
    {
        $this->provider->method('getAuthorizationUrl')
            ->with([
                'scope' => ['scopeA'],
                'optionA' => 'FOO',
            ])
            ->willReturn('https://example.com');
        $this->serverRequest->method('getAttribute')
            ->with(SessionInterface::class)
            ->willReturn($this->session);

        $client = new OAuth2Client(
            $this->provider,
            $this->httpFactory
        );
        $client->setAsStateless();

        $response = $client->redirect(
            $this->serverRequest,
            ['scopeA'],
            ['optionA' => 'FOO']
        );
        // don't need other checks - the assertion above when
        // mocking getAuthorizationUrl is enough
        $this->assertInstanceOf(
            \Psr\Http\Message\ResponseInterface::class,
            $response
        );
    }

    public function testGetAccessToken()
    {
        $this->serverRequest
            ->method('getQueryParams')
            ->willReturn(['state' => 'THE_STATE', 'code' => 'CODE_ABC']);
        $this->serverRequest->method('getAttribute')
            ->with(SessionInterface::class)
            ->willReturn($this->session);

        $this->session->set(OAuth2Client::OAUTH2_SESSION_STATE_KEY, 'THE_STATE');

        $expectedToken = new AccessToken(['access_token' => 'foo']);
        $this->provider->method('getAccessToken')
            ->with('authorization_code', ['code' => 'CODE_ABC'])
            ->willReturn($expectedToken);

        $client = new OAuth2Client(
            $this->provider,
            $this->httpFactory
        );
        $this->assertSame($expectedToken, $client->getAccessToken($this->serverRequest));
    }

    public function testGetAccessTokenWithOptions()
    {
        $this->serverRequest
            ->method('getQueryParams')
            ->willReturn(['state' => 'THE_STATE', 'code' => 'CODE_ABC']);
        $this->serverRequest->method('getAttribute')
            ->with(SessionInterface::class)
            ->willReturn($this->session);

        $this->session->set(OAuth2Client::OAUTH2_SESSION_STATE_KEY, 'THE_STATE');

        $expectedToken = new AccessToken(['access_token' => 'foo']);
        $this->provider->method('getAccessToken')
            ->with('authorization_code', ['code' => 'CODE_ABC', 'redirectUri' => 'https://some.url'])
            ->willReturn($expectedToken);

        $client = new OAuth2Client(
            $this->provider,
            $this->httpFactory
        );
        $actualToken = $client->getAccessToken($this->serverRequest, ['redirectUri' => 'https://some.url']);
        $this->assertSame($expectedToken, $actualToken);
    }

    public function testGetAccessTokenFromPOST()
    {
        $this->serverRequest
            ->method('getQueryParams')
            ->willReturn(['state' => 'THE_STATE', 'code' => 'CODE_ABC']);
        $this->serverRequest->method('getAttribute')
            ->with(SessionInterface::class)
            ->willReturn($this->session);

        $expectedToken = new AccessToken(['access_token' => 'foo']);
        $this->provider->method('getAccessToken')
            ->with('authorization_code', ['code' => 'CODE_ABC'])
            ->willReturn($expectedToken);

        $client = new OAuth2Client(
            $this->provider,
            $this->httpFactory
        );
        $client->setAsStateless();
        $this->assertSame($expectedToken, $client->getAccessToken($this->serverRequest));
    }

    public function testRefreshAccessToken()
    {
        $existingToken = new AccessToken([
            'access_token' => 'existing',
            'refresh_token' => 'TOKEN_ABC',
        ]);

        $expectedToken = new AccessToken(['access_token' => 'new_one']);
        $this->provider->method('getAccessToken')
            ->with('refresh_token', ['refresh_token' => 'TOKEN_ABC'])
            ->willReturn($expectedToken);

        $client = new OAuth2Client(
            $this->provider,
            $this->httpFactory
        );
        $actualToken = $client->refreshAccessToken($existingToken->getRefreshToken());
        $this->assertSame($expectedToken, $actualToken);
    }

    public function testRefreshAccessTokenWithOptions()
    {
        $existingToken = new AccessToken([
            'access_token' => 'existing',
            'refresh_token' => 'TOKEN_ABC',
        ]);

        $expectedToken = new AccessToken(['access_token' => 'new_one']);
        $this->provider->method('getAccessToken')
            ->with('refresh_token', ['refresh_token' => 'TOKEN_ABC', 'redirect_uri' => 'https://some.url'])
            ->willReturn($expectedToken);

        $client = new OAuth2Client(
            $this->provider,
            $this->httpFactory
        );
        $actualToken = $client->refreshAccessToken($existingToken->getRefreshToken(), ['redirect_uri' => 'https://some.url']);
        $this->assertSame($expectedToken, $actualToken);
    }

    public function testGetAccessTokenThrowsInvalidStateException()
    {
        $this->serverRequest
            ->method('getQueryParams')
            ->willReturn(['state' => 'ACTUAL_STATE', 'code' => 'CODE_ABC']);
        $this->serverRequest->method('getAttribute')
            ->with(SessionInterface::class)
            ->willReturn($this->session);
        $this->expectException(InvalidStateException::class);
        $this->session->set(OAuth2Client::OAUTH2_SESSION_STATE_KEY, 'OTHER_STATE');

        $client = new OAuth2Client(
            $this->provider,
            $this->httpFactory
        );
        $client->getAccessToken($this->serverRequest);
    }

    public function testGetAccessTokenThrowsMissingAuthCodeException()
    {
        $this->serverRequest
            ->method('getQueryParams')
            ->willReturn(['state' => 'ACTUAL_STATE']);
        $this->serverRequest->method('getAttribute')
            ->with(SessionInterface::class)
            ->willReturn($this->session);
        $this->expectException(MissingAuthorizationCodeException::class);
        $this->session->set(OAuth2Client::OAUTH2_SESSION_STATE_KEY, 'ACTUAL_STATE');

        // don't set a code query parameter
        $client = new OAuth2Client(
            $this->provider,
            $this->httpFactory
        );
        $client->getAccessToken($this->serverRequest);
    }

    public function testFetchUser()
    {
        $this->serverRequest
            ->method('getQueryParams')
            ->willReturn(['state' => 'THE_STATE', 'code' => 'CODE_ABC']);
        $this->serverRequest->method('getAttribute')
            ->with(SessionInterface::class)
            ->willReturn($this->session);

        $expectedToken = new AccessToken(['access_token' => 'expected']);
        $this->provider->method('getAccessToken')
            ->with('authorization_code', ['code' => 'CODE_ABC'])
            ->willReturn($expectedToken);

        $client = new OAuth2Client(
            $this->provider,
            $this->httpFactory
        );

        $client->setAsStateless();
        $actualToken = $client->getAccessToken($this->serverRequest);

        $resourceOwner = new FacebookUser([
            'id' => '1',
            'name' => 'testUser',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@doe.com',
        ]);

        $this->provider->method('getResourceOwner')
            ->with($actualToken)
            ->willReturn($resourceOwner);
        $user = $client->fetchUser($this->serverRequest);

        $this->assertInstanceOf(FacebookUser::class, $user);
        $this->assertEquals('testUser', $user->getName());
    }

    public function testShouldReturnProviderObject()
    {
        $testClient = new OAuth2Client(
            $this->provider,
            $this->httpFactory
        );

        $result = $testClient->getOAuth2Provider();

        $this->assertInstanceOf(AbstractProvider::class, $result);
    }

    public function testShouldThrowExceptionOnRedirectIfNoSessionAndNotRunningStateless()
    {
        $this->serverRequest->method('getAttribute')
            ->with(SessionInterface::class)
            ->willReturn(null);
        $testClient = new OAuth2Client(
            $this->provider,
            $this->httpFactory
        );

        $this->expectException(\LogicException::class);
        $testClient->redirect($this->serverRequest);
    }

    public function testShouldThrowExceptionOnGetAccessTokenIfNoSessionAndNotRunningStateless()
    {
        $this->serverRequest->method('getAttribute')
            ->with(SessionInterface::class)
            ->willReturn(null);
        $testClient = new OAuth2Client(
            $this->provider,
            $this->httpFactory
        );

        $this->expectException(\LogicException::class);
        $testClient->getAccessToken($this->serverRequest);
    }
}
