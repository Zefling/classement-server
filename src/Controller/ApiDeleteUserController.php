<?php

namespace App\Controller;

use App\Controller\Common\CodeError;
use App\Controller\Common\DeleteUserController;
use App\Controller\Common\TokenAuthenticatedController;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class ApiDeleteUserController extends DeleteUserController implements TokenAuthenticatedController
{

    // required API Platform 3.x
    public static function getName(): string
    {
        return 'app_api_user_delete';
    }

    public function __invoke(#[CurrentUser] ?User $user, ManagerRegistry $doctrine): Response
    {
        if (null === $user) {
            return $this->error(CodeError::USER_NOT_FOUND, 'missing credentials', Response::HTTP_UNAUTHORIZED);
        }

        parent::invoke($user, $doctrine);

        // return updated data
        return $this->OK();
    }
}
