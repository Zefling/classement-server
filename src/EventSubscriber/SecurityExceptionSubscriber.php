<?php

namespace App\EventSubscriber;

use App\Controller\Common\CodeError;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class SecurityExceptionSubscriber implements EventSubscriberInterface
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        // Handle authentication and access denied exceptions
        if ($exception instanceof AuthenticationException || 
            $exception instanceof AccessDeniedException ||
            $exception instanceof AccessDeniedHttpException) {
            
            // Check if it's an API request (starts with /api/)
            $request = $event->getRequest();
            if (str_starts_with($request->getPathInfo(), '/api/')) {
                $data = [
                    'errorCode' => CodeError::INVALID_TOKEN,
                    'errorMessage' => 'Invalid credentials.',
                    'code' => Response::HTTP_UNAUTHORIZED,
                    'status' => 'KO',
                ];

                $response = new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
                $event->setResponse($response);
            }
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 100], // High priority to run before API Platform
        ];
    }
}
