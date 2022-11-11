<?php

namespace App\Controller;

use App\Controller\Common\GetUserController;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
class ApiGetUserController extends GetUserController
{

    #[Route(
        '/api/profile/{id}',
        name: 'app_api_user_get',
        methods: ['GET'],
        defaults: [
            '_api_resource_class' => User::class,
            '_api_item_operations_name' => 'app_api_user_get',
        ],
    )]
    public function __invoke(string $id, ManagerRegistry $doctrine): Response
    {
        return parent::invoke($id, $doctrine, false, false);
    }
}
