<?php

namespace App\Tests\Controller;

use App\Controller\TmdbProxyController;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/** * 
 * Property-based test that verifies for ANY response returned to the client,
 * the response body and headers SHALL NOT contain the TMDb API key value.
 */
class TmdbProxyApiKeyConfidentialityPropertyTest extends KernelTestCase
{
    private const ITERATIONS = 100;
    private const TMDB_API_KEY = 'test_api_key_12345';

    /**
     * Property test: API key confidentiality
     * 
     * This test generates random TMDb responses and verifies that NO response
     * body or headers contain the API key value.
     * 
     * Runs 100 iterations with randomly generated responses to verify the property
     * holds across all valid executions.
     * 
     * **Validates: Requirements 1.4**
     */
    public function testApiKeyNotExposedInResponses(): void
    {
        $failedCases = [];
        
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Generate random path
            $path = $this->generateRandomPath();
            
            // Generate random TMDb response
            $tmdbResponseData = $this->generateRandomTmdbResponse();
            
            // Create mock response
            $mockResponse = $this->createMock(ResponseInterface::class);
            $mockResponse->method('getStatusCode')->willReturn(200);
            $mockResponse->method('toArray')->willReturn($tmdbResponseData);
            
            // Create mock HTTP client
            $mockClient = $this->createMock(HttpClientInterface::class);
            $mockClient->method('request')->willReturn($mockResponse);
            
            // Create mock logger
            $mockLogger = $this->createMock(LoggerInterface::class);
            
            // Create controller
            $controller = new TmdbProxyController($mockClient, $mockLogger);
            
            // Create request
            $request = Request::create('/api/tmdb/' . $path, 'GET');
            
            // Execute
            $response = $controller->proxy($path, $request);
            
            // Get response content
            $responseContent = $response->getContent();
            
            // Get response headers
            $responseHeaders = $response->headers->all();
            
            // Check if API key is present in response body
            if (str_contains($responseContent, self::TMDB_API_KEY)) {
                $failedCases[] = [
                    'iteration' => $i,
                    'path' => $path,
                    'reason' => 'API key found in response body',
                    'responseContent' => substr($responseContent, 0, 200) // Truncate for readability
                ];
            }
            
            // Check if API key is present in any response header
            foreach ($responseHeaders as $headerName => $headerValues) {
                foreach ($headerValues as $headerValue) {
                    if (str_contains($headerValue, self::TMDB_API_KEY)) {
                        $failedCases[] = [
                            'iteration' => $i,
                            'path' => $path,
                            'reason' => "API key found in response header '{$headerName}'",
                            'headerValue' => $headerValue
                        ];
                    }
                }
            }
        }
        
        // Assert no failures occurred
        $this->assertEmpty($failedCases, 
            "Property test failed for " . count($failedCases) . " out of " . self::ITERATIONS . " iterations:\n" .
            json_encode($failedCases, JSON_PRETTY_PRINT)
        );
    }

    /**
     * Property test: API key confidentiality in error responses
     * 
     * This test verifies that error responses also don't expose the API key.
     */
    public function testApiKeyNotExposedInErrorResponses(): void
    {
        $failedCases = [];
        
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Generate random path
            $path = $this->generateRandomPath();
            
            // Generate random error response
            $errorData = $this->generateRandomErrorResponse();
            $statusCode = $errorData['statusCode'];
            $errorBody = $errorData['body'];
            
            // Create mock error response
            $mockResponse = $this->createMock(ResponseInterface::class);
            $mockResponse->method('getStatusCode')->willReturn($statusCode);
            $mockResponse->method('toArray')->willReturn($errorBody);
            
            // Create mock HTTP exception
            $mockException = $this->createMock(\Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface::class);
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
            
            // Get response content
            $responseContent = $response->getContent();
            
            // Get response headers
            $responseHeaders = $response->headers->all();
            
            // Check if API key is present in response body
            if (str_contains($responseContent, self::TMDB_API_KEY)) {
                $failedCases[] = [
                    'iteration' => $i,
                    'path' => $path,
                    'statusCode' => $statusCode,
                    'reason' => 'API key found in error response body',
                    'responseContent' => substr($responseContent, 0, 200)
                ];
            }
            
            // Check if API key is present in any response header
            foreach ($responseHeaders as $headerName => $headerValues) {
                foreach ($headerValues as $headerValue) {
                    if (str_contains($headerValue, self::TMDB_API_KEY)) {
                        $failedCases[] = [
                            'iteration' => $i,
                            'path' => $path,
                            'statusCode' => $statusCode,
                            'reason' => "API key found in error response header '{$headerName}'",
                            'headerValue' => $headerValue
                        ];
                    }
                }
            }
        }
        
        // Assert no failures occurred
        $this->assertEmpty($failedCases, 
            "Property test failed for " . count($failedCases) . " out of " . self::ITERATIONS . " iterations:\n" .
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
            'configuration/primary_translations',
            'movie/' . rand(1, 100000),
            'tv/' . rand(1, 100000),
            'discover/movie',
            'trending/movie/day',
        ];
        
        return $paths[array_rand($paths)];
    }

    /**
     * Generate random TMDb response data
     * 
     * @return array<string, mixed>
     */
    private function generateRandomTmdbResponse(): array
    {
        $responseTypes = [
            // Search response
            [
                'page' => rand(1, 100),
                'results' => $this->generateRandomResults(),
                'total_pages' => rand(1, 500),
                'total_results' => rand(0, 10000)
            ],
            // Configuration response
            [
                'languages' => $this->generateRandomLanguages()
            ],
            // Movie details response
            [
                'id' => rand(1, 100000),
                'title' => $this->generateRandomString(5, 50),
                'overview' => $this->generateRandomString(50, 200),
                'release_date' => $this->generateRandomDate(),
                'vote_average' => round(rand(0, 100) / 10, 1),
                'poster_path' => '/random_poster_' . rand(1, 1000) . '.jpg'
            ],
            // Empty response
            [
                'results' => []
            ]
        ];
        
        return $responseTypes[array_rand($responseTypes)];
    }

    /**
     * Generate random error response
     * 
     * @return array{statusCode: int, body: array<string, mixed>}
     */
    private function generateRandomErrorResponse(): array
    {
        $statusCodes = [400, 401, 404, 429, 500, 503];
        $statusCode = $statusCodes[array_rand($statusCodes)];
        
        $errorMessages = [
            'Invalid API key',
            'Resource not found',
            'Rate limit exceeded',
            'Internal server error',
            'Service unavailable',
            'Bad request'
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
     * Generate random results array
     * 
     * @return array<int, array<string, mixed>>
     */
    private function generateRandomResults(): array
    {
        $count = rand(0, 20);
        $results = [];
        
        for ($i = 0; $i < $count; $i++) {
            $results[] = [
                'id' => rand(1, 100000),
                'title' => $this->generateRandomString(5, 50),
                'overview' => $this->generateRandomString(20, 150),
                'poster_path' => '/poster_' . rand(1, 1000) . '.jpg',
                'vote_average' => round(rand(0, 100) / 10, 1)
            ];
        }
        
        return $results;
    }

    /**
     * Generate random languages array
     * 
     * @return array<int, string>
     */
    private function generateRandomLanguages(): array
    {
        $languages = ['en', 'fr', 'es', 'de', 'it', 'ja', 'ko', 'pt', 'ru', 'zh'];
        $count = rand(5, count($languages));
        
        shuffle($languages);
        return array_slice($languages, 0, $count);
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

    /**
     * Generate a random date
     * 
     * @return string
     */
    private function generateRandomDate(): string
    {
        $year = rand(1900, 2024);
        $month = str_pad((string) rand(1, 12), 2, '0', STR_PAD_LEFT);
        $day = str_pad((string) rand(1, 28), 2, '0', STR_PAD_LEFT);
        
        return "{$year}-{$month}-{$day}";
    }
}
