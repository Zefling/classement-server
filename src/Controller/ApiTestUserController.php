<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
class ApiTestUserController extends AbstractApiController
{

    #[Route(
        '/api/test',
        name: 'app_api_user_test',
        methods: ['POST'],
        defaults: [
            '_api_resource_class' => User::class,
            '_api_collection_operations_name' => 'app_api_user_test',
        ],
    )]
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

        if ($test) {
            return $this->json([
                'message' => !empty($user),
                'code' => Response::HTTP_OK,
                'status' => 'OK'
            ]);
        } else {
            return $this->error(CodeError::INVALID_TEST, 'Test invalid');
        }
    }
}
