<?php

namespace App\Tests\Controller\Preferences;

use App\Entity\Preferences;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * For any valid preference data and authenticated user, saving preferences
 * then retrieving them SHALL produce equivalent data.
 *
 * This property validates the complete save-retrieve flow including validation,
 * encryption, database storage, retrieval, and decryption.
 */
class ApiSavePreferencesPersistenceRoundTripPropertyTest extends WebTestCase
{
    private const ITERATIONS = 100;

    /**
     * Property test: Persistence round-trip preservation
     *
     * This test generates random valid preference datasets, saves them via POST,
     * then retrieves them via GET, and verifies the data is equivalent.
     *
     * Runs 100 iterations with randomly generated valid preferences to verify
     * the property holds across all valid executions.
     *
     * @dataProvider validPreferencesDataProvider
     */
    public function testPersistenceRoundTripPreservation(array $preferences): void
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

        $tokenString = $token->getToken();

        try {
            // Save preferences via POST
            $client->request(
                'POST',
                '/api/preferences',
                [],
                [],
                [
                    'HTTP_X_AUTH_TOKEN' => $tokenString,
                    'CONTENT_TYPE' => 'application/json',
                ],
                json_encode($preferences)
            );

            $saveResponse = $client->getResponse();

            // Verify save was successful
            $this->assertEquals(
                200,
                $saveResponse->getStatusCode(),
                'Save operation should return 200 OK. Response: ' . $saveResponse->getContent()
            );

            // Retrieve preferences via GET
            $client->request(
                'GET',
                '/api/preferences',
                [],
                [],
                [
                    'HTTP_X_AUTH_TOKEN' => $tokenString,
                ]
            );

            $getResponse = $client->getResponse();

            // Verify retrieval was successful
            $this->assertEquals(
                200,
                $getResponse->getStatusCode(),
                'Retrieve operation should return 200 OK. Response: ' . $getResponse->getContent()
            );

            // Parse retrieved data
            $responseData = json_decode($getResponse->getContent(), true);

            // The response should contain the preferences in the 'message' field
            $this->assertArrayHasKey('message', $responseData, 'Response should contain message field');
            $retrieved = $responseData['message'];

            // Verify all fields are preserved
            foreach ($preferences as $key => $value) {
                $this->assertArrayHasKey(
                    $key,
                    $retrieved,
                    "Retrieved preferences should contain field '$key'"
                );

                $this->assertEquals(
                    $value,
                    $retrieved[$key],
                    "Field '$key' should be preserved through save-retrieve cycle"
                );
            }

            // Verify no extra fields were added
            $this->assertCount(
                count($preferences),
                $retrieved,
                'Retrieved preferences should not contain extra fields'
            );
        } finally {
            // Cleanup: Remove test user and preferences
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
     * Générateur de données de préférences valides aléatoires.
     * Génère 100+ jeux de données conformes au schéma PreferencesData.
     *
     * @return \Generator
     */
    public static function validPreferencesDataProvider(): \Generator
    {
        $newColorValues = ['mixed', 'same'];
        $newLineValues = ['below', 'above', 'ask-me'];
        $lineOptionValues = ['auto', 'reduce', 'hidden'];
        $autoResizeValues = ['300×300', '500×500', 'origin'];
        $interfaceLanguages = ['fr', 'en', 'es', 'de', 'it', 'ja', 'zh'];
        $interfaceThemes = ['light', 'dark', 'auto', 'custom'];
        $modes = ['default', 'compact', 'expanded', 'choice'];
        $themes = ['modern', 'classic', 'minimal', 'colorful'];

        // Générer 100 jeux de données aléatoires
        for ($i = 0; $i < 100; $i++) {
            $preferences = [
                'nameCopy' => (bool)random_int(0, 1),
                'newColor' => $newColorValues[array_rand($newColorValues)],
                'newLine' => $newLineValues[array_rand($newLineValues)],
                'lineOption' => $lineOptionValues[array_rand($lineOptionValues)],
                'mode' => $modes[array_rand($modes)],
                'autoResize' => $autoResizeValues[array_rand($autoResizeValues)],
                'pageSize' => random_int(1, 100),
                'mainMenuReduce' => (bool)random_int(0, 1),
                'emojiList' => self::generateRandomEmojiList(),
                'zoomMobile' => random_int(50, 200),
                'adult' => (bool)random_int(0, 1),
                'advancedOptions' => (bool)random_int(0, 1),
                'advancedFork' => (bool)random_int(0, 1),
                'authApiKeys' => [
                    'tmdb' => self::generateRandomApiKey()
                ],
                'api' => [
                    'anilist' => (bool)random_int(0, 1)
                ]
            ];

            // Ajouter des champs optionnels aléatoirement
            if (random_int(0, 1)) {
                $preferences['interfaceLanguage'] = $interfaceLanguages[array_rand($interfaceLanguages)];
            }
            if (random_int(0, 1)) {
                $preferences['interfaceTheme'] = $interfaceThemes[array_rand($interfaceThemes)];
            }
            if (random_int(0, 1)) {
                $preferences['theme'] = $themes[array_rand($themes)];
            }

            yield "Random preferences set #$i" => [$preferences];
        }

        // Ajouter des cas limites spécifiques
        yield 'Minimum values' => [[
            'nameCopy' => false,
            'newColor' => 'mixed',
            'newLine' => 'below',
            'lineOption' => 'auto',
            'mode' => 'default',
            'autoResize' => '300×300',
            'pageSize' => 1,
            'mainMenuReduce' => false,
            'emojiList' => [],
            'zoomMobile' => 50,
            'adult' => false,
            'advancedOptions' => false,
            'advancedFork' => false,
            'authApiKeys' => ['tmdb' => ''],
            'api' => ['anilist' => false]
        ]];

        yield 'Maximum values' => [[
            'nameCopy' => true,
            'newColor' => 'same',
            'newLine' => 'ask-me',
            'lineOption' => 'hidden',
            'mode' => 'expanded',
            'autoResize' => 'origin',
            'pageSize' => 1000,
            'mainMenuReduce' => true,
            'emojiList' => array_fill(0, 50, '🎉'),
            'zoomMobile' => 200,
            'adult' => true,
            'advancedOptions' => true,
            'advancedFork' => true,
            'authApiKeys' => ['tmdb' => str_repeat('x', 100)],
            'api' => ['anilist' => true]
        ]];

        yield 'All optional fields present' => [[
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
            'authApiKeys' => ['tmdb' => 'test-key-123'],
            'api' => ['anilist' => true]
        ]];

        yield 'Special characters in strings' => [[
            'nameCopy' => true,
            'newColor' => 'mixed',
            'newLine' => 'below',
            'lineOption' => 'auto',
            'mode' => 'Spécial çàéè 日本語',
            'autoResize' => '300×300',
            'pageSize' => 10,
            'mainMenuReduce' => false,
            'emojiList' => ['🎉', '🚀', '💻', '🌟', '❤️'],
            'zoomMobile' => 100,
            'adult' => false,
            'advancedOptions' => false,
            'advancedFork' => false,
            'authApiKeys' => ['tmdb' => '!@#$%^&*()_+-=[]{}|;:,.<>?'],
            'api' => ['anilist' => false]
        ]];
    }

    /**
     * Génère une liste aléatoire d'emojis.
     *
     * @return array
     */
    private static function generateRandomEmojiList(): array
    {
        $emojis = [
            '😀',
            '😃',
            '😄',
            '😁',
            '😆',
            '😅',
            '🤣',
            '😂',
            '🙂',
            '🙃',
            '😉',
            '😊',
            '😇',
            '🥰',
            '😍',
            '🤩',
            '😘',
            '😗',
            '😚',
            '😙',
            '🎉',
            '🎊',
            '🎈',
            '🎁',
            '🎀',
            '🎂',
            '🍰',
            '🧁',
            '🍪',
            '🍩',
            '🚀',
            '✨',
            '⭐',
            '🌟',
            '💫',
            '🔥',
            '💥',
            '💯',
            '✅',
            '❤️'
        ];

        $count = random_int(0, 15);
        $result = [];

        for ($i = 0; $i < $count; $i++) {
            $result[] = $emojis[array_rand($emojis)];
        }

        return $result;
    }

    /**
     * Génère une clé API aléatoire.
     *
     * @return string
     */
    private static function generateRandomApiKey(): string
    {
        $length = random_int(0, 50);
        if ($length === 0) {
            return '';
        }

        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-_';
        $key = '';

        for ($i = 0; $i < $length; $i++) {
            $key .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return $key;
    }
}
