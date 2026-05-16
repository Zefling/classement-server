<?php

namespace App\Tests\Controller\TMDB;

use App\Controller\TmdbProxyController;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Property-based test that verifies for ANY TMDb API response, the JSON data
 * structure returned to the client SHALL be identical to the JSON data structure
 * received from TMDb (no transformation, no additional fields, no removed fields).
 */
class TmdbProxyResponseDataPreservationPropertyTest extends KernelTestCase
{
    private const ITERATIONS = 100;

    /**
     * Property test: Response data preservation
     * 
     * This test generates random JSON structures simulating TMDb responses
     * and verifies that the proxy returns identical JSON structure (deep equality check).
     * 
     * Runs 100 iterations with randomly generated JSON structures to verify
     * the property holds across all valid executions.
     */
    public function testResponseDataPreservedWithoutModification(): void
    {
        $failedCases = [];
        
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Generate random path
            $path = $this->generateRandomPath();
            
            // Generate random TMDb response with complex structure
            $tmdbResponseData = $this->generateRandomComplexResponse();
            
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
            
            // Get response data
            $responseData = json_decode($response->getContent(), true);
            
            // Perform deep equality check
            $differences = $this->findDifferences($tmdbResponseData, $responseData);
            
            if (!empty($differences)) {
                $failedCases[] = [
                    'iteration' => $i,
                    'path' => $path,
                    'differences' => $differences,
                    'reason' => 'Response data structure not identical to TMDb response'
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
     * Property test: Nested structures are preserved
     * 
     * This test verifies that deeply nested JSON structures are preserved
     * without modification.
     */
    public function testNestedStructuresPreserved(): void
    {
        $failedCases = [];
        
        for ($i = 0; $i < 50; $i++) {
            // Generate random path
            $path = $this->generateRandomPath();
            
            // Generate deeply nested structure
            $tmdbResponseData = $this->generateDeeplyNestedResponse();
            
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
            
            // Get response data
            $responseData = json_decode($response->getContent(), true);
            
            // Perform deep equality check
            $differences = $this->findDifferences($tmdbResponseData, $responseData);
            
            if (!empty($differences)) {
                $failedCases[] = [
                    'iteration' => $i,
                    'path' => $path,
                    'differences' => $differences,
                    'reason' => 'Nested structures not preserved'
                ];
            }
        }
        
        // Assert no failures occurred
        $this->assertEmpty($failedCases, 
            "Property test failed for " . count($failedCases) . " out of 50 iterations:\n" .
            json_encode($failedCases, JSON_PRETTY_PRINT)
        );
    }

    /**
     * Property test: Edge case values are preserved
     * 
     * This test verifies that edge case values (null, empty strings, zero,
     * boolean false, etc.) are preserved correctly.
     */
    public function testEdgeCaseValuesPreserved(): void
    {
        $failedCases = [];
        
        $edgeCases = [
            'null_value' => ['value' => null],
            'empty_string' => ['value' => ''],
            'zero' => ['value' => 0],
            'false' => ['value' => false],
            'empty_array' => ['value' => []],
            'empty_object' => ['value' => new \stdClass()],
            'negative_number' => ['value' => -123],
            'float' => ['value' => 3.14159],
            'large_number' => ['value' => 9999999999],
            'unicode' => ['value' => 'Café Müller 日本語 🎬'],
        ];
        
        foreach ($edgeCases as $caseName => $caseData) {
            // Generate random path
            $path = $this->generateRandomPath();
            
            // Create mock response
            $mockResponse = $this->createMock(ResponseInterface::class);
            $mockResponse->method('getStatusCode')->willReturn(200);
            $mockResponse->method('toArray')->willReturn($caseData);
            
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
            
            // Get response data
            $responseData = json_decode($response->getContent(), true);
            
            // Perform deep equality check
            $differences = $this->findDifferences($caseData, $responseData);
            
            if (!empty($differences)) {
                $failedCases[] = [
                    'case' => $caseName,
                    'path' => $path,
                    'differences' => $differences,
                    'reason' => "Edge case value '{$caseName}' not preserved"
                ];
            }
        }
        
        // Assert no failures occurred
        $this->assertEmpty($failedCases, 
            "Property test failed for " . count($failedCases) . " edge cases:\n" .
            json_encode($failedCases, JSON_PRETTY_PRINT)
        );
    }

    /**
     * Find differences between two arrays (deep comparison)
     * 
     * This comparison is lenient about PHP JSON encoding quirks:
     * - Floats that are whole numbers (5.0) may become integers (5)
     * - Empty objects may become empty arrays
     * 
     * @param mixed $expected
     * @param mixed $actual
     * @param string $path
     * @return array<string, mixed>
     */
    private function findDifferences($expected, $actual, string $path = 'root'): array
    {
        $differences = [];
        
        // Check if types match (with leniency for JSON encoding quirks)
        $expectedType = gettype($expected);
        $actualType = gettype($actual);
        
        // Allow integer/double interchangeability (JSON encoding converts 5.0 to 5)
        if (($expectedType === 'double' && $actualType === 'integer') ||
            ($expectedType === 'integer' && $actualType === 'double')) {
            // Check if values are equal
            if ($expected != $actual) {
                $differences[] = [
                    'path' => $path,
                    'type' => 'value_mismatch',
                    'expected' => $expected,
                    'actual' => $actual
                ];
            }
            return $differences;
        }
        
        // Allow empty object/array interchangeability (JSON encoding converts {} to [])
        if (($expectedType === 'object' && $actualType === 'array' && empty($actual)) ||
            ($expectedType === 'array' && $actualType === 'object' && empty((array)$actual))) {
            return $differences; // Both are empty, consider them equal
        }
        
        if ($expectedType !== $actualType) {
            $differences[] = [
                'path' => $path,
                'type' => 'type_mismatch',
                'expected_type' => $expectedType,
                'actual_type' => $actualType
            ];
            return $differences;
        }
        
        // Handle arrays
        if (is_array($expected)) {
            // Check if keys match
            $expectedKeys = array_keys($expected);
            $actualKeys = array_keys($actual);
            
            $missingKeys = array_diff($expectedKeys, $actualKeys);
            $extraKeys = array_diff($actualKeys, $expectedKeys);
            
            if (!empty($missingKeys)) {
                $differences[] = [
                    'path' => $path,
                    'type' => 'missing_keys',
                    'keys' => $missingKeys
                ];
            }
            
            if (!empty($extraKeys)) {
                $differences[] = [
                    'path' => $path,
                    'type' => 'extra_keys',
                    'keys' => $extraKeys
                ];
            }
            
            // Recursively check values
            foreach ($expectedKeys as $key) {
                if (array_key_exists($key, $actual)) {
                    $subDifferences = $this->findDifferences(
                        $expected[$key],
                        $actual[$key],
                        $path . '.' . $key
                    );
                    $differences = array_merge($differences, $subDifferences);
                }
            }
        } elseif ($expected !== $actual) {
            // For scalar values, check equality
            $differences[] = [
                'path' => $path,
                'type' => 'value_mismatch',
                'expected' => $expected,
                'actual' => $actual
            ];
        }
        
        return $differences;
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
            'discover/movie',
            'trending/movie/day',
        ];
        
        return $paths[array_rand($paths)];
    }

    /**
     * Generate random complex response with various data types
     * 
     * @return array<string, mixed>
     */
    private function generateRandomComplexResponse(): array
    {
        $responseTypes = [
            // Search response with nested results
            [
                'page' => rand(1, 100),
                'results' => $this->generateRandomResults(),
                'total_pages' => rand(1, 500),
                'total_results' => rand(0, 10000),
                'dates' => [
                    'minimum' => $this->generateRandomDate(),
                    'maximum' => $this->generateRandomDate()
                ]
            ],
            // Movie details with nested objects
            [
                'id' => rand(1, 100000),
                'title' => $this->generateRandomString(5, 50),
                'overview' => $this->generateRandomString(50, 200),
                'release_date' => $this->generateRandomDate(),
                'vote_average' => round(rand(0, 100) / 10, 1),
                'vote_count' => rand(0, 50000),
                'genres' => $this->generateRandomGenres(),
                'production_companies' => $this->generateRandomCompanies(),
                'spoken_languages' => $this->generateRandomSpokenLanguages(),
                'belongs_to_collection' => rand(0, 1) ? [
                    'id' => rand(1, 10000),
                    'name' => $this->generateRandomString(10, 30),
                    'poster_path' => '/collection_' . rand(1, 100) . '.jpg'
                ] : null
            ],
            // Configuration with arrays
            [
                'images' => [
                    'base_url' => 'https://image.tmdb.org/t/p/',
                    'secure_base_url' => 'https://image.tmdb.org/t/p/',
                    'backdrop_sizes' => ['w300', 'w780', 'w1280', 'original'],
                    'logo_sizes' => ['w45', 'w92', 'w154', 'w185', 'w300', 'w500', 'original'],
                    'poster_sizes' => ['w92', 'w154', 'w185', 'w342', 'w500', 'w780', 'original']
                ],
                'change_keys' => $this->generateRandomChangeKeys()
            ]
        ];
        
        return $responseTypes[array_rand($responseTypes)];
    }

    /**
     * Generate deeply nested response structure
     * 
     * @return array<string, mixed>
     */
    private function generateDeeplyNestedResponse(): array
    {
        return [
            'level1' => [
                'level2' => [
                    'level3' => [
                        'level4' => [
                            'level5' => [
                                'value' => $this->generateRandomString(10, 20),
                                'number' => rand(1, 1000),
                                'array' => [1, 2, 3, 4, 5],
                                'nested_object' => [
                                    'key1' => 'value1',
                                    'key2' => 'value2',
                                    'key3' => [
                                        'subkey1' => 'subvalue1',
                                        'subkey2' => rand(1, 100)
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'parallel_branch' => [
                'data' => $this->generateRandomResults()
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
        $count = rand(1, 10);
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
                'adult' => (bool) rand(0, 1),
                'genre_ids' => $this->generateRandomGenreIds()
            ];
        }
        
        return $results;
    }

    /**
     * Generate random genres array
     * 
     * @return array<int, array<string, mixed>>
     */
    private function generateRandomGenres(): array
    {
        $genreNames = ['Action', 'Comedy', 'Drama', 'Horror', 'Thriller', 'Romance', 'Sci-Fi'];
        $count = rand(1, 5);
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
     * Generate random companies array
     * 
     * @return array<int, array<string, mixed>>
     */
    private function generateRandomCompanies(): array
    {
        $count = rand(1, 5);
        $companies = [];
        
        for ($i = 0; $i < $count; $i++) {
            $companies[] = [
                'id' => rand(1, 10000),
                'name' => $this->generateRandomString(5, 30),
                'logo_path' => rand(0, 1) ? '/logo_' . rand(1, 100) . '.png' : null,
                'origin_country' => $this->generateRandomCountryCode()
            ];
        }
        
        return $companies;
    }

    /**
     * Generate random spoken languages array
     * 
     * @return array<int, array<string, mixed>>
     */
    private function generateRandomSpokenLanguages(): array
    {
        $languages = [
            ['iso_639_1' => 'en', 'name' => 'English'],
            ['iso_639_1' => 'fr', 'name' => 'Français'],
            ['iso_639_1' => 'es', 'name' => 'Español'],
            ['iso_639_1' => 'de', 'name' => 'Deutsch'],
            ['iso_639_1' => 'ja', 'name' => '日本語'],
        ];
        
        $count = rand(1, 3);
        shuffle($languages);
        
        return array_slice($languages, 0, $count);
    }

    /**
     * Generate random change keys array
     * 
     * @return array<int, string>
     */
    private function generateRandomChangeKeys(): array
    {
        $keys = ['adult', 'air_date', 'also_known_as', 'alternative_titles', 'biography', 'birthday'];
        $count = rand(3, count($keys));
        
        shuffle($keys);
        return array_slice($keys, 0, $count);
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
     * Generate a random country code
     * 
     * @return string
     */
    private function generateRandomCountryCode(): string
    {
        $codes = ['US', 'FR', 'GB', 'DE', 'JP', 'KR', 'CN', 'IT', 'ES', 'CA'];
        
        return $codes[array_rand($codes)];
    }
}
