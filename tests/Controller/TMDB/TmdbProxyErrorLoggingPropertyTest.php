<?php

namespace App\Tests\Controller\TMDB;

use App\Controller\TmdbProxyController;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Property-based test that verifies for ANY error response (from TMDb API
 * or network errors), the proxy SHALL create a log entry containing the
 * error details.
 */
class TmdbProxyErrorLoggingPropertyTest extends KernelTestCase
{
    private const ITERATIONS = 100;

    /**
     * Property test: Error logging for TMDb API errors
     *
     * This test generates random TMDb API errors (4xx, 5xx) and verifies
     * that ALL errors are logged with appropriate details.
     *
     * Runs 100 iterations with randomly generated errors to verify the property
     * holds across all valid executions.
     */
    public function testTmdbApiErrorsAreLogged(): void
    {
        $failedCases = [];

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Generate random path
            $path = $this->generateRandomPath();

            // Generate random error
            $errorData = $this->generateRandomTmdbError();
            $statusCode = $errorData['statusCode'];
            $errorBody = $errorData['body'];

            // Track if logger was called
            $loggerCalled = false;
            $loggedLevel = null;
            $loggedMessage = null;
            $loggedContext = null;

            // Create mock error response
            $mockResponse = $this->createMock(ResponseInterface::class);
            $mockResponse->method('getStatusCode')->willReturn($statusCode);
            $mockResponse->method('toArray')->willReturn($errorBody);

            // Create appropriate exception based on status code
            if ($statusCode >= 400 && $statusCode < 500) {
                $mockException = $this->createMock(ClientExceptionInterface::class);
            } else {
                $mockException = $this->createMock(ServerExceptionInterface::class);
            }
            $mockException->method('getResponse')->willReturn($mockResponse);

            // Create mock HTTP client that throws exception
            $mockClient = $this->createMock(HttpClientInterface::class);
            $mockClient->method('request')->willThrowException($mockException);

            // Create mock logger that captures log calls
            $mockLogger = $this->createMock(LoggerInterface::class);
            $mockLogger->expects($this->once())
                ->method('error')
                ->with(
                    $this->callback(function ($message) use (&$loggerCalled, &$loggedMessage) {
                        $loggerCalled = true;
                        $loggedMessage = $message;
                        return true;
                    }),
                    $this->callback(function ($context) use (&$loggedContext) {
                        $loggedContext = $context;
                        return true;
                    })
                );

            // Create mock logger
            $mockCache = $this->createMock(CacheInterface::class);

            // Create controller
            $controller = new TmdbProxyController($mockClient, $mockLogger, $mockCache);

            // Create request
            $request = Request::create('/api/tmdb/' . $path, 'GET');

            // Execute
            $response = $controller->proxy($path, $request);

            // Verify logger was called
            if (!$loggerCalled) {
                $failedCases[] = [
                    'iteration' => $i,
                    'path' => $path,
                    'statusCode' => $statusCode,
                    'reason' => 'Logger was not called for TMDb API error'
                ];
            }

            // Verify log message is appropriate
            if ($loggedMessage !== 'TMDb API error') {
                $failedCases[] = [
                    'iteration' => $i,
                    'path' => $path,
                    'statusCode' => $statusCode,
                    'expectedMessage' => 'TMDb API error',
                    'actualMessage' => $loggedMessage,
                    'reason' => 'Log message does not match expected format'
                ];
            }

            // Verify log context contains path
            if (!isset($loggedContext['path']) || $loggedContext['path'] !== $path) {
                $failedCases[] = [
                    'iteration' => $i,
                    'path' => $path,
                    'statusCode' => $statusCode,
                    'loggedContext' => $loggedContext,
                    'reason' => 'Log context does not contain correct path'
                ];
            }

            // Verify log context contains status code
            if (!isset($loggedContext['status']) || $loggedContext['status'] !== $statusCode) {
                $failedCases[] = [
                    'iteration' => $i,
                    'path' => $path,
                    'statusCode' => $statusCode,
                    'loggedContext' => $loggedContext,
                    'reason' => 'Log context does not contain correct status code'
                ];
            }
        }

        // Assert no failures occurred
        $this->assertEmpty(
            $failedCases,
            "Property test failed for " . count($failedCases) . " out of " . self::ITERATIONS . " iterations:\n" .
                json_encode($failedCases, JSON_PRETTY_PRINT)
        );
    }

    /**
     * Property test: Error logging for network/transport errors
     *
     * This test generates random network/transport errors and verifies
     * that ALL errors are logged with appropriate details.
     */
    public function testNetworkErrorsAreLogged(): void
    {
        $failedCases = [];

        for ($i = 0; $i < 50; $i++) {
            // Generate random path
            $path = $this->generateRandomPath();

            // Generate random transport error message
            $errorMessage = $this->generateRandomTransportErrorMessage();

            // Track if logger was called
            $loggerCalled = false;
            $loggedMessage = null;
            $loggedContext = null;

            // Create mock transport exception
            $mockException = new class($errorMessage) extends \Exception implements TransportExceptionInterface {
                public function __construct(string $message)
                {
                    parent::__construct($message);
                }
            };

            // Create mock HTTP client that throws exception
            $mockClient = $this->createMock(HttpClientInterface::class);
            $mockClient->method('request')->willThrowException($mockException);

            // Create mock logger that captures log calls
            $mockLogger = $this->createMock(LoggerInterface::class);
            $mockLogger->expects($this->once())
                ->method('error')
                ->with(
                    $this->callback(function ($message) use (&$loggerCalled, &$loggedMessage) {
                        $loggerCalled = true;
                        $loggedMessage = $message;
                        return true;
                    }),
                    $this->callback(function ($context) use (&$loggedContext) {
                        $loggedContext = $context;
                        return true;
                    })
                );

            // Create mock logger
            $mockCache = $this->createMock(CacheInterface::class);

            // Create controller
            $controller = new TmdbProxyController($mockClient, $mockLogger, $mockCache);

            // Create request
            $request = Request::create('/api/tmdb/' . $path, 'GET');

            // Execute
            $response = $controller->proxy($path, $request);

            // Verify logger was called
            if (!$loggerCalled) {
                $failedCases[] = [
                    'iteration' => $i,
                    'path' => $path,
                    'errorMessage' => $errorMessage,
                    'reason' => 'Logger was not called for transport error'
                ];
            }

            // Verify log message is appropriate
            if ($loggedMessage !== 'TMDb API transport error') {
                $failedCases[] = [
                    'iteration' => $i,
                    'path' => $path,
                    'expectedMessage' => 'TMDb API transport error',
                    'actualMessage' => $loggedMessage,
                    'reason' => 'Log message does not match expected format'
                ];
            }

            // Verify log context contains path
            if (!isset($loggedContext['path']) || $loggedContext['path'] !== $path) {
                $failedCases[] = [
                    'iteration' => $i,
                    'path' => $path,
                    'loggedContext' => $loggedContext,
                    'reason' => 'Log context does not contain correct path'
                ];
            }

            // Verify log context contains error message
            if (!isset($loggedContext['error']) || $loggedContext['error'] !== $errorMessage) {
                $failedCases[] = [
                    'iteration' => $i,
                    'path' => $path,
                    'errorMessage' => $errorMessage,
                    'loggedContext' => $loggedContext,
                    'reason' => 'Log context does not contain correct error message'
                ];
            }
        }

        // Assert no failures occurred
        $this->assertEmpty(
            $failedCases,
            "Property test failed for " . count($failedCases) . " out of 50 iterations:\n" .
                json_encode($failedCases, JSON_PRETTY_PRINT)
        );
    }

    /**
     * Generate a random TMDb API path
     *
     * @return string
     */
    private function generateRandomPath(): string
    {
        $paths = [
            'search/movie',
            'search/tv',
            'search/person',
            'configuration/primary_translations',
            'configuration/languages',
            'movie/' . rand(1, 100000),
            'tv/' . rand(1, 100000),
            'discover/movie',
            'trending/movie/day',
            'genre/movie/list',
        ];

        return $paths[array_rand($paths)];
    }

    /**
     * Generate random TMDb error
     *
     * @return array{statusCode: int, body: array<string, mixed>}
     */
    private function generateRandomTmdbError(): array
    {
        $statusCodes = [400, 401, 404, 422, 429, 500, 502, 503, 504];
        $statusCode = $statusCodes[array_rand($statusCodes)];

        $errorMessages = [
            'Invalid API key: You must be granted a valid key.',
            'The resource you requested could not be found.',
            'Your request count (30) is over the allowed limit of 20.',
            'Internal Server Error',
            'Service Unavailable',
            'Bad Gateway',
            'Gateway Timeout',
            'Invalid parameters: You must provide a query string.',
            'Authentication failed: You do not have permissions to access the service.',
            'Validation failed: The request could not be understood.',
        ];

        return [
            'statusCode' => $statusCode,
            'body' => [
                'success' => false,
                'status_code' => rand(1, 50),
                'status_message' => $errorMessages[array_rand($errorMessages)]
            ]
        ];
    }

    /**
     * Generate random transport error message
     *
     * @return string
     */
    private function generateRandomTransportErrorMessage(): string
    {
        $errorMessages = [
            'Connection timeout',
            'Could not resolve host: api.themoviedb.org',
            'Network is unreachable',
            'Connection refused',
            'SSL certificate problem: unable to get local issuer certificate',
            'Operation timed out after 10000 milliseconds',
            'Failed to connect to api.themoviedb.org port 443',
            'Recv failure: Connection reset by peer',
            'Empty reply from server',
            'HTTP/2 stream 0 was not closed cleanly',
        ];

        return $errorMessages[array_rand($errorMessages)];
    }
}
