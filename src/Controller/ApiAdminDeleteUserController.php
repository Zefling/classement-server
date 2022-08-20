<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[AsController]
class ApiAdminDeleteUserController extends DeleteUserController implements TokenAuthenticatedController
{

    #[Route(
        '/api/admin/user/{id}',
        name: 'app_api_admin_user_delete',
        methods: ['DELETE'],
        defaults: [
            '_api_resource_class' => User::class,
            '_api_item_operations_name' => 'app_api_admin_user_delete',
        ],
    )]
    public function __invoke(#[CurrentUser] ?User $user, string $id, ManagerRegistry $doctrine): Response
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
            return $this->error(CodeError::USER_NO_PERMISSION_ADMIN, 'User not found', Response::HTTP_NOT_FOUND);
        }
    }
}
