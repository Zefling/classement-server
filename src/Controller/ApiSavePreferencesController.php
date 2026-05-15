<?php

namespace App\Controller;

use App\Controller\Common\AbstractApiController;
use App\Controller\Common\TokenAuthenticatedController;
use App\Entity\Preferences;
use App\Entity\User;
use App\Enum\CodeError;
use App\Service\EncryptionService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[AsController]
class ApiSavePreferencesController extends AbstractApiController implements TokenAuthenticatedController
{
    // required API Platform 3.x
    public static function getName(): string
    {
        return 'app_api_preferences_save';
    }

    public function __invoke(
        #[CurrentUser] ?User $user,
        Request $request,
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

        try {
            // Extract JSON data from request
            $data = $request->toArray();
        } catch (\Exception $e) {
            return $this->error(
                CodeError::INVALID_DATA,
                'Invalid JSON data: ' . $e->getMessage(),
                Response::HTTP_BAD_REQUEST
            );
        }

        // Validate against PreferencesSchema with JsonValidation
        try {
            $validator = new \App\Controller\Schema\JsonValidation();
            if (!$validator->isValid($data, \App\Controller\Schema\PreferencesSchema::$jsonSchema)) {
                return $this->error(
                    CodeError::INVALID_DATA,
                    'JsonSchema Error: Invalid preferences data',
                    Response::HTTP_BAD_REQUEST
                );
            }
        } catch (\Exception $e) {
            return $this->error(
                CodeError::INVALID_DATA,
                'JsonSchema Error: ' . $e->getMessage(),
                Response::HTTP_BAD_REQUEST
            );
        }

        // Encrypt data with EncryptionService
        try {
            $encryptedData = $encryptionService->encrypt($data);
        } catch (\Exception $e) {
            return $this->error(
                CodeError::ENCRYPTION_ERROR,
                'Failed to encrypt preferences',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // Search for existing Preferences for the user
        $entityManager = $doctrine->getManager();
        $preferencesRepository = $doctrine->getRepository(Preferences::class);
        $preferences = $preferencesRepository->findByUser($user);

        try {
            if ($preferences === null) {
                // Create new entity if none exists
                $preferences = new Preferences();
                $preferences->setUser($user);
                $preferences->setDateCreate(new \DateTimeImmutable());
            } else {
                // Update existing entity
                $preferences->setDateChange(new \DateTime());
            }

            // Set encrypted data
            $preferences->setEncryptedData($encryptedData);

            // Persist to database
            $entityManager->persist($preferences);
            $entityManager->flush();

            // Return 200 OK with confirmation
            return $this->OK('Preferences saved successfully');
        } catch (\Exception $e) {
            // Handle database errors (500 Internal Server Error)
            return $this->error(
                CodeError::DB_SAVE_REQUEST_ERROR,
                'Failed to save preferences',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
