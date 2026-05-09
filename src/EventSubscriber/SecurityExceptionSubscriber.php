<?php

namespace App\EventSubscriber;

use App\Enum\CodeError;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class SecurityExceptionSubscriber implements EventSubscriberInterface
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        // Only handle API requests (starts with /api/)
        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        // Handle authentication and access denied exceptions
        if ($exception instanceof AuthenticationException || 
            $exception instanceof AccessDeniedException ||
            $exception instanceof AccessDeniedHttpException) {
            
            $data = [
                'errorCode' => CodeError::INVALID_TOKEN,
                'errorMessage' => 'Invalid credentials.',
                'code' => Response::HTTP_UNAUTHORIZED,
                'status' => 'KO',
            ];

            $response = new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
            $event->setResponse($response);
            return;
        }

        // Handle 404 Not Found exceptions
        if ($exception instanceof NotFoundHttpException) {
            $data = [
                'errorCode' => CodeError::INVALID_REQUEST,
                'errorMessage' => 'Resource not found.',
                'code' => Response::HTTP_NOT_FOUND,
                'status' => 'KO',
            ];

            $response = new JsonResponse($data, Response::HTTP_NOT_FOUND);
            $event->setResponse($response);
            return;
        }

        // Handle other HTTP exceptions (400, 405, 500, etc.)
        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
            $message = $exception->getMessage() ?: 'An error occurred.';

            $data = [
                'errorCode' => CodeError::INVALID_REQUEST,
                'errorMessage' => $message,
                'code' => $statusCode,
                'status' => 'KO',
            ];

            $response = new JsonResponse($data, $statusCode);
            $event->setResponse($response);
            return;
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 100], // High priority to run before API Platform
        ];
    }
}
