<?php

namespace App\Controller;

use App\Controller\Common\GetUserController;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
class ApiGetUserController extends GetUserController
{

    // required API Platform 3.x
    public static function getName(): string
    {
        return 'app_api_user_get';
    }

    public function __invoke(string $id, Request $request, ManagerRegistry $doctrine): Response
    {

        $adult = $request->query->get('adult') === 'true';
        return parent::invoke($id, $doctrine, $adult, false, false);
    }
}
