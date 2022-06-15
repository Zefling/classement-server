<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserSingup;
use DateTimeImmutable;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
class ApiSignupController extends AbstractApiController
{
    #[Route(
        '/api/signup',
        name: 'app_api_signup',
        methods: ['POST'],
        defaults: [
            '_api_resource_class' => UserSingup::class,
            '_api_collection_operation_name' => 'app_api_signup',
        ],
    )]
    public function __invoke(Request $request, ManagerRegistry $doctrine): Response
    {
        $content = $request->toArray();

        $user = new User();

        if (!empty($content['username']) && !empty($username = trim($content['username']))) {
            $user->setUsername($username);
        } else {
            return  $this->error(CodeError::LOGIN_MISSING, 'No username');
        }

        if (!empty($content['password']) && strlen($password = trim($content['password'])) > 8) {
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $password
            );
            $user->setPassword($hashedPassword);
        } else {
            return  $this->error(CodeError::PASSWORD_MISSING, 'No password or valid password');
        }

        if (!empty($content['email']) && filter_var($email = trim($content['email']), FILTER_VALIDATE_EMAIL)) {
            $user->setEmail($email);
        } else {
            return  $this->error(CodeError::EMAIL_MISSING, 'No email  or valid email');
        }

        $user->setRoles(['ROLE_USER']);
        $user->setDateCreate(new DateTimeImmutable());
        $user->setIsValidated(false);

        try {
            $entityManager = $doctrine->getManager();
            $entityManager->persist($user);
            $entityManager->flush();
            return $this->json([
                'code' => Response::HTTP_OK,
                'status' => 'OK'
            ]);
        } catch (UniqueConstraintViolationException $ex) {
            return $this->error(CodeError::DUPLICATE_CONTENT, $ex->getMessage());
        }
    }
}
