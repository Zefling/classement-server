<?php

namespace App\Controller;

use App\Controller\Common\AbstractApiController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
class ApiTestController extends AbstractApiController
{

    #[Route(
        '/api/test',
        name: 'app_api_test',
        methods: ['GET'],
    )]
    public function __invoke(): Response
    {
        return $this->OK();
    }
}
