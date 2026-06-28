<?php

namespace App\Tests\Controller\Preferences;

use App\Entity\Preferences;
use App\Entity\Token;
use App\Entity\User;
use App\Enum\CodeError;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Unit tests for ApiSavePreferencesController
 *
 * Tests authentication, validation, and persistence scenarios for the
 * preferences save endpoint.
 */
class ApiSavePreferencesControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $testUser;
    private $testToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $container = static::getContainer();
        $this->entityManager = $container->get('doctrine')->getManager();

        // Create a test user for authenticated tests
        $this->testUser = new User();
        $this->testUser->setUsername('test_user_' . uniqid());
        $this->testUser->setEmail('test_' . uniqid() . '@example.com');
        $this->testUser->setPassword('hashed_password');
        $this->testUser->setRoles(['ROLE_USER']);
        $this->testUser->setDateCreate(new \DateTimeImmutable());
        $this->testUser->setIsValidated(true);
        $this->testUser->setAvatar(false);
        $this->testUser->setDeleted(false);

        $this->entityManager->persist($this->testUser);
        $this->entityManager->flush();

        // Create API token for the user
        $token = new Token($this->testUser, new \DateInterval('P1D'), 'login');
        $this->entityManager->persist($token);
        $this->entityManager->flush();

        $this->testToken = $token->getToken();
    }

    protected function tearDown(): void
    {
        // Cleanup: Remove test user and preferences
        if ($this->testUser) {
            // Refresh the entity manager to avoid detached entity issues
            $this->entityManager = static::getContainer()->get('doctrine')->getManager();

            // Find the user again to ensure it's managed
            $user = $this->entityManager->find(User::class, $this->testUser->getId());

            if ($user) {
                $preferencesRepo = $this->entityManager->getRepository(Preferences::class);
                $userPreferences = $preferencesRepo->findByUser($user);
                if ($userPreferences) {
                    $this->entityManager->remove($userPreferences);
                }
                $this->entityManager->remove($user);
                $this->entityManager->flush();
            }
        }

        parent::tearDown();
    }

    /**
     * Test: requête sans token → 401 Unauthorized
     *
     * When an unauthenticated request is received, the controller SHALL
     * return a 401 Unauthorized response.
     */
    public function testSavePreferencesWithoutToken(): void
    {
        $validPreferences = $this->getValidPreferencesData();

        $this->client->request(
            'POST',
            '/api/preferences',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode($validPreferences)
        );

        $response = $this->client->getResponse();

        $this->assertEquals(
            401,
            $response->getStatusCode(),
            'Request without token should return 401 Unauthorized'
        );

        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals('KO', $responseData['status']);
        // The authentication system returns INVALID_TOKEN when no token is provided
        $this->assertEquals(CodeError::INVALID_TOKEN->value, $responseData['errorCode']);
    }

    /**
     * Test: données avec champs partiels → 200 OK
     *
     * When saving partial preferences (subset of fields), the controller
     * SHALL accept the data since all fields are optional.
     */
    public function testSavePreferencesWithPartialData(): void
    {
        $partialPreferences = [
            'interfaceLanguage' => 'en',
            'pageSize' => 50
        ];

        $this->client->request(
            'POST',
            '/api/preferences',
            [],
            [],
            [
                'HTTP_X_AUTH_TOKEN' => $this->testToken,
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode($partialPreferences)
        );

        $response = $this->client->getResponse();

        $this->assertEquals(
            200,
            $response->getStatusCode(),
            'Request with partial preferences should return 200 OK. Response: ' . $response->getContent()
        );

        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals('OK', $responseData['status']);
        $this->assertStringContainsString('saved successfully', $responseData['message']);
    }

    /**
     * Test: données avec type incorrect → 400 Bad Request
     *
     * When validation fails due to incorrect data type, the controller
     * SHALL return a 400 Bad Request response with validation errors.
     */
    public function testSavePreferencesWithIncorrectType(): void
    {
        $invalidPreferences = $this->getValidPreferencesData();
        // Change boolean to string (incorrect type)
        $invalidPreferences['nameCopy'] = 'true'; // should be boolean

        $this->client->request(
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

        $response = $this->client->getResponse();

        $this->assertEquals(
            400,
            $response->getStatusCode(),
            'Request with incorrect type should return 400 Bad Request'
        );

        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals('KO', $responseData['status']);
        $this->assertEquals(CodeError::INVALID_DATA->value, $responseData['errorCode']);
        $this->assertStringContainsString('JsonSchema Error', $responseData['errorMessage']);
    }

    /**
     * Test: données avec valeur hors limites → 400 Bad Request
     *
     * When validation fails due to out-of-range value, the controller
     * SHALL return a 400 Bad Request response with validation errors.
     */
    public function testSavePreferencesWithOutOfRangeValue(): void
    {
        $invalidPreferences = $this->getValidPreferencesData();
        // Set zoomMobile to value below minimum (should be 50-200)
        $invalidPreferences['zoomMobile'] = 0;

        $this->client->request(
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

        $response = $this->client->getResponse();

        $this->assertEquals(
            400,
            $response->getStatusCode(),
            'Request with out-of-range value should return 400 Bad Request'
        );

        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals('KO', $responseData['status']);
        $this->assertEquals(CodeError::INVALID_DATA->value, $responseData['errorCode']);
        $this->assertStringContainsString('JsonSchema Error', $responseData['errorMessage']);
    }

    /**
     * Test: première sauvegarde crée une nouvelle entité
     *
     * When preferences do not exist for the user, the controller SHALL
     * create a new record.
     */
    public function testFirstSaveCreatesNewEntity(): void
    {
        $validPreferences = $this->getValidPreferencesData();

        // Verify no preferences exist before save
        $preferencesRepo = $this->entityManager->getRepository(Preferences::class);
        $existingPreferences = $preferencesRepo->findByUser($this->testUser);
        $this->assertNull($existingPreferences, 'No preferences should exist before first save');

        // Save preferences
        $this->client->request(
            'POST',
            '/api/preferences',
            [],
            [],
            [
                'HTTP_X_AUTH_TOKEN' => $this->testToken,
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode($validPreferences)
        );

        $response = $this->client->getResponse();

        $this->assertEquals(
            200,
            $response->getStatusCode(),
            'First save should return 200 OK. Response: ' . $response->getContent()
        );

        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals('OK', $responseData['status']);
        $this->assertStringContainsString('saved successfully', $responseData['message']);

        // Verify a new entity was created
        $savedPreferences = $preferencesRepo->findByUser($this->testUser);
        $this->assertNotNull($savedPreferences, 'Preferences entity should be created');
        $this->assertEquals($this->testUser->getId(), $savedPreferences->getUser()->getId());
        $this->assertNotNull($savedPreferences->getDateCreate());
        $this->assertNull($savedPreferences->getDateChange(), 'dateChange should be null on first save');
    }

    /**
     * Test: deuxième sauvegarde met à jour l'entité existante
     *
     * When preferences already exist for the user, the controller SHALL
     * update the existing record.
     */
    public function testSecondSaveUpdatesExistingEntity(): void
    {
        $validPreferences = $this->getValidPreferencesData();

        // First save
        $this->client->request(
            'POST',
            '/api/preferences',
            [],
            [],
            [
                'HTTP_X_AUTH_TOKEN' => $this->testToken,
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode($validPreferences)
        );

        $firstResponse = $this->client->getResponse();
        $this->assertEquals(200, $firstResponse->getStatusCode());

        // Get the created entity
        $preferencesRepo = $this->entityManager->getRepository(Preferences::class);
        $firstSavedPreferences = $preferencesRepo->findByUser($this->testUser);
        $firstId = $firstSavedPreferences->getId();
        $firstDateCreate = $firstSavedPreferences->getDateCreate();

        // Wait a moment to ensure dateChange is different
        sleep(1);

        // Modify preferences and save again
        $modifiedPreferences = $validPreferences;
        $modifiedPreferences['pageSize'] = 50; // Change a value

        $this->client->request(
            'POST',
            '/api/preferences',
            [],
            [],
            [
                'HTTP_X_AUTH_TOKEN' => $this->testToken,
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode($modifiedPreferences)
        );

        $secondResponse = $this->client->getResponse();

        $this->assertEquals(
            200,
            $secondResponse->getStatusCode(),
            'Second save should return 200 OK. Response: ' . $secondResponse->getContent()
        );

        $responseData = json_decode($secondResponse->getContent(), true);

        $this->assertEquals('OK', $responseData['status']);

        // Verify the same entity was updated (not a new one created)
        $this->entityManager->clear(); // Clear to force fresh fetch
        $updatedPreferences = $preferencesRepo->findByUser($this->testUser);

        $this->assertNotNull($updatedPreferences, 'Preferences entity should still exist');
        $this->assertEquals($firstId, $updatedPreferences->getId(), 'Should be the same entity (same ID)');
        // Compare timestamps without microseconds (database doesn't store microseconds)
        $this->assertEquals(
            $firstDateCreate->getTimestamp(),
            $updatedPreferences->getDateCreate()->getTimestamp(),
            'dateCreate should not change'
        );
        $this->assertNotNull($updatedPreferences->getDateChange(), 'dateChange should be set on update');

        // Verify only one record exists
        $allPreferences = $preferencesRepo->findBy(['user' => $this->testUser]);
        $this->assertCount(1, $allPreferences, 'Only one Preferences record should exist');
    }

    /**
     * Helper method to generate valid preferences data
     *
     * @return array
     */
    private function getValidPreferencesData(): array
    {
        return [
            'interfaceLanguage' => 'fr',
            'interfaceTheme' => 'dark',
            'nameCopy' => true,
            'newColor' => 'mixed',
            'newLine' => 'below',
            'lineOption' => 'auto',
            'mode' => 'default',
            'autoResize' => '500×500',
            'theme' => 'modern',
            'pageSize' => 25,
            'mainMenuReduce' => false,
            'emojiList' => ['😀', '😎', '🎉'],
            'zoomMobile' => 100,
            'adult' => false,
            'advancedOptions' => true,
            'advancedFork' => false,
            'authApiKeys' => [
                'tmdb' => 'test-key-123'
            ],
            'api' => [
                'anilist' => true
            ]
        ];
    }
}
