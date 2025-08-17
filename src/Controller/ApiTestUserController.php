<?php

namespace App\Controller;

use App\Controller\Common\CodeError;
use App\Controller\Common\AbstractApiController;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class ApiTestUserController extends AbstractApiController
{

    // required API Platform 3.x
    public static function getName(): string
    {
        return 'app_api_user_test';
    }

    public function __invoke(Request $request, ManagerRegistry $doctrine): Response
    {
        $array = $request->toArray();
        $test = false;

        if (isset($array['username']) && !empty($array['username'])) {
            $user = $doctrine->getRepository(User::class)->findByUsername($array['username']);
            $test = true;
        } elseif (isset($array['email']) && !empty($array['email'])) {
            $user = $doctrine->getRepository(User::class)->findByEmail($array['email']);
            $test = true;
        }

        return $test
            ? $this->json(!empty($user))
            : $this->error(CodeError::INVALID_TEST, 'Test invalid');
    }
}
