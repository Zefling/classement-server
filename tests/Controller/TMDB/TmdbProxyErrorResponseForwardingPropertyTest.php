<?php

namespace App\Tests\Controller\TMDB;

use App\Controller\TmdbProxyController;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Property-based test that verifies for ANY error response from the TMDb API
 * (HTTP 4xx or 5xx), the proxy SHALL forward both the HTTP status code and
 * the error message body to the client without modification.
 */
class TmdbProxyErrorResponseForwardingPropertyTest extends KernelTestCase
{
    private const ITERATIONS = 100;

    /**
     * Property test: Error response forwarding
     * 
     * This test generates random TMDb error responses (400, 401, 404, 429, 500, 503)
     * and verifies that the proxy forwards both status code and error body without
     * modification.
     * 
     * Runs 100 iterations with randomly generated error responses to verify
     * the property holds across all valid executions.
     */
    public function testErrorResponsesForwardedWithoutModification(): void
    {
        $failedCases = [];
        
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Generate random path
            $path = $this->generateRandomPath();
            
            // Generate random error response
            $errorData = $this->generateRandomErrorResponse();
            $tmdbStatusCode = $errorData['statusCode'];
            $tmdbErrorBody = $errorData['body'];
            
            // Create mock error response
            $mockResponse = $this->createMock(ResponseInterface::class);
            $mockResponse->method('getStatusCode')->willReturn($tmdbStatusCode);
            $mockResponse->method('toArray')->willReturn($tmdbErrorBody);
            
            // Create appropriate exception based on status code
            if ($tmdbStatusCode >= 400 && $tmdbStatusCode < 500) {
                $mockException = $this->createMock(ClientExceptionInterface::class);
            } else {
                $mockException = $this->createMock(ServerExceptionInterface::class);
            }
            $mockException->method('getResponse')->willReturn($mockResponse);
            
            // Create mock HTTP client that throws exception
            $mockClient = $this->createMock(HttpClientInterface::class);
            $mockClient->method('request')->willThrowException($mockException);
            
            // Create mock logger
            $mockLogger = $this->createMock(LoggerInterface::class);
            
            // Create controller
            $controller = new TmdbProxyController($mockClient, $mockLogger);
            
            // Create request
            $request = Request::create('/api/tmdb/' . $path, 'GET');
            
            // Execute
            $response = $controller->proxy($path, $request);
            
            // Verify status code is forwarded
            if ($response->getStatusCode() !== $tmdbStatusCode) {
                $failedCases[] = [
                    'iteration' => $i,
                    'path' => $path,
                    'expectedStatus' => $tmdbStatusCode,
                    'actualStatus' => $response->getStatusCode(),
                    'reason' => 'Status code not forwarded correctly'
                ];
            }
            
            // Verify error body is forwarded without modification
            $responseData = json_decode($response->getContent(), true);
            
            // Use JSON comparison for deep equality check
            if (json_encode($responseData) !== json_encode($tmdbErrorBody)) {
                $failedCases[] = [
                    'iteration' => $i,
                    'path' => $path,
                    'statusCode' => $tmdbStatusCode,
                    'expectedBody' => $tmdbErrorBody,
                    'actualBody' => $responseData,
                    'reason' => 'Error body not identical to TMDb error response'
                ];
            }
        }
        
        // Assert no failures occurred
        $this->assertEmpty($failedCases, 
            "Property test failed for " . count($failedCases) . " out of " . self::ITERATIONS . " iterations:\n" .
            json_encode($failedCases, JSON_PRETTY_PRINT)
        );
    }

    /**
     * Property test: Various 4xx error codes are forwarded correctly
     * 
     * This test verifies that different 4xx client error codes are all
     * forwarded correctly.
     */
    public function testVarious4xxErrorCodesForwardedCorrectly(): void
    {
        $failedCases = [];
        $statusCodes = [400, 401, 403, 404, 422, 429];
        
        for ($i = 0; $i < 30; $i++) {
            // Generate random path
            $path = $this->generateRandomPath();
            
            // Pick a random 4xx status code
            $tmdbStatusCode = $statusCodes[array_rand($statusCodes)];
            
            // Generate error body
            $tmdbErrorBody = $this->generateErrorBody($tmdbStatusCode);
            
            // Create mock error response
            $mockResponse = $this->createMock(ResponseInterface::class);
            $mockResponse->method('getStatusCode')->willReturn($tmdbStatusCode);
            $mockResponse->method('toArray')->willReturn($tmdbErrorBody);
            
            // Create client exception
            $mockException = $this->createMock(ClientExceptionInterface::class);
            $mockException->method('getResponse')->willReturn($mockResponse);
            
            // Create mock HTTP client that throws exception
            $mockClient = $this->createMock(HttpClientInterface::class);
            $mockClient->method('request')->willThrowException($mockException);
            
            // Create mock logger
            $mockLogger = $this->createMock(LoggerInterface::class);
            
            // Create controller
            $controller = new TmdbProxyController($mockClient, $mockLogger);
            
            // Create request
            $request = Request::create('/api/tmdb/' . $path, 'GET');
            
            // Execute
            $response = $controller->proxy($path, $request);
            
            // Verify status code is forwarded
            if ($response->getStatusCode() !== $tmdbStatusCode) {
                $failedCases[] = [
                    'iteration' => $i,
                    'path' => $path,
                    'expectedStatus' => $tmdbStatusCode,
                    'actualStatus' => $response->getStatusCode(),
                    'reason' => '4xx status code not forwarded correctly'
                ];
            }
            
            // Verify error body is forwarded
            $responseData = json_decode($response->getContent(), true);
            
            if (json_encode($responseData) !== json_encode($tmdbErrorBody)) {
                $failedCases[] = [
                    'iteration' => $i,
                    'path' => $path,
                    'statusCode' => $tmdbStatusCode,
                    'expectedBody' => $tmdbErrorBody,
                    'actualBody' => $responseData,
                    'reason' => '4xx error body not identical'
                ];
            }
        }
        
        // Assert no failures occurred
        $this->assertEmpty($failedCases, 
            "Property test failed for " . count($failedCases) . " out of 30 iterations:\n" .
            json_encode($failedCases, JSON_PRETTY_PRINT)
        );
    }

    /**
     * Property test: Various 5xx error codes are forwarded correctly
     * 
     * This test verifies that different 5xx server error codes are all
     * forwarded correctly.
     */
    public function testVarious5xxErrorCodesForwardedCorrectly(): void
    {
        $failedCases = [];
        $statusCodes = [500, 502, 503, 504];
        
        for ($i = 0; $i < 20; $i++) {
            // Generate random path
            $path = $this->generateRandomPath();
            
            // Pick a random 5xx status code
            $tmdbStatusCode = $statusCodes[array_rand($statusCodes)];
            
            // Generate error body
            $tmdbErrorBody = $this->generateErrorBody($tmdbStatusCode);
            
            // Create mock error response
            $mockResponse = $this->createMock(ResponseInterface::class);
            $mockResponse->method('getStatusCode')->willReturn($tmdbStatusCode);
            $mockResponse->method('toArray')->willReturn($tmdbErrorBody);
            
            // Create server exception
            $mockException = $this->createMock(ServerExceptionInterface::class);
            $mockException->method('getResponse')->willReturn($mockResponse);
            
            // Create mock HTTP client that throws exception
            $mockClient = $this->createMock(HttpClientInterface::class);
            $mockClient->method('request')->willThrowException($mockException);
            
            // Create mock logger
            $mockLogger = $this->createMock(LoggerInterface::class);
            
            // Create controller
            $controller = new TmdbProxyController($mockClient, $mockLogger);
            
            // Create request
            $request = Request::create('/api/tmdb/' . $path, 'GET');
            
            // Execute
            $response = $controller->proxy($path, $request);
            
            // Verify status code is forwarded
            if ($response->getStatusCode() !== $tmdbStatusCode) {
                $failedCases[] = [
                    'iteration' => $i,
                    'path' => $path,
                    'expectedStatus' => $tmdbStatusCode,
                    'actualStatus' => $response->getStatusCode(),
                    'reason' => '5xx status code not forwarded correctly'
                ];
            }
            
            // Verify error body is forwarded
            $responseData = json_decode($response->getContent(), true);
            
            if (json_encode($responseData) !== json_encode($tmdbErrorBody)) {
                $failedCases[] = [
                    'iteration' => $i,
                    'path' => $path,
                    'statusCode' => $tmdbStatusCode,
                    'expectedBody' => $tmdbErrorBody,
                    'actualBody' => $responseData,
                    'reason' => '5xx error body not identical'
                ];
            }
        }
        
        // Assert no failures occurred
        $this->assertEmpty($failedCases, 
            "Property test failed for " . count($failedCases) . " out of 20 iterations:\n" .
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
     * Generate random error response
     * 
     * @return array{statusCode: int, body: array<string, mixed>}
     */
    private function generateRandomErrorResponse(): array
    {
        $statusCodes = [400, 401, 404, 422, 429, 500, 502, 503, 504];
        $statusCode = $statusCodes[array_rand($statusCodes)];
        
        return [
            'statusCode' => $statusCode,
            'body' => $this->generateErrorBody($statusCode)
        ];
    }

    /**
     * Generate error body based on status code
     * 
     * @param int $statusCode
     * @return array<string, mixed>
     */
    private function generateErrorBody(int $statusCode): array
    {
        $errorMessages = [
            400 => 'Invalid parameters: You must provide a query string.',
            401 => 'Invalid API key: You must be granted a valid key.',
            403 => 'Authentication failed: You do not have permissions to access the service.',
            404 => 'The resource you requested could not be found.',
            422 => 'Validation failed: The request could not be understood.',
            429 => 'Your request count (30) is over the allowed limit of 20.',
            500 => 'Internal Server Error',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
        ];
        
        $message = $errorMessages[$statusCode] ?? 'Unknown error';
        
        // Generate various error body structures
        $bodyTypes = [
            // Standard TMDb error format
            [
                'success' => false,
                'status_code' => rand(1, 50),
                'status_message' => $message
            ],
            // Alternative format with additional fields
            [
                'success' => false,
                'status_code' => rand(1, 50),
                'status_message' => $message,
                'errors' => [
                    $this->generateRandomString(10, 30)
                ]
            ],
            // Minimal error format
            [
                'error' => $message,
                'code' => $statusCode
            ],
            // Detailed error format
            [
                'success' => false,
                'status_code' => rand(1, 50),
                'status_message' => $message,
                'timestamp' => time(),
                'path' => '/3/' . $this->generateRandomPath()
            ],
        ];
        
        return $bodyTypes[array_rand($bodyTypes)];
    }

    /**
     * Generate a random string
     * 
     * @param int $minLength
     * @param int $maxLength
     * @return string
     */
    private function generateRandomString(int $minLength, int $maxLength): string
    {
        $length = rand($minLength, $maxLength);
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789 ';
        $string = '';
        
        for ($i = 0; $i < $length; $i++) {
            $string .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        return trim($string);
    }
}
