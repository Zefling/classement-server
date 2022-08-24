<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[AsController]
class ApiGetCurrentUserController extends GetUserController implements TokenAuthenticatedController
{

    #[Route(
        '/api/user/current',
        name: 'app_api_user_current',
        methods: ['GET'],
        defaults: [
            '_api_resource_class' => User::class,
            '_api_collection_operations_name' => 'app_api_user_current',
        ],
    )]
    public function __invoke(#[CurrentUser] ?User $user, ManagerRegistry $doctrine): Response
    {
        if ($user) {
            if (!$user->isBanned()) {
                return parent::invoke($user->getUsername(), $doctrine);
            }
            return $this->error(CodeError::USER_BANNED, 'Banned user', Response::HTTP_UNAUTHORIZED);
        }
        return $this->error(CodeError::USER_NOT_FOUND, 'User not found', Response::HTTP_NOT_FOUND);
    }
}
