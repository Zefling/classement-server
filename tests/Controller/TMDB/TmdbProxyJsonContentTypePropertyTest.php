<?php

namespace App\Tests\Controller\TMDB;

use App\Controller\TmdbProxyController;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Property-based test that verifies for ANY successful response returned
 * to the client, the response SHALL include a Content-Type header with
 * the value "application/json".
 */
class TmdbProxyJsonContentTypePropertyTest extends KernelTestCase
{
    private const ITERATIONS = 100;

    /**
     * Property test: JSON content type
     *
     * This test generates random successful responses and verifies that ALL
     * responses include the Content-Type: application/json header.
     *
     * Runs 100 iterations with randomly generated responses to verify the property
     * holds across all valid executions.
     */
    public function testSuccessfulResponsesHaveJsonContentType(): void
    {
        $failedCases = [];

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Generate random path
            $path = $this->generateRandomPath();

            // Generate random successful TMDb response
            $tmdbResponseData = $this->generateRandomSuccessfulResponse();

            // Create mock response
            $mockResponse = $this->createMock(ResponseInterface::class);
            $mockResponse->method('getStatusCode')->willReturn(200);
            $mockResponse->method('toArray')->willReturn($tmdbResponseData);

            // Create mock HTTP client
            $mockClient = $this->createMock(HttpClientInterface::class);
            $mockClient->method('request')->willReturn($mockResponse);

            // Create mock logger
            $mockLogger = $this->createMock(LoggerInterface::class);

            // Create mock logger
            $mockCache = $this->createMock(CacheInterface::class);

            // Create controller
            $controller = new TmdbProxyController($mockClient, $mockLogger, $mockCache);

            // Create request
            $request = Request::create('/api/tmdb/' . $path, 'GET');

            // Execute
            $response = $controller->proxy($path, $request);

            // Verify Content-Type header is present
            if (!$response->headers->has('Content-Type')) {
                $failedCases[] = [
                    'iteration' => $i,
                    'path' => $path,
                    'reason' => 'Content-Type header is missing'
                ];
                continue;
            }

            // Get Content-Type header value
            $contentType = $response->headers->get('Content-Type');

            // Verify Content-Type is application/json (may include charset)
            if (!str_starts_with($contentType, 'application/json')) {
                $failedCases[] = [
                    'iteration' => $i,
                    'path' => $path,
                    'expectedContentType' => 'application/json',
                    'actualContentType' => $contentType,
                    'reason' => 'Content-Type is not application/json'
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
     * Property test: JSON content type for various response types
     *
     * This test verifies that different types of successful responses
     * (search, configuration, movie details, etc.) all have the correct
     * Content-Type header.
     */
    public function testVariousResponseTypesHaveJsonContentType(): void
    {
        $failedCases = [];

        $responseTypes = [
            'search' => $this->generateSearchResponse(),
            'configuration' => $this->generateConfigurationResponse(),
            'movie_details' => $this->generateMovieDetailsResponse(),
            'genre_list' => $this->generateGenreListResponse(),
            'empty_results' => $this->generateEmptyResultsResponse(),
            'trending' => $this->generateTrendingResponse(),
        ];

        foreach ($responseTypes as $type => $responseData) {
            for ($i = 0; $i < 10; $i++) {
                // Generate random path
                $path = $this->generateRandomPath();

                // Create mock response
                $mockResponse = $this->createMock(ResponseInterface::class);
                $mockResponse->method('getStatusCode')->willReturn(200);
                $mockResponse->method('toArray')->willReturn($responseData);

                // Create mock HTTP client
                $mockClient = $this->createMock(HttpClientInterface::class);
                $mockClient->method('request')->willReturn($mockResponse);

                // Create mock logger
                $mockLogger = $this->createMock(LoggerInterface::class);

                // Create mock logger
                $mockCache = $this->createMock(CacheInterface::class);

                // Create controller
                $controller = new TmdbProxyController($mockClient, $mockLogger, $mockCache);

                // Create request
                $request = Request::create('/api/tmdb/' . $path, 'GET');

                // Execute
                $response = $controller->proxy($path, $request);

                // Verify Content-Type header
                $contentType = $response->headers->get('Content-Type');

                if (!str_starts_with($contentType, 'application/json')) {
                    $failedCases[] = [
                        'responseType' => $type,
                        'iteration' => $i,
                        'path' => $path,
                        'expectedContentType' => 'application/json',
                        'actualContentType' => $contentType,
                        'reason' => "Content-Type is not application/json for {$type} response"
                    ];
                }
            }
        }

        // Assert no failures occurred
        $this->assertEmpty(
            $failedCases,
            "Property test failed for " . count($failedCases) . " cases:\n" .
                json_encode($failedCases, JSON_PRETTY_PRINT)
        );
    }

    /**
     * Property test: JSON content type for empty responses
     *
     * This test verifies that even empty or minimal responses have the
     * correct Content-Type header.
     */
    public function testEmptyResponsesHaveJsonContentType(): void
    {
        $failedCases = [];

        for ($i = 0; $i < 20; $i++) {
            // Generate random path
            $path = $this->generateRandomPath();

            // Create empty response
            $emptyResponseData = [];

            // Create mock response
            $mockResponse = $this->createMock(ResponseInterface::class);
            $mockResponse->method('getStatusCode')->willReturn(200);
            $mockResponse->method('toArray')->willReturn($emptyResponseData);

            // Create mock HTTP client
            $mockClient = $this->createMock(HttpClientInterface::class);
            $mockClient->method('request')->willReturn($mockResponse);

            // Create mock logger
            $mockLogger = $this->createMock(LoggerInterface::class);

            // Create mock logger
            $mockCache = $this->createMock(CacheInterface::class);

            // Create controller
            $controller = new TmdbProxyController($mockClient, $mockLogger, $mockCache);

            // Create request
            $request = Request::create('/api/tmdb/' . $path, 'GET');

            // Execute
            $response = $controller->proxy($path, $request);

            // Verify Content-Type header
            $contentType = $response->headers->get('Content-Type');

            if (!str_starts_with($contentType, 'application/json')) {
                $failedCases[] = [
                    'iteration' => $i,
                    'path' => $path,
                    'expectedContentType' => 'application/json',
                    'actualContentType' => $contentType,
                    'reason' => 'Content-Type is not application/json for empty response'
                ];
            }
        }

        // Assert no failures occurred
        $this->assertEmpty(
            $failedCases,
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
            $this->generateSearchResponse(),
            $this->generateConfigurationResponse(),
            $this->generateMovieDetailsResponse(),
            $this->generateGenreListResponse(),
            $this->generateEmptyResultsResponse(),
            $this->generateTrendingResponse(),
        ];

        return $responseTypes[array_rand($responseTypes)];
    }

    /**
     * Generate search response
     *
     * @return array<string, mixed>
     */
    private function generateSearchResponse(): array
    {
        return [
            'page' => rand(1, 100),
            'results' => $this->generateRandomResults(),
            'total_pages' => rand(1, 500),
            'total_results' => rand(0, 10000)
        ];
    }

    /**
     * Generate configuration response
     *
     * @return array<string, mixed>
     */
    private function generateConfigurationResponse(): array
    {
        return [
            'languages' => $this->generateRandomLanguages()
        ];
    }

    /**
     * Generate movie details response
     *
     * @return array<string, mixed>
     */
    private function generateMovieDetailsResponse(): array
    {
        return [
            'id' => rand(1, 100000),
            'title' => $this->generateRandomString(5, 50),
            'overview' => $this->generateRandomString(50, 200),
            'release_date' => $this->generateRandomDate(),
            'vote_average' => round(rand(0, 100) / 10, 1),
            'vote_count' => rand(0, 50000),
            'poster_path' => '/random_poster_' . rand(1, 1000) . '.jpg',
            'backdrop_path' => '/random_backdrop_' . rand(1, 1000) . '.jpg',
        ];
    }

    /**
     * Generate genre list response
     *
     * @return array<string, mixed>
     */
    private function generateGenreListResponse(): array
    {
        return [
            'genres' => $this->generateRandomGenres()
        ];
    }

    /**
     * Generate empty results response
     *
     * @return array<string, mixed>
     */
    private function generateEmptyResultsResponse(): array
    {
        return [
            'page' => 1,
            'results' => [],
            'total_pages' => 0,
            'total_results' => 0
        ];
    }

    /**
     * Generate trending response
     *
     * @return array<string, mixed>
     */
    private function generateTrendingResponse(): array
    {
        return [
            'page' => 1,
            'results' => $this->generateRandomResults(),
            'total_pages' => rand(1, 100),
            'total_results' => rand(0, 2000)
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
                'vote_average' => round(rand(0, 100) / 10, 1),
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
     * Generate random genres array
     *
     * @return array<int, array<string, mixed>>
     */
    private function generateRandomGenres(): array
    {
        $genreNames = ['Action', 'Comedy', 'Drama', 'Horror', 'Thriller', 'Romance'];
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
