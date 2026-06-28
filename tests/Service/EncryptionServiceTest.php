<?php

namespace App\Tests\Service;

use App\Service\EncryptionService;
use PHPUnit\Framework\TestCase;

class EncryptionServiceTest extends TestCase
{
    private EncryptionService $encryptionService;
    private string $testKey = 'test-encryption-key-32-bytes!!';

    protected function setUp(): void
    {
        $this->encryptionService = new EncryptionService($this->testKey);
    }

    public function testConstructorThrowsExceptionForEmptyKey(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Encryption key cannot be empty');

        new EncryptionService('');
    }

    public function testEncryptReturnsBase64String(): void
    {
        $data = ['test' => 'value', 'number' => 42];

        $encrypted = $this->encryptionService->encrypt($data);

        $this->assertIsString($encrypted);
        $this->assertNotEmpty($encrypted);
        // Vérifier que c'est bien du base64
        $this->assertNotFalse(base64_decode($encrypted, true));
    }

    public function testDecryptReturnsOriginalData(): void
    {
        $data = ['test' => 'value', 'number' => 42, 'bool' => true];

        $encrypted = $this->encryptionService->encrypt($data);
        $decrypted = $this->encryptionService->decrypt($encrypted);

        $this->assertEquals($data, $decrypted);
    }

    public function testEncryptionProducesDifferentOutputForSameInput(): void
    {
        $data = ['test' => 'value'];

        $encrypted1 = $this->encryptionService->encrypt($data);
        $encrypted2 = $this->encryptionService->encrypt($data);

        // Les deux chiffrements doivent être différents (IV aléatoire)
        $this->assertNotEquals($encrypted1, $encrypted2);

        // Mais les deux doivent déchiffrer vers les mêmes données
        $this->assertEquals($data, $this->encryptionService->decrypt($encrypted1));
        $this->assertEquals($data, $this->encryptionService->decrypt($encrypted2));
    }

    public function testDecryptThrowsExceptionForInvalidBase64(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to decrypt data');

        $this->encryptionService->decrypt('not-valid-base64!!!');
    }

    public function testDecryptThrowsExceptionForTooShortData(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to decrypt data');

        // Données trop courtes (moins de IV_LENGTH + TAG_LENGTH)
        $shortData = base64_encode('short');
        $this->encryptionService->decrypt($shortData);
    }

    public function testDecryptThrowsExceptionForCorruptedData(): void
    {
        $data = ['test' => 'value'];
        $encrypted = $this->encryptionService->encrypt($data);

        // Corrompre les données chiffrées
        $decoded = base64_decode($encrypted);
        $corrupted = substr($decoded, 0, -5) . 'xxxxx'; // Modifier les derniers octets
        $corruptedEncrypted = base64_encode($corrupted);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to decrypt data');

        $this->encryptionService->decrypt($corruptedEncrypted);
    }

    public function testEncryptDecryptWithComplexPreferencesData(): void
    {
        $preferencesData = [
            'interfaceLanguage' => 'fr',
            'interfaceTheme' => 'dark',
            'nameCopy' => true,
            'newColor' => 'mixed',
            'newLine' => 'below',
            'lineOption' => 'auto',
            'mode' => 'default',
            'autoResize' => '300×300',
            'theme' => 'modern',
            'pageSize' => 25,
            'mainMenuReduce' => false,
            'emojiList' => ['😀', '😎', '🎉'],
            'zoomMobile' => 100,
            'adult' => false,
            'advancedOptions' => true,
            'advancedFork' => false,
            'authApiKeys' => [
                'tmdb' => 'secret-api-key-12345'
            ],
            'api' => [
                'anilist' => true
            ]
        ];

        $encrypted = $this->encryptionService->encrypt($preferencesData);
        $decrypted = $this->encryptionService->decrypt($encrypted);

        $this->assertEquals($preferencesData, $decrypted);
    }

    public function testEncryptDecryptWithEmptyArray(): void
    {
        $data = [];

        $encrypted = $this->encryptionService->encrypt($data);
        $decrypted = $this->encryptionService->decrypt($encrypted);

        $this->assertEquals($data, $decrypted);
    }

    public function testEncryptDecryptWithNestedArrays(): void
    {
        $data = [
            'level1' => [
                'level2' => [
                    'level3' => 'deep value'
                ]
            ],
            'array' => [1, 2, 3, 4, 5]
        ];

        $encrypted = $this->encryptionService->encrypt($data);
        $decrypted = $this->encryptionService->decrypt($encrypted);

        $this->assertEquals($data, $decrypted);
    }

    public function testEncryptDecryptWithSpecialCharacters(): void
    {
        $data = [
            'special' => 'Spécial çàéè 日本語 🎉',
            'symbols' => '!@#$%^&*()_+-=[]{}|;:,.<>?'
        ];

        $encrypted = $this->encryptionService->encrypt($data);
        $decrypted = $this->encryptionService->decrypt($encrypted);

        $this->assertEquals($data, $decrypted);
    }

    public function testDifferentKeysProduceDifferentEncryption(): void
    {
        $data = ['test' => 'value'];

        $service1 = new EncryptionService('key1-must-be-32-bytes-long!!!');
        $service2 = new EncryptionService('key2-must-be-32-bytes-long!!!');

        $encrypted1 = $service1->encrypt($data);
        $encrypted2 = $service2->encrypt($data);

        // Les chiffrements avec des clés différentes doivent être différents
        $this->assertNotEquals($encrypted1, $encrypted2);

        // Et ne peuvent pas être déchiffrés avec l'autre clé
        $this->expectException(\Exception::class);
        $service1->decrypt($encrypted2);
    }

    /**
     * Feature: user-preferences-api, Property 1: Encryption Round-Trip Preservation
     * **Validates: Requirements 7.1**
     *
     * For any valid preference data, encrypting then decrypting SHALL produce equivalent data.
     *
     * This property-based test generates 100+ random valid preference datasets and verifies
     * that the encryption/decryption process is reversible and doesn't lose or corrupt data.
     *
     * @dataProvider validPreferencesDataProvider
     */
    public function testEncryptionRoundTripPreservation(array $preferences): void
    {
        $encrypted = $this->encryptionService->encrypt($preferences);
        $decrypted = $this->encryptionService->decrypt($encrypted);

        $this->assertEquals(
            $preferences,
            $decrypted,
            'Encryption round-trip should preserve all data exactly'
        );
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

        yield 'Empty emoji list' => [[
            'nameCopy' => false,
            'newColor' => 'same',
            'newLine' => 'above',
            'lineOption' => 'reduce',
            'mode' => 'compact',
            'autoResize' => 'origin',
            'pageSize' => 50,
            'mainMenuReduce' => true,
            'emojiList' => [],
            'zoomMobile' => 150,
            'adult' => true,
            'advancedOptions' => true,
            'advancedFork' => true,
            'authApiKeys' => ['tmdb' => 'api-key-xyz'],
            'api' => ['anilist' => true]
        ]];

        yield 'Large emoji list' => [[
            'nameCopy' => true,
            'newColor' => 'mixed',
            'newLine' => 'ask-me',
            'lineOption' => 'auto',
            'mode' => 'default',
            'autoResize' => '500×500',
            'pageSize' => 75,
            'mainMenuReduce' => false,
            'emojiList' => [
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
                '😙'
            ],
            'zoomMobile' => 125,
            'adult' => false,
            'advancedOptions' => false,
            'advancedFork' => false,
            'authApiKeys' => ['tmdb' => 'long-api-key-' . str_repeat('abc', 20)],
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
