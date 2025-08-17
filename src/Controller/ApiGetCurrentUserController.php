<?php

namespace App\Controller;

use App\Controller\Common\CodeError;
use App\Controller\Common\GetUserController;
use App\Controller\Common\TokenAuthenticatedController;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class ApiGetCurrentUserController extends GetUserController implements TokenAuthenticatedController
{

    // required API Platform 3.x
    public static function getName(): string
    {
        return 'app_api_user_current';
    }

    public function __invoke(#[CurrentUser] ?User $user, ManagerRegistry $doctrine): Response
    {
        if (!$user) {
            return $this->error(CodeError::USER_NOT_FOUND, 'User not found', Response::HTTP_NOT_FOUND);
        }
        if ($user->isBanned()) {
            return $this->error(CodeError::USER_BANNED, 'Banned user', Response::HTTP_UNAUTHORIZED);
        }

        return parent::invoke($user->getUsername(), $doctrine, true);
    }
}
