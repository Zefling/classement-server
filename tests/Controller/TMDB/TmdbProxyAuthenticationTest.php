<?php

namespace App\Tests\Controller;

use App\Controller\TmdbProxyController;
use App\Security\ApiKeyAuthenticator;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Tests that verify the proxy requires authentication via X-AUTH-TOKEN header.
 * These tests verify that the controller implements TokenAuthenticatedController
 * interface which triggers authentication via ApiKeyAuthenticator.
 */
class TmdbProxyAuthenticationTest extends KernelTestCase
{
    /**
     * Test that TmdbProxyController implements TokenAuthenticatedController interface
     *
     * This verifies that the controller is configured to require authentication.
     * The actual authentication is handled by ApiKeyAuthenticator which checks
     * for X-AUTH-TOKEN header.
     */
    public function testControllerImplementsTokenAuthenticatedInterface(): void
    {
        $controller = new TmdbProxyController(
            $this->createMock(HttpClientInterface::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(CacheInterface::class)
        );

        // Verify controller implements TokenAuthenticatedController
        $this->assertInstanceOf(
            \App\Controller\Common\TokenAuthenticatedController::class,
            $controller,
            'TmdbProxyController must implement TokenAuthenticatedController to require authentication'
        );
    }

    /**
     * Test that ApiKeyAuthenticator supports requests with X-AUTH-TOKEN header
     */
    public function testAuthenticatorSupportsRequestsWithAuthToken(): void
    {
        $kernel = self::bootKernel();
        $doctrine = $kernel->getContainer()->get('doctrine');
        $authenticator = new ApiKeyAuthenticator($doctrine);

        // Request with X-AUTH-TOKEN header should be supported
        $requestWithToken = Request::create('/api/tmdb/search/movie', 'GET');
        $requestWithToken->headers->set('X-AUTH-TOKEN', 'some-token');

        $this->assertTrue(
            $authenticator->supports($requestWithToken),
            'Authenticator should support requests with X-AUTH-TOKEN header'
        );
    }

    /**
     * Test that ApiKeyAuthenticator does not support requests without X-AUTH-TOKEN header
     */
    public function testAuthenticatorDoesNotSupportRequestsWithoutAuthToken(): void
    {
        $kernel = self::bootKernel();
        $doctrine = $kernel->getContainer()->get('doctrine');
        $authenticator = new ApiKeyAuthenticator($doctrine);

        // Request without X-AUTH-TOKEN header should not be supported
        $requestWithoutToken = Request::create('/api/tmdb/search/movie', 'GET');

        $this->assertFalse(
            $authenticator->supports($requestWithoutToken),
            'Authenticator should not support requests without X-AUTH-TOKEN header'
        );
    }

    /**
     * Test that ApiKeyAuthenticator returns 401 response on authentication failure
     */
    public function testAuthenticatorReturns401OnAuthenticationFailure(): void
    {
        $kernel = self::bootKernel();
        $doctrine = $kernel->getContainer()->get('doctrine');
        $authenticator = new ApiKeyAuthenticator($doctrine);

        $request = Request::create('/api/tmdb/search/movie', 'GET');
        $exception = new AuthenticationException('Invalid credentials');

        $response = $authenticator->onAuthenticationFailure($request, $exception);

        // Verify 401 status code
        $this->assertEquals(
            Response::HTTP_UNAUTHORIZED,
            $response->getStatusCode(),
            'Authentication failure should return HTTP 401'
        );

        // Verify error response structure
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errorCode', $responseData);
        $this->assertArrayHasKey('errorMessage', $responseData);
        $this->assertArrayHasKey('code', $responseData);
        $this->assertArrayHasKey('status', $responseData);
        $this->assertEquals(1030, $responseData['errorCode']); // CodeError::INVALID_TOKEN
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $responseData['code']);
        $this->assertEquals('KO', $responseData['status']);
    }

    /**
     * Test that ApiKeyAuthenticator start() method returns 401 when authentication is needed
     *
     * This is called when authentication is required but not provided.
     */
    public function testAuthenticatorStartReturns401(): void
    {
        $kernel = self::bootKernel();
        $doctrine = $kernel->getContainer()->get('doctrine');
        $authenticator = new ApiKeyAuthenticator($doctrine);

        $request = Request::create('/api/tmdb/search/movie', 'GET');

        $response = $authenticator->start($request);

        // Verify 401 status code
        $this->assertEquals(
            Response::HTTP_UNAUTHORIZED,
            $response->getStatusCode(),
            'Start method should return HTTP 401 when authentication is needed'
        );

        // Verify error response structure
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals(1030, $responseData['errorCode']); // CodeError::INVALID_TOKEN
        $this->assertEquals('Invalid credentials.', $responseData['errorMessage']);
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $responseData['code']);
        $this->assertEquals('KO', $responseData['status']);
    }

    /**
     * Test that controller processes request when authentication succeeds
     *
     * This test verifies that when authentication is successful (not tested here,
     * as that's the responsibility of ApiKeyAuthenticator), the controller
     * processes the request and forwards it to TMDb API.
     */
    public function testControllerProcessesRequestAfterAuthentication(): void
    {
        // Create mock successful TMDb response
        $mockResponseData = ['results' => []];
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(200);
        $mockResponse->method('toArray')->willReturn($mockResponseData);

        // Create mock HTTP client
        $mockClient = $this->createMock(HttpClientInterface::class);
        $mockClient->expects($this->once())
            ->method('request')
            ->willReturn($mockResponse);

        // Create mock logger
        $mockLogger = $this->createMock(LoggerInterface::class);

        // Create mock logger
        $mockCache = $this->createMock(CacheInterface::class);

        // Create controller
        $controller = new TmdbProxyController($mockClient, $mockLogger, $mockCache);

        // Create request (authentication would have already passed at this point)
        $request = Request::create('/api/tmdb/search/movie', 'GET', ['query' => 'Matrix']);

        // Execute
        $response = $controller->proxy('search/movie', $request);

        // Verify request is processed (not rejected)
        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals($mockResponseData, $responseData);
    }
}
