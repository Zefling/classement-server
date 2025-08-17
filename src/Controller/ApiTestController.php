<?php

namespace App\Controller;

use App\Controller\Common\AbstractApiController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class ApiTestController extends AbstractApiController
{

    // required API Platform 3.x
    public static function getName(): string
    {
        return 'app_api_test';
    }

    public function __invoke(): Response
    {
        return $this->OK();
    }
}
