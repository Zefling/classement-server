<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AbstractApiController extends AbstractController
{

    public function error($code, $message, $codeHttp = Response::HTTP_INTERNAL_SERVER_ERROR): Response
    {
        return new JsonResponse(
            [
                'errorCode' => $code,
                'errorMessage' => $message,
                'status' => 'KO',
                'code' => $codeHttp,
            ],
            $codeHttp
        );
    }
}
