<?php

namespace App\Tests\Controller;

use App\Controller\TmdbProxyController;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class TmdbProxyControllerTest extends KernelTestCase
{
    public function testSuccessfulSearchRequestForwarding(): void
    {
        // Create mock successful TMDb search response
        $mockResponseData = [
            'page' => 1,
            'results' => [
                [
                    'id' => 603,
                    'title' => 'The Matrix',
                    'original_title' => 'The Matrix',
                    'overview' => 'Set in the 22nd century...',
                    'poster_path' => '/f89U3ADr1oiB1s9GkdPOEpXUk5H.jpg',
                    'release_date' => '1999-03-30',
                    'vote_average' => 8.7
                ]
            ],
            'total_pages' => 1,
            'total_results' => 1
        ];

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(200);
        $mockResponse->method('toArray')->willReturn($mockResponseData);

        // Create mock HTTP client that captures the request
        $capturedPath = null;
        $capturedOptions = null;

        $mockClient = $this->createMock(HttpClientInterface::class);
        $mockClient->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                $this->callback(function ($path) use (&$capturedPath) {
                    $capturedPath = $path;
                    return true;
                }),
                $this->callback(function ($options) use (&$capturedOptions) {
                    $capturedOptions = $options;
                    return true;
                })
            )
            ->willReturn($mockResponse);

        // Create mock logger
        $mockLogger = $this->createMock(LoggerInterface::class);

        // Create mock logger
        $mockCache = $this->createMock(CacheInterface::class);

        // Create controller with mocks
        $controller = new TmdbProxyController($mockClient, $mockLogger, $mockCache);

        // Create request with query parameters
        $queryParams = [
            'query' => 'Matrix',
            'language' => 'fr-FR',
            'page' => '1',
            'include_adult' => 'false'
        ];
        $request = Request::create('/api/tmdb/search/movie', 'GET', $queryParams);

        // Execute
        $response = $controller->proxy('search/movie', $request);

        // Assert response status and body
        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals($mockResponseData, $responseData);

        // Assert query parameters were forwarded correctly
        $this->assertEquals('search/movie', $capturedPath);
        $this->assertArrayHasKey('query', $capturedOptions);
        $this->assertEquals($queryParams, $capturedOptions['query']);

        // Verify specific query parameters
        $this->assertEquals('Matrix', $capturedOptions['query']['query']);
        $this->assertEquals('fr-FR', $capturedOptions['query']['language']);
        $this->assertEquals('1', $capturedOptions['query']['page']);
        $this->assertEquals('false', $capturedOptions['query']['include_adult']);
    }

    public function testSuccessfulLanguagesRequestForwarding(): void
    {
        // Create mock successful TMDb languages response
        $mockResponseData = [
            'en-US',
            'fr-FR',
            'es-ES',
            'de-DE',
            'it-IT',
            'ja-JP',
            'ko-KR',
            'pt-BR',
            'ru-RU',
            'zh-CN'
        ];

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(200);
        $mockResponse->method('toArray')->willReturn($mockResponseData);

        // Create mock HTTP client
        $mockClient = $this->createMock(HttpClientInterface::class);
        $mockClient->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'configuration/primary_translations',
                ['query' => []]
            )
            ->willReturn($mockResponse);

        // Create mock logger
        $mockLogger = $this->createMock(LoggerInterface::class);

        // Create mock logger
        $mockCache = $this->createMock(CacheInterface::class);

        // Create controller with mocks
        $controller = new TmdbProxyController($mockClient, $mockLogger, $mockCache);

        // Create request without query parameters
        $request = Request::create('/api/tmdb/configuration/primary_translations', 'GET');

        // Execute
        $response = $controller->proxy('configuration/primary_translations', $request);

        // Assert response status and body
        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals($mockResponseData, $responseData);

        // Verify response is an array of language codes
        $this->assertIsArray($responseData);
        $this->assertNotEmpty($responseData);
        $this->assertContains('en-US', $responseData);
        $this->assertContains('fr-FR', $responseData);
    }

    public function testHttpExceptionHandling(): void
    {
        // Create mock response
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(401);
        $mockResponse->method('toArray')->with(false)->willReturn([
            'success' => false,
            'status_code' => 7,
            'status_message' => 'Invalid API key: You must be granted a valid key.'
        ]);

        // Create mock HTTP exception
        $mockException = $this->createMock(HttpExceptionInterface::class);
        $mockException->method('getResponse')->willReturn($mockResponse);

        // Create mock HTTP client that throws the exception
        $mockClient = $this->createMock(HttpClientInterface::class);
        $mockClient->method('request')->willThrowException($mockException);

        // Create mock logger
        $mockLogger = $this->createMock(LoggerInterface::class);
        $mockLogger->expects($this->once())
            ->method('error')
            ->with(
                'TMDb API error',
                $this->callback(function ($context) {
                    return isset($context['path']) &&
                        isset($context['status']) &&
                        $context['status'] === 401;
                })
            );

        // Create mock logger
        $mockCache = $this->createMock(CacheInterface::class);

        // Create controller with mocks
        $controller = new TmdbProxyController($mockClient, $mockLogger, $mockCache);

        // Create request
        $request = Request::create('/api/tmdb/search/movie', 'GET', ['query' => 'Matrix']);

        // Execute
        $response = $controller->proxy('search/movie', $request);

        // Assert
        $this->assertEquals(401, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertFalse($responseData['success']);
        $this->assertEquals(7, $responseData['status_code']);
        $this->assertStringContainsString('Invalid API key', $responseData['status_message']);
    }

    public function testHttpExceptionForwarding404(): void
    {
        // Create mock response for 404
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(404);
        $mockResponse->method('toArray')->with(false)->willReturn([
            'success' => false,
            'status_code' => 34,
            'status_message' => 'The resource you requested could not be found.'
        ]);

        // Create mock HTTP exception
        $mockException = $this->createMock(HttpExceptionInterface::class);
        $mockException->method('getResponse')->willReturn($mockResponse);

        // Create mock HTTP client
        $mockClient = $this->createMock(HttpClientInterface::class);
        $mockClient->method('request')->willThrowException($mockException);

        // Create mock logger
        $mockLogger = $this->createMock(LoggerInterface::class);
        $mockLogger->expects($this->once())
            ->method('error')
            ->with('TMDb API error', $this->anything());

        // Create mock logger
        $mockCache = $this->createMock(CacheInterface::class);

        // Create controller
        $controller = new TmdbProxyController($mockClient, $mockLogger, $mockCache);

        // Create request
        $request = Request::create('/api/tmdb/movie/999999', 'GET');

        // Execute
        $response = $controller->proxy('movie/999999', $request);

        // Assert
        $this->assertEquals(404, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertFalse($responseData['success']);
        $this->assertEquals(34, $responseData['status_code']);
    }

    public function testHttpExceptionForwarding429RateLimit(): void
    {
        // Create mock response for 429 Rate Limit
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(429);
        $mockResponse->method('toArray')->with(false)->willReturn([
            'success' => false,
            'status_code' => 25,
            'status_message' => 'Your request count (30) is over the allowed limit of 20.'
        ]);

        // Create mock HTTP exception
        $mockException = $this->createMock(HttpExceptionInterface::class);
        $mockException->method('getResponse')->willReturn($mockResponse);

        // Create mock HTTP client
        $mockClient = $this->createMock(HttpClientInterface::class);
        $mockClient->method('request')->willThrowException($mockException);

        // Create mock logger
        $mockLogger = $this->createMock(LoggerInterface::class);

        // Create mock logger
        $mockCache = $this->createMock(CacheInterface::class);

        // Create controller
        $controller = new TmdbProxyController($mockClient, $mockLogger, $mockCache);

        // Create request
        $request = Request::create('/api/tmdb/search/movie', 'GET', ['query' => 'Test']);

        // Execute
        $response = $controller->proxy('search/movie', $request);

        // Assert
        $this->assertEquals(429, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertFalse($responseData['success']);
        $this->assertEquals(25, $responseData['status_code']);
        $this->assertStringContainsString('over the allowed limit', $responseData['status_message']);
    }

    /**
     * Test error handling for network errors
     * **Validates: Requirements 4.3, 4.5**
     */
    public function testTransportExceptionHandling(): void
    {
        // Create a concrete transport exception (network error)
        $mockException = new class('Connection timeout') extends \Exception implements TransportExceptionInterface {};

        // Create mock HTTP client that throws transport exception
        $mockClient = $this->createMock(HttpClientInterface::class);
        $mockClient->method('request')->willThrowException($mockException);

        // Create mock logger and verify error is logged
        $mockLogger = $this->createMock(LoggerInterface::class);
        $mockLogger->expects($this->once())
            ->method('error')
            ->with(
                'TMDb API transport error',
                $this->callback(function ($context) {
                    return isset($context['path']) &&
                        $context['path'] === 'search/movie' &&
                        isset($context['error']) &&
                        $context['error'] === 'Connection timeout';
                })
            );

        // Boot kernel to get container for controller
        $kernel = self::bootKernel();
        $container = $kernel->getContainer()->get('test.service_container');

        // Create mock logger
        $mockCache = $this->createMock(CacheInterface::class);

        // Create controller with mocks
        $controller = new TmdbProxyController($mockClient, $mockLogger, $mockCache);
        $controller->setContainer($container);

        // Create request
        $request = Request::create('/api/tmdb/search/movie', 'GET', ['query' => 'Matrix']);

        // Execute
        $response = $controller->proxy('search/movie', $request);

        // Assert response returns HTTP 503 with error message
        $this->assertEquals(503, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('TMDb API unavailable', $responseData['error']);
    }

    /**
     * Test path parameter forwarding for various paths
     * **Validates: Requirements 2.1, 3.1**
     */
    public function testPathParameterForwarding(): void
    {
        // Define test cases with different paths (only authorized endpoints)
        $testCases = [
            [
                'path' => 'search/movie',
                'queryParams' => ['query' => 'Matrix', 'language' => 'en-US'],
                'mockResponse' => ['page' => 1, 'results' => []]
            ],
            [
                'path' => 'configuration/primary_translations',
                'queryParams' => [],
                'mockResponse' => ['en-US', 'fr-FR', 'es-ES']
            ]
        ];

        foreach ($testCases as $testCase) {
            // Create mock response
            $mockResponse = $this->createMock(ResponseInterface::class);
            $mockResponse->method('getStatusCode')->willReturn(200);
            $mockResponse->method('toArray')->willReturn($testCase['mockResponse']);

            // Track captured path
            $capturedPath = null;
            $capturedOptions = null;

            // Create mock HTTP client
            $mockClient = $this->createMock(HttpClientInterface::class);
            $mockClient->expects($this->once())
                ->method('request')
                ->with(
                    'GET',
                    $this->callback(function ($path) use (&$capturedPath) {
                        $capturedPath = $path;
                        return true;
                    }),
                    $this->callback(function ($options) use (&$capturedOptions) {
                        $capturedOptions = $options;
                        return true;
                    })
                )
                ->willReturn($mockResponse);

            // Create mock logger
            $mockLogger = $this->createMock(LoggerInterface::class);

            // Create mock logger
            $mockCache = $this->createMock(CacheInterface::class);

            // Create controller
            $controller = new TmdbProxyController($mockClient, $mockLogger, $mockCache);

            // Create request
            $request = Request::create(
                '/api/tmdb/' . $testCase['path'],
                'GET',
                $testCase['queryParams']
            );

            // Execute
            $response = $controller->proxy($testCase['path'], $request);

            // Assert path was forwarded correctly
            $this->assertEquals(
                $testCase['path'],
                $capturedPath,
                "Path '{$testCase['path']}' was not forwarded correctly"
            );

            // Assert query parameters were forwarded correctly
            $this->assertEquals(
                $testCase['queryParams'],
                $capturedOptions['query'],
                "Query parameters for path '{$testCase['path']}' were not forwarded correctly"
            );

            // Assert response status and body
            $this->assertEquals(200, $response->getStatusCode());
            $responseData = json_decode($response->getContent(), true);
            $this->assertEquals($testCase['mockResponse'], $responseData);
        }
    }
}
