<?php

namespace App\Tests\Controller;

use App\Controller\TmdbProxyController;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Property-based test that verifies for ANY successful TMDb API response (HTTP 2xx),
 * the proxy SHALL return the response body to the client with HTTP 200 status
 * and preserve the original JSON structure.
 */
class TmdbProxySuccessfulResponseForwardingPropertyTest extends KernelTestCase
{
    private const ITERATIONS = 100;

    /**
     * Property test: Successful response forwarding
     * 
     * This test generates random successful TMDb responses (HTTP 200) and verifies
     * that the proxy returns HTTP 200 with identical response body.
     * 
     * Runs 100 iterations with randomly generated responses to verify the property
     * holds across all valid executions.
     */
    public function testSuccessfulResponsesForwardedWithHttp200(): void
    {
        $failedCases = [];
        
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Generate random path
            $path = $this->generateRandomPath();
            
            // Generate random successful TMDb response
            $tmdbResponseData = $this->generateRandomSuccessfulResponse();
            $tmdbStatusCode = 200; // Always 200 for successful responses
            
            // Create mock response
            $mockResponse = $this->createMock(ResponseInterface::class);
            $mockResponse->method('getStatusCode')->willReturn($tmdbStatusCode);
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
            
            // Verify status code is 200
            if ($response->getStatusCode() !== 200) {
                $failedCases[] = [
                    'iteration' => $i,
                    'path' => $path,
                    'expectedStatus' => 200,
                    'actualStatus' => $response->getStatusCode(),
                    'reason' => 'Status code not forwarded correctly'
                ];
            }
            
            // Verify response body is identical
            $responseData = json_decode($response->getContent(), true);
            
            // Use JSON comparison for deep equality check
            if (json_encode($responseData) !== json_encode($tmdbResponseData)) {
                $failedCases[] = [
                    'iteration' => $i,
                    'path' => $path,
                    'expectedBody' => $tmdbResponseData,
                    'actualBody' => $responseData,
                    'reason' => 'Response body not identical to TMDb response'
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
     * Property test: Various 2xx status codes are handled correctly
     * 
     * This test verifies that other 2xx status codes (201, 202, 204) are also
     * forwarded correctly.
     */
    public function testVarious2xxStatusCodesForwardedCorrectly(): void
    {
        $failedCases = [];
        $statusCodes = [200, 201, 202, 204];
        
        for ($i = 0; $i < 20; $i++) {
            // Generate random path
            $path = $this->generateRandomPath();
            
            // Pick a random 2xx status code
            $tmdbStatusCode = $statusCodes[array_rand($statusCodes)];
            
            // Generate response data (empty for 204)
            $tmdbResponseData = $tmdbStatusCode === 204 ? [] : $this->generateRandomSuccessfulResponse();
            
            // Create mock response
            $mockResponse = $this->createMock(ResponseInterface::class);
            $mockResponse->method('getStatusCode')->willReturn($tmdbStatusCode);
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
            
            // Verify status code is forwarded
            if ($response->getStatusCode() !== $tmdbStatusCode) {
                $failedCases[] = [
                    'iteration' => $i,
                    'path' => $path,
                    'expectedStatus' => $tmdbStatusCode,
                    'actualStatus' => $response->getStatusCode(),
                    'reason' => '2xx status code not forwarded correctly'
                ];
            }
            
            // Verify response body is identical
            $responseData = json_decode($response->getContent(), true);
            
            // Use JSON comparison for deep equality check
            if (json_encode($responseData) !== json_encode($tmdbResponseData)) {
                $failedCases[] = [
                    'iteration' => $i,
                    'path' => $path,
                    'statusCode' => $tmdbStatusCode,
                    'expectedBody' => $tmdbResponseData,
                    'actualBody' => $responseData,
                    'reason' => 'Response body not identical'
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
     * Generate random successful TMDb response data
     * 
     * @return array<string, mixed>
     */
    private function generateRandomSuccessfulResponse(): array
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
                'vote_count' => rand(0, 50000),
                'poster_path' => '/random_poster_' . rand(1, 1000) . '.jpg',
                'backdrop_path' => '/random_backdrop_' . rand(1, 1000) . '.jpg',
                'adult' => (bool) rand(0, 1),
                'original_language' => $this->generateRandomLanguageCode(),
                'genre_ids' => $this->generateRandomGenreIds()
            ],
            // Genre list response
            [
                'genres' => $this->generateRandomGenres()
            ],
            // Empty results response
            [
                'page' => 1,
                'results' => [],
                'total_pages' => 0,
                'total_results' => 0
            ],
            // Trending response
            [
                'page' => 1,
                'results' => $this->generateRandomResults(),
                'total_pages' => rand(1, 100),
                'total_results' => rand(0, 2000)
            ]
        ];
        
        return $responseTypes[array_rand($responseTypes)];
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
                'vote_average' => round(rand(0, 100) / 10, 1),
                'vote_count' => rand(0, 10000),
                'release_date' => $this->generateRandomDate(),
                'adult' => (bool) rand(0, 1)
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
        $languages = ['en', 'fr', 'es', 'de', 'it', 'ja', 'ko', 'pt', 'ru', 'zh', 'ar', 'hi', 'nl'];
        $count = rand(5, count($languages));
        
        shuffle($languages);
        return array_slice($languages, 0, $count);
    }

    /**
     * Generate random genres array
     * 
     * @return array<int, array<string, mixed>>
     */
    private function generateRandomGenres(): array
    {
        $genreNames = ['Action', 'Comedy', 'Drama', 'Horror', 'Thriller', 'Romance', 'Sci-Fi', 'Fantasy'];
        $count = rand(3, count($genreNames));
        $genres = [];
        
        for ($i = 0; $i < $count; $i++) {
            $genres[] = [
                'id' => rand(1, 100),
                'name' => $genreNames[array_rand($genreNames)]
            ];
        }
        
        return $genres;
    }

    /**
     * Generate random genre IDs
     * 
     * @return array<int, int>
     */
    private function generateRandomGenreIds(): array
    {
        $count = rand(1, 5);
        $ids = [];
        
        for ($i = 0; $i < $count; $i++) {
            $ids[] = rand(1, 100);
        }
        
        return $ids;
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

    /**
     * Generate a random language code
     * 
     * @return string
     */
    private function generateRandomLanguageCode(): string
    {
        $languages = ['en', 'fr', 'es', 'de', 'it', 'ja', 'ko', 'pt', 'ru', 'zh'];
        
        return $languages[array_rand($languages)];
    }
}
