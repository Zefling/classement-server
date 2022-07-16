<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsController]
class ApiAdminUserUpdateController extends AbstractApiController implements TokenAuthenticatedController
{

    #[Route(
        '/api/admin/user/{id}',
        name: 'app_api_admin_user_update',
        methods: ['POST'],
        defaults: [
            '_api_resource_class' => User::class,
            '_api_item_operations_name' => 'app_api_admin_user_update',
        ],
    )]
    public function __invoke(
        #[CurrentUser] ?User $user,
        string $id,
        Request $request,
        ManagerRegistry $doctrine,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        if (!($user?->isModerator())) {
            return $this->error(CodeError::USER_NO_PERMISSION, 'moderation role required', Response::HTTP_UNAUTHORIZED);
        }

        // mapping
        $content = $request->toArray();

        $userRep = $doctrine->getRepository(User::class);
        $userEdit =  $userRep->findOneBy(['id' => $id]);

        if ($userEdit === null) {
            return $this->error(CodeError::USER_NOT_FOUND, 'user not found', Response::HTTP_NOT_FOUND);
        }

        // username

        if (
            empty($content['username'])  &&
            !empty($username = trim($content['username'])) &&
            $content['username'] !== $userEdit->get
        ) {

            // test if login already exist


            if ($userEdit === null) {
                $userEdit->setUsername($username);
            } else {
                return $this->error(CodeError::LOGIN_ALREADY_EXISTS, 'This username already exists.');
            }
        }

        // password

        if (!empty($content['password'])) {
            if (strlen($password = trim($content['password'])) > 8) {
                $hashedPassword = $passwordHasher->hashPassword(
                    $user,
                    $password
                );
                $userEdit->setPassword($hashedPassword);
            } else {
                return $this->error(CodeError::PASSWORD_MISSING, 'No password or valid password');
            }
        }

        // email

        if ($content['email'] !== $userEdit->getEmail()) {

            if (!empty($content['email']) && filter_var($email = trim($content['email']), FILTER_VALIDATE_EMAIL)) {

                // test if email already exist
                $userEmail =  $userRep->findOneBy(['email' => $email]);

                if ($userEmail === null) {
                    $userEdit->setEmail($email);
                } else {
                    return $this->error(CodeError::EMAIL_ALREADY_EXISTS, 'This email already exists.');
                }
            } else {
                return $this->error(CodeError::EMAIL_MISSING, 'No email or valid email');
            }
        }

        $entityManager = $doctrine->getManager();
        $entityManager->persist($userEdit);
        $entityManager->flush();

        return $this->OK();
    }
}
