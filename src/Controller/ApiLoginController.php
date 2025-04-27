<?php

namespace App\Controller;

use App\Controller\Common\CodeError;
use App\Controller\Common\TokenInit;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User;
use App\Entity\UserLogin;
use App\EventSubscriber\TokenSubscriber;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


#[AsController]
class ApiLoginController extends TokenInit
{
    public function __construct(private TokenSubscriber $tokenSubscriber) {}

    #[Route(
        '/api/login',
        name: 'app_api_login',
        methods: ['POST'],
        defaults: [
            '_api_resource_class' => UserLogin::class,
            '_api_collection_operations_name' => 'app_api_login',
        ],
    )]
    public function __invoke(
        Request $request,
        ManagerRegistry $doctrine,
        UserPasswordHasherInterface $passwordHasher
    ): Response {

        $userLogin = new UserLogin();
        $userLogin->mapFromArray($request->toArray());

        if (empty(trim($userLogin->getUsername()))) {
            return $this->error(CodeError::LOGIN_MISSING, 'No username');
        }

        if (empty($userLogin->getPassword())) {
            return $this->error(CodeError::PASSWORD_MISSING, 'No password');
        }

        $userRep = $doctrine->getRepository(User::class);
        $user = $userRep->findUserOrEmail($userLogin->getUsername());

        if ($user === null) {
            return $this->error(CodeError::USER_NOT_FOUND, 'User not found', Response::HTTP_NOT_FOUND);
        }

        $valid = $passwordHasher->isPasswordValid(
            $user,
            $userLogin->getPassword()
        );

        if (!$valid) {
            return $this->error(CodeError::USER_NOT_FOUND, 'User not found', Response::HTTP_NOT_FOUND);
        }

        if ($user->isBanned()) {
            return $this->error(CodeError::USER_BANNED, 'Banned user', Response::HTTP_FORBIDDEN);
        }

        if (!$user->getIsValidated()) {
            return $this->error(CodeError::USER_NOT_VALIDATED, 'Not validated user', Response::HTTP_FORBIDDEN);
        }

        try {
            $token = $this->initToken($user, $doctrine, 'login', '1 week');

            return $this->ok([
                'user'  => $user->getUserIdentifier(),
                'token' => $token->getToken(),
            ]);
        } catch (UniqueConstraintViolationException $ex) {
            return $this->error(CodeError::DUPLICATE_CONTENT, $ex->getMessage());
        }
    }
}
