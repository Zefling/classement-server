<?php

namespace App\Controller;

use App\Entity\ClassementSubmit;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
class ApiClassementController extends AbstractApiController implements TokenAuthenticatedController
{

    #[Route(
        '/api/classement/add',
        name: 'app_api_classement_add',
        methods: ['POST'],
        defaults: [
            '_api_resource_class' => ClassementSubmit::class,
            '_api_collection_operationsname' => 'post_publication',
        ],
    )]
    public function __invoke(Request $request, ManagerRegistry $doctrine): Response
    {
        $classement = new ClassementSubmit();
        $classement->mapFromArray($request->toArray());

        return new JsonResponse(
            [
                'message' => $classement->toArray(),
                'code' => Response::HTTP_OK,
                'status' => 'OK'
            ]
        );
    }
}
