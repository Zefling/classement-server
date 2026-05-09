<?php

namespace App\State;

use App\Enum\CodeError;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractStateProvider
{
    /**
     * Wrap data in standard API response format
     * Same format as AbstractApiController::OK()
     */
    protected function OK(mixed $message = null): array
    {
        return $message
            ? [
                'message' => $message,
                'code' => Response::HTTP_OK,
                'status' => 'OK'
            ]
            : [
                'code' => Response::HTTP_OK,
                'status' => 'OK'
            ];
    }

    /**
     * Return error response in standard format
     * Same format as AbstractApiController::error()
     */
    protected function error(CodeError $code, string $message, int $codeHttp = Response::HTTP_BAD_REQUEST): array
    {
        return [
            'errorCode' => $code,
            'errorMessage' => $message,
            'status' => 'KO',
            'code' => $codeHttp
        ];
    }
}
