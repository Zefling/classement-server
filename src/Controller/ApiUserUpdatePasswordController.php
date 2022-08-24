<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User;
use App\Entity\UserPassword;
use App\EventSubscriber\TokenSubscriber;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsController]
class ApiUserUpdatePasswordController extends AbstractApiController implements TokenAuthenticatedController
{
    public function __construct(private TokenSubscriber $tokenSubscriber)
    {
    }

    #[Route(
        '/api/user/update/password',
        name: 'app_api_user_update_password',
        methods: ['POST'],
        defaults: [
            '_api_resource_class' => UserPassword::class,
            '_api_item_operation_name' => 'app_api_user_update_password',
        ],
    )]
    public function __invoke(
        #[CurrentUser] ?User $user,
        Request $request,
        ManagerRegistry $doctrine,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        if (null === $user) {
            return $this->error(CodeError::USER_MISSING_CREDENTIALS, 'Missing credentials', Response::HTTP_UNAUTHORIZED);
        }

        // mapping

        $userPassword = new UserPassword();
        $userPassword->mapFromArray($request->toArray());

        if (empty($userPassword->getPasswordOld())) {
            return $this->error(CodeError::PASSWORD_MISSING_OLD, 'No current password');
        }

        if (empty($userPassword->getPasswordNew())) {
            return $this->error(CodeError::PASSWORD_MISSING_NEW, 'No old password');
        }

        $valid = $passwordHasher->isPasswordValid(
            $user,
            $userPassword->getPasswordOld()
        );

        // password

        if ($valid) {

            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $userPassword->getPasswordNew()
            );
            $user->setPassword($hashedPassword);

            $entityManager = $doctrine->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            return  $this->OK();
        } else {
            return $this->error(CodeError::PASSWORD_INVALID, 'That is not the current password.');
        }
    }
}
