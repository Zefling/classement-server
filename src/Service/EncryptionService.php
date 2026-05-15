<?php

namespace App\Service;

use App\Enum\CodeError;
use Exception;

/**
 * Encryption/decryption service for sensitive data.
 * Uses AES-256-GCM to ensure data confidentiality and integrity.
 */
class EncryptionService
{
    private const CIPHER_METHOD = 'aes-256-gcm';
    private const IV_LENGTH = 16; // 128 bits for AES-GCM
    private const TAG_LENGTH = 16; // 128 bits for authentication tag

    private string $encryptionKey;

    /**
     * @param string $encryptionKey Encryption key (must be 32 bytes for AES-256)
     * @throws Exception If the encryption key is invalid
     */
    public function __construct(string $encryptionKey)
    {
        if (empty($encryptionKey)) {
            throw new Exception('Encryption key cannot be empty');
        }

        // Ensure the key is exactly 32 bytes (256 bits)
        // If the key is shorter, pad it with zeros
        // If it's longer, truncate it
        $this->encryptionKey = substr(str_pad($encryptionKey, 32, "\0"), 0, 32);
    }

    /**
     * Encrypts preferences data.
     * 
     * @param array $data Data to encrypt
     * @return string Encrypted data in base64(IV:TAG:CIPHERTEXT) format
     * @throws Exception If encryption fails
     */
    public function encrypt(array $data): string
    {
        try {
            // Convert data to JSON
            $jsonData = json_encode($data, JSON_THROW_ON_ERROR);
            
            // Generate a unique random IV for each operation
            $iv = random_bytes(self::IV_LENGTH);
            
            // Variable to store the authentication tag
            $tag = '';
            
            // Encrypt data with AES-256-GCM
            $ciphertext = openssl_encrypt(
                $jsonData,
                self::CIPHER_METHOD,
                $this->encryptionKey,
                OPENSSL_RAW_DATA,
                $iv,
                $tag,
                '',
                self::TAG_LENGTH
            );
            
            if ($ciphertext === false) {
                throw new Exception('Encryption failed: ' . openssl_error_string());
            }
            
            // Combine IV, TAG and CIPHERTEXT, then encode to base64
            // Format: base64(IV:TAG:CIPHERTEXT)
            $combined = $iv . $tag . $ciphertext;
            
            return base64_encode($combined);
        } catch (Exception $e) {
            throw new Exception(
                'Failed to encrypt data: ' . $e->getMessage(),
                CodeError::ENCRYPTION_ERROR->value,
                $e
            );
        }
    }

    /**
     * Decrypts preferences data.
     * 
     * @param string $encryptedData Encrypted data in base64(IV:TAG:CIPHERTEXT) format
     * @return array Decrypted data
     * @throws Exception If decryption fails or data is corrupted
     */
    public function decrypt(string $encryptedData): array
    {
        try {
            // Decode from base64
            $combined = base64_decode($encryptedData, true);
            
            if ($combined === false) {
                throw new Exception('Invalid base64 encoding');
            }
            
            // Verify data has minimum required length
            $minLength = self::IV_LENGTH + self::TAG_LENGTH;
            if (strlen($combined) < $minLength) {
                throw new Exception('Encrypted data is too short');
            }
            
            // Extract IV, TAG and CIPHERTEXT
            $iv = substr($combined, 0, self::IV_LENGTH);
            $tag = substr($combined, self::IV_LENGTH, self::TAG_LENGTH);
            $ciphertext = substr($combined, self::IV_LENGTH + self::TAG_LENGTH);
            
            // Decrypt data
            $decrypted = openssl_decrypt(
                $ciphertext,
                self::CIPHER_METHOD,
                $this->encryptionKey,
                OPENSSL_RAW_DATA,
                $iv,
                $tag
            );
            
            if ($decrypted === false) {
                throw new Exception('Decryption failed: authentication tag verification failed or data corrupted');
            }
            
            // Convert from JSON
            $data = json_decode($decrypted, true, 512, JSON_THROW_ON_ERROR);
            
            if (!is_array($data)) {
                throw new Exception('Decrypted data is not a valid array');
            }
            
            return $data;
        } catch (Exception $e) {
            throw new Exception(
                'Failed to decrypt data: ' . $e->getMessage(),
                CodeError::DECRYPTION_ERROR->value,
                $e
            );
        }
    }
}

