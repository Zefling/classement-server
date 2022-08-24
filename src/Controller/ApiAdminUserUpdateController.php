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

        $edit = false;

        // mapping
        $content = $request->toArray();

        $userRep = $doctrine->getRepository(User::class);
        $userEdit =  $userRep->findOneBy(['id' => $id]);

        if ($userEdit === null) {
            return $this->error(CodeError::USER_NOT_FOUND, 'user not found', Response::HTTP_NOT_FOUND);
        }

        // username

        if (
            isset($content['username']) &&
            !empty($username = trim($content['username'])) &&
            $username !== $userEdit->getUsername()
        ) {

            // test if username already exist
            $userEmail =  $userRep->findOneBy(['username' => $username]);

            if ($userEmail === null) {
                $userEdit->setUsername($username);
                $edit = true;
            } else {
                return $this->error(
                    CodeError::LOGIN_ALREADY_EXISTS,
                    'This username already exists.',
                    Response::HTTP_CONFLICT
                );
            }
        }

        // password

        if (
            isset($content['password']) &&
            !empty($content['password'])
        ) {
            if (strlen($password = trim($content['password'])) > 8) {
                $hashedPassword = $passwordHasher->hashPassword(
                    $user,
                    $password
                );
                $userEdit->setPassword($hashedPassword);
                $edit = true;
            } else {
                return $this->error(CodeError::PASSWORD_MISSING, 'No password or valid password');
            }
        }

        // email

        if (
            isset($content['email']) &&
            filter_var($email = trim($content['email']), FILTER_VALIDATE_EMAIL) &&
            $email !== $userEdit->getEmail()
        ) {

            // test if email already exist
            $userEmail =  $userRep->findOneBy(['email' => $email]);

            if ($userEmail === null) {
                $userEdit->setEmail($email);
                $edit = true;
            } else {
                return $this->error(
                    CodeError::EMAIL_ALREADY_EXISTS,
                    'This email already exists.',
                    Response::HTTP_CONFLICT
                );
            }
        }

        $roles = $userEdit->getRoles();
        if (isset($content['moderator'])) {
            if ($content['moderator'] === true && !in_array('ROLE_MODERATOR', $roles)) {
                $roles[] = 'ROLE_MODERATOR';
                $edit = true;
            } else if ($content['moderator'] !== true && ($key = array_search('ROLE_MODERATOR', $roles)) !== false) {
                unset($roles[$key]);
                $edit = true;
            }
        }

        if (isset($content['admin'])) {
            if ($content['admin'] === true && !in_array('ROLE_ADMIN', $roles)) {
                $roles[] = 'ROLE_ADMIN';
                $edit = true;
            } else if ($content['admin'] !== true && ($key = array_search('ROLE_ADMIN', $roles)) !== false) {
                unset($roles[$key]);
                $edit = true;
            }
        }

        $isAdmin = in_array('ROLE_ADMIN', $roles);

        if (isset($content['banned']) || $isAdmin) {
            if (
                isset($content['banned']) && $content['banned'] === true && !in_array('ROLE_BANNED', $roles) &&
                !$isAdmin
            ) {
                $roles[] = 'ROLE_BANNED';
                $edit = true;
            } else if (
                (isset($content['banned']) && $content['banned'] !== true || $isAdmin) &&
                ($key = array_search('ROLE_BANNED', $roles)) !== false
            ) {
                unset($roles[$key]);
                $edit = true;
            }
        }

        $userEdit->setRoles($roles);

        if ($edit) {
            $entityManager = $doctrine->getManager();
            $entityManager->persist($userEdit);
            $entityManager->flush();
        }

        $userArray = $userEdit->toArray();

        unset(
            $userArray['password']
        );

        return $this->OK(
            $userArray
        );
    }
}
