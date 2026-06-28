<?php

namespace App\Tests\Controller;

use App\Entity\Preferences;
use App\Entity\Token;
use App\Entity\User;
use App\Service\EncryptionService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApiDeleteUserControllerTest extends WebTestCase
{
    public function testDeleteUserAlsoDeletesPreferences(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        $encryptionService = $container->get(EncryptionService::class);

        // Create test user
        $user = new User();
        $user->setUsername('test_user_delete_' . uniqid());
        $user->setEmail('test_delete_' . uniqid() . '@example.com');
        $user->setPassword('hashed_password');
        $user->setRoles(['ROLE_USER']);
        $user->setDateCreate(new \DateTimeImmutable());
        $user->setIsValidated(true);
        $user->setAvatar(false);
        $user->setDeleted(false);

        $entityManager->persist($user);
        $entityManager->flush();

        // Create preferences for the user
        $preferencesData = [
            'interfaceLanguage' => 'fr',
            'pageSize' => 25,
        ];
        $encryptedData = $encryptionService->encrypt($preferencesData);

        $preferences = new Preferences();
        $preferences->setUser($user);
        $preferences->setEncryptedData($encryptedData);
        $preferences->setDateCreate(new \DateTimeImmutable());

        $entityManager->persist($preferences);
        $entityManager->flush();

        $userId = $user->getId();
        $preferencesId = $preferences->getId();

        // Create API token for the user
        $token = new Token($user, new \DateInterval('P1D'), 'login');
        $entityManager->persist($token);
        $entityManager->flush();

        // Verify preferences exist before deletion
        $preferencesRepo = $entityManager->getRepository(Preferences::class);
        $this->assertNotNull($preferencesRepo->find($preferencesId));

        // Delete the user via API
        $client->request(
            'DELETE',
            '/api/user',
            [],
            [],
            [
                'HTTP_X_AUTH_TOKEN' => $token->getToken(),
                'CONTENT_TYPE' => 'application/json',
            ]
        );

        $this->assertResponseIsSuccessful();

        // Verify preferences were deleted
        $entityManager->clear();
        $deletedPreferences = $preferencesRepo->find($preferencesId);
        $this->assertNull($deletedPreferences, 'Preferences should be deleted when user is deleted');

        // Verify user is marked as deleted (not actually removed from DB)
        $userRepo = $entityManager->getRepository(User::class);
        $deletedUser = $userRepo->find($userId);
        $this->assertNotNull($deletedUser, 'User entity should still exist');
        $this->assertTrue($deletedUser->getDeleted(), 'User should be marked as deleted');
        $this->assertEmpty($deletedUser->getUsername(), 'Username should be cleared');
        $this->assertEmpty($deletedUser->getEmail(), 'Email should be cleared');
    }

    public function testDeleteUserWithoutPreferencesSucceeds(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Create test user without preferences
        $user = new User();
        $user->setUsername('test_user_no_prefs_' . uniqid());
        $user->setEmail('test_no_prefs_' . uniqid() . '@example.com');
        $user->setPassword('hashed_password');
        $user->setRoles(['ROLE_USER']);
        $user->setDateCreate(new \DateTimeImmutable());
        $user->setIsValidated(true);
        $user->setAvatar(false);
        $user->setDeleted(false);

        $entityManager->persist($user);
        $entityManager->flush();

        $userId = $user->getId();

        // Create API token for the user
        $token = new Token($user, new \DateInterval('P1D'), 'login');
        $entityManager->persist($token);
        $entityManager->flush();

        // Delete the user via API
        $client->request(
            'DELETE',
            '/api/user',
            [],
            [],
            [
                'HTTP_X_AUTH_TOKEN' => $token->getToken(),
                'CONTENT_TYPE' => 'application/json',
            ]
        );

        $this->assertResponseIsSuccessful();

        // Verify user is marked as deleted
        $entityManager->clear();
        $userRepo = $entityManager->getRepository(User::class);
        $deletedUser = $userRepo->find($userId);
        $this->assertNotNull($deletedUser, 'User entity should still exist');
        $this->assertTrue($deletedUser->getDeleted(), 'User should be marked as deleted');
    }
}
