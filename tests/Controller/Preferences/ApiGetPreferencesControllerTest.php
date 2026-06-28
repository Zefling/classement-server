<?php

namespace App\Tests\Controller\Preferences;

use App\Entity\Preferences;
use App\Entity\Token;
use App\Entity\User;
use App\Enum\CodeError;
use App\Service\EncryptionService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Unit tests for ApiGetPreferencesController
 *
 * Tests authentication, not found, successful retrieval, and corrupted data
 * scenarios for the preferences retrieval endpoint.
 */
class ApiGetPreferencesControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $testUser;
    private $testToken;
    private $encryptionService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $container = static::getContainer();
        $this->entityManager = $container->get('doctrine')->getManager();
        $this->encryptionService = $container->get(EncryptionService::class);

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
    public function testGetPreferencesWithoutToken(): void
    {
        $this->client->request(
            'GET',
            '/api/preferences',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ]
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
     * Test: utilisateur sans préférences → 404 Not Found
     *
     * When no preferences exist for the user, the controller SHALL
     * return a 404 Not Found response.
     */
    public function testGetPreferencesWhenNoneExist(): void
    {
        // Verify no preferences exist for the user
        $preferencesRepo = $this->entityManager->getRepository(Preferences::class);
        $existingPreferences = $preferencesRepo->findByUser($this->testUser);
        $this->assertNull($existingPreferences, 'No preferences should exist for test user');

        $this->client->request(
            'GET',
            '/api/preferences',
            [],
            [],
            [
                'HTTP_X_AUTH_TOKEN' => $this->testToken,
                'CONTENT_TYPE' => 'application/json',
            ]
        );

        $response = $this->client->getResponse();

        $this->assertEquals(
            404,
            $response->getStatusCode(),
            'Request for non-existent preferences should return 404 Not Found'
        );

        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals('KO', $responseData['status']);
        $this->assertEquals(CodeError::PREFERENCES_NOT_FOUND->value, $responseData['errorCode']);
        $this->assertStringContainsString('No preferences found', $responseData['errorMessage']);
    }

    /**
     * Test: récupération réussie retourne les données déchiffrées
     *
     * When preferences exist, the controller SHALL decrypt the data and
     * return a 200 OK response with the preferences data.
     */
    public function testGetPreferencesSuccessfulRetrieval(): void
    {
        // Create and save preferences for the user
        $originalPreferences = $this->getValidPreferencesData();

        // Encrypt the preferences
        $encryptedData = $this->encryptionService->encrypt($originalPreferences);

        // Create Preferences entity
        $preferences = new Preferences();
        $preferences->setUser($this->testUser);
        $preferences->setEncryptedData($encryptedData);
        $preferences->setDateCreate(new \DateTimeImmutable());

        $this->entityManager->persist($preferences);
        $this->entityManager->flush();

        // Retrieve preferences via API
        $this->client->request(
            'GET',
            '/api/preferences',
            [],
            [],
            [
                'HTTP_X_AUTH_TOKEN' => $this->testToken,
                'CONTENT_TYPE' => 'application/json',
            ]
        );

        $response = $this->client->getResponse();

        $this->assertEquals(
            200,
            $response->getStatusCode(),
            'Successful retrieval should return 200 OK. Response: ' . $response->getContent()
        );

        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals('OK', $responseData['status']);
        $this->assertArrayHasKey('message', $responseData);

        // Verify the decrypted data matches the original
        $retrievedPreferences = $responseData['message'];
        $this->assertEquals($originalPreferences['nameCopy'], $retrievedPreferences['nameCopy']);
        $this->assertEquals($originalPreferences['newColor'], $retrievedPreferences['newColor']);
        $this->assertEquals($originalPreferences['pageSize'], $retrievedPreferences['pageSize']);
        $this->assertEquals($originalPreferences['zoomMobile'], $retrievedPreferences['zoomMobile']);
        $this->assertEquals($originalPreferences['authApiKeys']['tmdb'], $retrievedPreferences['authApiKeys']['tmdb']);
        $this->assertEquals($originalPreferences['api']['anilist'], $retrievedPreferences['api']['anilist']);
    }

    /**
     * Test: données chiffrées corrompues → 500 Internal Server Error
     *
     * When a decryption error occurs, the controller SHALL return a
     * 500 Internal Server Error response.
     */
    public function testGetPreferencesWithCorruptedData(): void
    {
        // Create Preferences entity with corrupted encrypted data
        $preferences = new Preferences();
        $preferences->setUser($this->testUser);
        // Set corrupted data that cannot be decrypted
        $preferences->setEncryptedData('corrupted_base64_data_that_cannot_be_decrypted');
        $preferences->setDateCreate(new \DateTimeImmutable());

        $this->entityManager->persist($preferences);
        $this->entityManager->flush();

        // Attempt to retrieve preferences via API
        $this->client->request(
            'GET',
            '/api/preferences',
            [],
            [],
            [
                'HTTP_X_AUTH_TOKEN' => $this->testToken,
                'CONTENT_TYPE' => 'application/json',
            ]
        );

        $response = $this->client->getResponse();

        $this->assertEquals(
            500,
            $response->getStatusCode(),
            'Corrupted data should return 500 Internal Server Error'
        );

        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals('KO', $responseData['status']);
        $this->assertEquals(CodeError::DECRYPTION_ERROR->value, $responseData['errorCode']);
        $this->assertStringContainsString('Failed to decrypt', $responseData['errorMessage']);
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
