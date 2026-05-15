<?php

namespace App\Controller;

use App\Controller\Common\AbstractApiController;
use App\Controller\Common\TokenAuthenticatedController;
use App\Entity\Preferences;
use App\Entity\User;
use App\Enum\CodeError;
use App\Service\EncryptionService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[AsController]
class ApiGetPreferencesController extends AbstractApiController implements TokenAuthenticatedController
{
    // required API Platform 3.x
    public static function getName(): string
    {
        return 'app_api_preferences_get';
    }

    public function __invoke(
        #[CurrentUser] ?User $user,
        ManagerRegistry $doctrine,
        EncryptionService $encryptionService
    ): Response {
        // Check authentication
        if (!$user instanceof User) {
            return $this->error(
                CodeError::USER_NOT_AUTHENTICATED,
                'Authentication required',
                Response::HTTP_UNAUTHORIZED
            );
        }

        // Search for Preferences for the user via PreferencesRepository
        $preferencesRepository = $doctrine->getRepository(Preferences::class);
        $preferences = $preferencesRepository->findByUser($user);

        // Return 404 with CodeError::PREFERENCES_NOT_FOUND if not found
        if ($preferences === null) {
            return $this->error(
                CodeError::PREFERENCES_NOT_FOUND,
                'No preferences found for this user',
                Response::HTTP_NOT_FOUND
            );
        }

        // Decrypt data with EncryptionService
        try {
            $decryptedData = $encryptionService->decrypt($preferences->getEncryptedData());
        } catch (\Exception $e) {
            // Handle decryption errors (500 with CodeError::DECRYPTION_ERROR)
            return $this->error(
                CodeError::DECRYPTION_ERROR,
                'Failed to decrypt preferences',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // Return 200 OK with decrypted preferences
        return $this->OK($decryptedData);
    }
}
