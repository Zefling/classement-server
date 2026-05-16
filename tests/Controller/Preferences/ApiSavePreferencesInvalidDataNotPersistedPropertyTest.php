<?php

namespace App\Tests\Controller\Preferences;

use App\Entity\Preferences;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * For any invalid preference data, when validation fails, the data SHALL NOT
 * be stored in the database.
 *
 * This property ensures that validation acts as a proper gate, preventing any
 * invalid data from reaching the database.
 */
class ApiSavePreferencesInvalidDataNotPersistedPropertyTest extends WebTestCase
{
    private $testToken;
    /**
     * Property test: Invalid data not persisted
     *
     * This test generates random invalid preference datasets, attempts to save them,
     * and verifies that:
     * 1. The save operation returns 400 Bad Request
     * 2. No Preferences record is created in the database
     *
     * Runs multiple iterations with different types of invalid data to verify
     * the property holds across all invalid executions.
     *
     * @dataProvider invalidPreferencesDataProvider
     */
    public function testInvalidDataNotPersisted(array $invalidPreferences, string $reason): void
    {
        $client = static::createClient();

        // Create a test user and get authentication token
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Create test user
        $user = new User();
        $user->setUsername('test_user_' . uniqid());
        $user->setEmail('test_' . uniqid() . '@example.com');
        $user->setPassword('hashed_password');
        $user->setRoles(['ROLE_USER']);
        $user->setDateCreate(new \DateTimeImmutable());
        $user->setIsValidated(true);
        $user->setAvatar(false);
        $user->setDeleted(false);

        $entityManager->persist($user);
        $entityManager->flush();

        // Create API token for the user
        $token = new \App\Entity\Token($user, new \DateInterval('P1D'), 'login');
        $entityManager->persist($token);
        $entityManager->flush();

        $this->testToken = $token->getToken();

        try {
            // Verify no preferences exist before the test
            $preferencesRepo = $entityManager->getRepository(Preferences::class);
            $preferencesBeforeSave = $preferencesRepo->findByUser($user);
            $this->assertNull($preferencesBeforeSave, 'No preferences should exist before save attempt');

            // Attempt to save invalid preferences
            $client->request(
                'POST',
                '/api/preferences',
                [],
                [],
                [
                    'HTTP_X_AUTH_TOKEN' => $this->testToken,
                    'CONTENT_TYPE' => 'application/json',
                ],
                json_encode($invalidPreferences)
            );

            $response = $client->getResponse();

            // Verify save operation failed with 400 Bad Request
            $this->assertEquals(
                400,
                $response->getStatusCode(),
                "Save operation should return 400 Bad Request for invalid data ($reason). Response: " . $response->getContent()
            );

            // Verify the response contains error information
            $responseData = json_decode($response->getContent(), true);
            $this->assertArrayHasKey('status', $responseData, 'Response should contain status field');
            $this->assertEquals('KO', $responseData['status'], 'Status should be KO for validation error');
            $this->assertArrayHasKey('errorCode', $responseData, 'Response should contain errorCode field');
            $this->assertArrayHasKey('errorMessage', $responseData, 'Response should contain errorMessage field');

            // Verify no preferences were persisted in the database
            $entityManager->clear(); // Clear entity manager to force fresh query
            $preferencesAfterSave = $preferencesRepo->findByUser($user);

            $this->assertNull(
                $preferencesAfterSave,
                "No preferences should be persisted after failed validation ($reason)"
            );
        } finally {
            // Cleanup: Remove test user (preferences should not exist)
            $entityManager->clear(); // Clear to get fresh entity manager
            $entityManager = static::getContainer()->get('doctrine')->getManager();

            $user = $entityManager->find(User::class, $user->getId());
            if ($user) {
                $preferencesRepo = $entityManager->getRepository(Preferences::class);
                $userPreferences = $preferencesRepo->findByUser($user);
                if ($userPreferences) {
                    $entityManager->remove($userPreferences);
                }
                $entityManager->remove($user);
                $entityManager->flush();
            }
        }
    }

    /**
     * Générateur de données de préférences invalides.
     * Génère différents types de données invalides pour tester la validation.
     *
     * Note: All fields are optional, so missing fields are valid. These tests
     * focus on type errors, range violations, and invalid enum values.
     *
     * @return \Generator
     */
    public static function invalidPreferencesDataProvider(): \Generator
    {
        // Wrong type: nameCopy should be boolean
        yield 'Wrong type for nameCopy (string instead of boolean)' => [
            [
                'nameCopy' => 'true',
                'newColor' => 'mixed',
                'newLine' => 'below',
                'lineOption' => 'auto',
                'mode' => 'default',
                'autoResize' => '300×300',
                'pageSize' => 10,
                'mainMenuReduce' => false,
                'emojiList' => [],
                'zoomMobile' => 100,
                'adult' => false,
                'advancedOptions' => false,
                'advancedFork' => false,
                'authApiKeys' => ['tmdb' => 'test'],
                'api' => ['anilist' => false]
            ],
            'nameCopy wrong type'
        ];

        // Wrong type: pageSize should be integer
        yield 'Wrong type for pageSize (string instead of integer)' => [
            [
                'nameCopy' => true,
                'newColor' => 'mixed',
                'newLine' => 'below',
                'lineOption' => 'auto',
                'mode' => 'default',
                'autoResize' => '300×300',
                'pageSize' => '10',
                'mainMenuReduce' => false,
                'emojiList' => [],
                'zoomMobile' => 100,
                'adult' => false,
                'advancedOptions' => false,
                'advancedFork' => false,
                'authApiKeys' => ['tmdb' => 'test'],
                'api' => ['anilist' => false]
            ],
            'pageSize wrong type'
        ];

        // Out of range: pageSize minimum is 1
        yield 'Out of range pageSize (0, minimum is 1)' => [
            [
                'nameCopy' => true,
                'newColor' => 'mixed',
                'newLine' => 'below',
                'lineOption' => 'auto',
                'mode' => 'default',
                'autoResize' => '300×300',
                'pageSize' => 0,
                'mainMenuReduce' => false,
                'emojiList' => [],
                'zoomMobile' => 100,
                'adult' => false,
                'advancedOptions' => false,
                'advancedFork' => false,
                'authApiKeys' => ['tmdb' => 'test'],
                'api' => ['anilist' => false]
            ],
            'pageSize out of range (too low)'
        ];

        // Out of range: zoomMobile minimum is 50
        yield 'Out of range zoomMobile (49, minimum is 50)' => [
            [
                'nameCopy' => true,
                'newColor' => 'mixed',
                'newLine' => 'below',
                'lineOption' => 'auto',
                'mode' => 'default',
                'autoResize' => '300×300',
                'pageSize' => 10,
                'mainMenuReduce' => false,
                'emojiList' => [],
                'zoomMobile' => 49,
                'adult' => false,
                'advancedOptions' => false,
                'advancedFork' => false,
                'authApiKeys' => ['tmdb' => 'test'],
                'api' => ['anilist' => false]
            ],
            'zoomMobile out of range (too low)'
        ];

        // Out of range: zoomMobile maximum is 200
        yield 'Out of range zoomMobile (201, maximum is 200)' => [
            [
                'nameCopy' => true,
                'newColor' => 'mixed',
                'newLine' => 'below',
                'lineOption' => 'auto',
                'mode' => 'default',
                'autoResize' => '300×300',
                'pageSize' => 10,
                'mainMenuReduce' => false,
                'emojiList' => [],
                'zoomMobile' => 201,
                'adult' => false,
                'advancedOptions' => false,
                'advancedFork' => false,
                'authApiKeys' => ['tmdb' => 'test'],
                'api' => ['anilist' => false]
            ],
            'zoomMobile out of range (too high)'
        ];

        // Invalid enum: newColor should be 'mixed' or 'same'
        yield 'Invalid enum for newColor' => [
            [
                'nameCopy' => true,
                'newColor' => 'invalid',
                'newLine' => 'below',
                'lineOption' => 'auto',
                'mode' => 'default',
                'autoResize' => '300×300',
                'pageSize' => 10,
                'mainMenuReduce' => false,
                'emojiList' => [],
                'zoomMobile' => 100,
                'adult' => false,
                'advancedOptions' => false,
                'advancedFork' => false,
                'authApiKeys' => ['tmdb' => 'test'],
                'api' => ['anilist' => false]
            ],
            'newColor invalid enum'
        ];

        // Invalid enum: newLine should be 'below', 'above', or 'ask-me'
        yield 'Invalid enum for newLine' => [
            [
                'nameCopy' => true,
                'newColor' => 'mixed',
                'newLine' => 'invalid',
                'lineOption' => 'auto',
                'mode' => 'default',
                'autoResize' => '300×300',
                'pageSize' => 10,
                'mainMenuReduce' => false,
                'emojiList' => [],
                'zoomMobile' => 100,
                'adult' => false,
                'advancedOptions' => false,
                'advancedFork' => false,
                'authApiKeys' => ['tmdb' => 'test'],
                'api' => ['anilist' => false]
            ],
            'newLine invalid enum'
        ];

        // Invalid enum: lineOption should be 'auto', 'reduce', or 'hidden'
        yield 'Invalid enum for lineOption' => [
            [
                'nameCopy' => true,
                'newColor' => 'mixed',
                'newLine' => 'below',
                'lineOption' => 'invalid',
                'mode' => 'default',
                'autoResize' => '300×300',
                'pageSize' => 10,
                'mainMenuReduce' => false,
                'emojiList' => [],
                'zoomMobile' => 100,
                'adult' => false,
                'advancedOptions' => false,
                'advancedFork' => false,
                'authApiKeys' => ['tmdb' => 'test'],
                'api' => ['anilist' => false]
            ],
            'lineOption invalid enum'
        ];

        // Invalid enum: autoResize should be '300×300', '500×500', or 'origin'
        yield 'Invalid enum for autoResize' => [
            [
                'nameCopy' => true,
                'newColor' => 'mixed',
                'newLine' => 'below',
                'lineOption' => 'auto',
                'mode' => 'default',
                'autoResize' => '400×400',
                'pageSize' => 10,
                'mainMenuReduce' => false,
                'emojiList' => [],
                'zoomMobile' => 100,
                'adult' => false,
                'advancedOptions' => false,
                'advancedFork' => false,
                'authApiKeys' => ['tmdb' => 'test'],
                'api' => ['anilist' => false]
            ],
            'autoResize invalid enum'
        ];

        // Invalid nested object: authApiKeys.tmdb wrong type (should be string)
        yield 'Invalid nested object authApiKeys (tmdb wrong type)' => [
            [
                'nameCopy' => true,
                'newColor' => 'mixed',
                'newLine' => 'below',
                'lineOption' => 'auto',
                'mode' => 'default',
                'autoResize' => '300×300',
                'pageSize' => 10,
                'mainMenuReduce' => false,
                'emojiList' => [],
                'zoomMobile' => 100,
                'adult' => false,
                'advancedOptions' => false,
                'advancedFork' => false,
                'authApiKeys' => ['tmdb' => 123],
                'api' => ['anilist' => false]
            ],
            'authApiKeys.tmdb wrong type'
        ];

        // Invalid nested object: api.anilist wrong type (should be boolean)
        yield 'Invalid nested object api (anilist wrong type)' => [
            [
                'nameCopy' => true,
                'newColor' => 'mixed',
                'newLine' => 'below',
                'lineOption' => 'auto',
                'mode' => 'default',
                'autoResize' => '300×300',
                'pageSize' => 10,
                'mainMenuReduce' => false,
                'emojiList' => [],
                'zoomMobile' => 100,
                'adult' => false,
                'advancedOptions' => false,
                'advancedFork' => false,
                'authApiKeys' => ['tmdb' => 'test'],
                'api' => ['anilist' => 'true']
            ],
            'api.anilist wrong type'
        ];

        // Invalid array type: emojiList should be array of strings
        yield 'Invalid array type for emojiList (contains non-string)' => [
            [
                'nameCopy' => true,
                'newColor' => 'mixed',
                'newLine' => 'below',
                'lineOption' => 'auto',
                'mode' => 'default',
                'autoResize' => '300×300',
                'pageSize' => 10,
                'mainMenuReduce' => false,
                'emojiList' => ['😀', 123, '🎉'],
                'zoomMobile' => 100,
                'adult' => false,
                'advancedOptions' => false,
                'advancedFork' => false,
                'authApiKeys' => ['tmdb' => 'test'],
                'api' => ['anilist' => false]
            ],
            'emojiList contains non-string'
        ];

        // Wrong type: emojiList should be array
        yield 'Wrong type for emojiList (string instead of array)' => [
            [
                'nameCopy' => true,
                'newColor' => 'mixed',
                'newLine' => 'below',
                'lineOption' => 'auto',
                'mode' => 'default',
                'autoResize' => '300×300',
                'pageSize' => 10,
                'mainMenuReduce' => false,
                'emojiList' => 'not an array',
                'zoomMobile' => 100,
                'adult' => false,
                'advancedOptions' => false,
                'advancedFork' => false,
                'authApiKeys' => ['tmdb' => 'test'],
                'api' => ['anilist' => false]
            ],
            'emojiList wrong type'
        ];
    }
}
