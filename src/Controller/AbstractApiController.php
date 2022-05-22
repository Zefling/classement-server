<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class AbstractApiController extends AbstractController
{

    public function error($code, $message): Response
    {
        return $this->json(
            [
                'errorCode' => $code,
                'errorMessage' => $message,
                'status' => 'KO',
                'code' => Response::HTTP_INTERNAL_SERVER_ERROR,
            ],
            Response::HTTP_INTERNAL_SERVER_ERROR
        );
    }
}
