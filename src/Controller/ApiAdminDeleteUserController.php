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
class ApiAdminDeleteUserController extends DeleteUserController implements TokenAuthenticatedController
{

    // required API Platform 3.x
    public static function getName(): string
    {
        return 'app_api_admin_user_delete';
    }

    public function __invoke(#[CurrentUser] ?User $user, int $id, ManagerRegistry $doctrine): Response
    {
        if (null === $user->isAdmin()) {
            return $this->error(CodeError::USER_NO_PERMISSION_ADMIN, 'Admin role required', Response::HTTP_UNAUTHORIZED);
        }

        $rep = $doctrine->getRepository(User::class);
        $userAnonymous = $rep->findOneById($id);

        if ($userAnonymous) {
            parent::invoke($userAnonymous, $doctrine);
            return $this->ok();
        } else {
            return $this->error(CodeError::USER_NOT_FOUND, 'User not found', Response::HTTP_NOT_FOUND);
        }
    }
}
