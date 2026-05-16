<?php

namespace App\Tests\Controller;

use App\Controller\TmdbProxyController;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Property-based test that verifies for ANY search request with provided
 * query parameters, all parameters SHALL be forwarded to the TMDb API
 * endpoint without modification or omission.
 */
class TmdbProxyQueryParameterForwardingPropertyTest extends KernelTestCase
{
    private const ITERATIONS = 100;

    /**
     * Property test: Query parameter forwarding
     * 
     * This test generates random combinations of query parameters and verifies
     * that ALL provided parameters are forwarded to TMDb API without modification.
     * 
     * Runs 100 iterations with randomly generated query parameters to verify
     * the property holds across all valid executions.
     */
    public function testAllQueryParametersForwardedWithoutModification(): void
    {
        $failedCases = [];
        
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Generate random path
            $path = $this->generateRandomPath();
            
            // Generate random query parameters
            $queryParams = $this->generateRandomQueryParameters();
            
            // Track captured request options
            $capturedOptions = null;
            
            // Create mock response
            $mockResponse = $this->createMock(ResponseInterface::class);
            $mockResponse->method('getStatusCode')->willReturn(200);
            $mockResponse->method('toArray')->willReturn(['success' => true]);
            
            // Create mock HTTP client that captures the request
            $mockClient = $this->createMock(HttpClientInterface::class);
            $mockClient->expects($this->once())
                ->method('request')
                ->with(
                    'GET',
                    $path,
                    $this->callback(function ($options) use (&$capturedOptions) {
                        $capturedOptions = $options;
                        return true;
                    })
                )
                ->willReturn($mockResponse);
            
            // Create mock logger
            $mockLogger = $this->createMock(LoggerInterface::class);
            
            // Create controller
            $controller = new TmdbProxyController($mockClient, $mockLogger);
            
            // Create request with query parameters
            $request = Request::create('/api/tmdb/' . $path, 'GET', $queryParams);
            
            // Execute
            $response = $controller->proxy($path, $request);
            
            // Verify the request was successful
            $this->assertEquals(200, $response->getStatusCode(), 
                "Iteration {$i}: Request failed for path '{$path}'");
            
            // Verify query parameters are present in captured options
            if (!isset($capturedOptions['query'])) {
                $failedCases[] = [
                    'iteration' => $i,
                    'path' => $path,
                    'originalParams' => $queryParams,
                    'reason' => 'Query parameters not forwarded (query key missing in options)'
                ];
                continue;
            }
            
            $forwardedParams = $capturedOptions['query'];
            
            // Check if all original parameters are present
            foreach ($queryParams as $key => $value) {
                if (!array_key_exists($key, $forwardedParams)) {
                    $failedCases[] = [
                        'iteration' => $i,
                        'path' => $path,
                        'originalParams' => $queryParams,
                        'forwardedParams' => $forwardedParams,
                        'reason' => "Parameter '{$key}' was omitted"
                    ];
                } elseif ($forwardedParams[$key] !== $value) {
                    $failedCases[] = [
                        'iteration' => $i,
                        'path' => $path,
                        'originalParams' => $queryParams,
                        'forwardedParams' => $forwardedParams,
                        'reason' => "Parameter '{$key}' was modified: expected '{$value}', got '{$forwardedParams[$key]}'"
                    ];
                }
            }
            
            // Check if any extra parameters were added
            foreach ($forwardedParams as $key => $value) {
                if (!array_key_exists($key, $queryParams)) {
                    $failedCases[] = [
                        'iteration' => $i,
                        'path' => $path,
                        'originalParams' => $queryParams,
                        'forwardedParams' => $forwardedParams,
                        'reason' => "Extra parameter '{$key}' was added"
                    ];
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
     * Property test: Empty query parameters are handled correctly
     * 
     * This test verifies that requests with no query parameters are also
     * handled correctly (empty array forwarded).
     */
    public function testEmptyQueryParametersForwardedCorrectly(): void
    {
        $failedCases = [];
        
        for ($i = 0; $i < 20; $i++) {
            // Generate random path
            $path = $this->generateRandomPath();
            
            // No query parameters
            $queryParams = [];
            
            // Track captured request options
            $capturedOptions = null;
            
            // Create mock response
            $mockResponse = $this->createMock(ResponseInterface::class);
            $mockResponse->method('getStatusCode')->willReturn(200);
            $mockResponse->method('toArray')->willReturn(['success' => true]);
            
            // Create mock HTTP client that captures the request
            $mockClient = $this->createMock(HttpClientInterface::class);
            $mockClient->expects($this->once())
                ->method('request')
                ->with(
                    'GET',
                    $path,
                    $this->callback(function ($options) use (&$capturedOptions) {
                        $capturedOptions = $options;
                        return true;
                    })
                )
                ->willReturn($mockResponse);
            
            // Create mock logger
            $mockLogger = $this->createMock(LoggerInterface::class);
            
            // Create controller
            $controller = new TmdbProxyController($mockClient, $mockLogger);
            
            // Create request with no query parameters
            $request = Request::create('/api/tmdb/' . $path, 'GET');
            
            // Execute
            $response = $controller->proxy($path, $request);
            
            // Verify the request was successful
            $this->assertEquals(200, $response->getStatusCode());
            
            // Verify query parameters are present (should be empty array)
            if (!isset($capturedOptions['query'])) {
                $failedCases[] = [
                    'iteration' => $i,
                    'path' => $path,
                    'reason' => 'Query key missing in options'
                ];
            } elseif ($capturedOptions['query'] !== []) {
                $failedCases[] = [
                    'iteration' => $i,
                    'path' => $path,
                    'forwardedParams' => $capturedOptions['query'],
                    'reason' => 'Expected empty array, got non-empty query parameters'
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
            'movie/' . rand(1, 100000),
            'discover/movie',
            'trending/movie/day',
        ];
        
        return $paths[array_rand($paths)];
    }

    /**
     * Generate random query parameters with various types and edge cases
     * 
     * @return array<string, string>
     */
    private function generateRandomQueryParameters(): array
    {
        $possibleParams = [
            'query' => $this->generateRandomString(1, 50),
            'language' => $this->generateRandomLanguage(),
            'page' => (string) rand(1, 1000),
            'include_adult' => rand(0, 1) ? 'true' : 'false',
            'region' => $this->generateRandomRegion(),
            'year' => (string) rand(1900, 2024),
            'primary_release_year' => (string) rand(1900, 2024),
            'sort_by' => $this->generateRandomSortBy(),
            'with_genres' => (string) rand(1, 50),
            'with_cast' => (string) rand(1, 100000),
            'with_crew' => (string) rand(1, 100000),
            'vote_average.gte' => (string) rand(0, 10),
            'vote_average.lte' => (string) rand(0, 10),
            'with_runtime.gte' => (string) rand(0, 300),
            'with_runtime.lte' => (string) rand(0, 300),
            // Edge cases
            'special_chars' => 'test&value=123',
            'unicode' => 'Café Müller 日本',
            'empty_value' => '',
            'numeric_string' => '00123',
            'boolean_string' => 'false',
        ];
        
        // Randomly select 1-10 parameters
        $numParams = rand(1, 10);
        $selectedKeys = array_rand($possibleParams, min($numParams, count($possibleParams)));
        
        if (!is_array($selectedKeys)) {
            $selectedKeys = [$selectedKeys];
        }
        
        $params = [];
        foreach ($selectedKeys as $key) {
            $params[$key] = $possibleParams[$key];
        }
        
        return $params;
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
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789 -_';
        $string = '';
        
        for ($i = 0; $i < $length; $i++) {
            $string .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        return trim($string);
    }

    /**
     * Generate a random language code
     * 
     * @return string
     */
    private function generateRandomLanguage(): string
    {
        $languages = [
            'en-US', 'fr-FR', 'es-ES', 'de-DE', 'it-IT',
            'ja-JP', 'ko-KR', 'pt-BR', 'ru-RU', 'zh-CN'
        ];
        
        return $languages[array_rand($languages)];
    }

    /**
     * Generate a random region code
     * 
     * @return string
     */
    private function generateRandomRegion(): string
    {
        $regions = ['US', 'FR', 'ES', 'DE', 'IT', 'JP', 'KR', 'BR', 'RU', 'CN'];
        
        return $regions[array_rand($regions)];
    }

    /**
     * Generate a random sort_by parameter
     * 
     * @return string
     */
    private function generateRandomSortBy(): string
    {
        $sortOptions = [
            'popularity.asc',
            'popularity.desc',
            'release_date.asc',
            'release_date.desc',
            'vote_average.asc',
            'vote_average.desc'
        ];
        
        return $sortOptions[array_rand($sortOptions)];
    }
}
