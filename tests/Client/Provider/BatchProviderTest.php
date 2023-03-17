<?php

/*
 * OAuth2 Client Bundle
 * Copyright (c) KnpUniversity <http://knpuniversity.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KnpU\OAuth2ClientBundle\Tests\Client\Provider;

use GuzzleHttp\Psr7\HttpFactory;
use KnpU\OAuth2ClientBundle\Client\OAuth2Client;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use Mezzio\Session\Session;
use Mezzio\Session\SessionInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class BatchProviderTest extends TestCase
{
    public function testProviders()
    {
        // This is basically just validating that the clients are sane/implementing OAuth2Client

        $mockAccessToken = $this->getMockBuilder(AccessToken::class)->disableOriginalConstructor()->getMock();
        $mockProvider = $this->getMockProvider($mockAccessToken);
        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $session = new Session([],'1');
        $mockRequest
            ->method('getQueryParams')
            ->willReturn(['state' => 'THE_STATE', 'code' => 'CODE_ABC']);
        $mockRequest->method('getAttribute')
            ->with(SessionInterface::class)
            ->willReturn($session);

        $clients = scandir(__DIR__ . "/../../../src/Client/Provider");
        foreach($clients as $client) {
            if(!str_ends_with($client, ".php")) { continue; }

            $client = sprintf("KnpU\OAuth2ClientBundle\Client\Provider\%s", explode(".", $client)[0]);
            $testClient = new $client($mockProvider, new HttpFactory());
            $testClient->setAsStateless();
            $this->assertTrue(is_subclass_of($testClient, OAuth2Client::class));

            $this->assertInstanceOf(ResourceOwnerInterface::class, $testClient->fetchUserFromToken($mockAccessToken));
            $this->assertInstanceOf(ResourceOwnerInterface::class, $testClient->fetchUser($mockRequest));
        }
    }

    private function getMockProvider($mockAccessToken)
    {
        $mockProvider = $this->getMockBuilder(AbstractProvider::class)->getMock();
        $mockProvider->method("getResourceOwner")->willReturn($this->getMockBuilder(ResourceOwnerInterface::class)->getMock());
        $mockProvider->method("getAccessToken")->willReturn($mockAccessToken);
        return $mockProvider;
    }

}
