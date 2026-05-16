<?php

namespace App\EventSubscriber;

use App\Enum\CodeError;
use App\Controller\Common\TokenAuthenticatedController;
use App\Entity\Token;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class TokenSubscriber implements EventSubscriberInterface
{
    private $repository;

    public function __construct(private ManagerRegistry $doctrine)
    {
        $this->repository = $doctrine->getRepository(Token::class);
    }

    public function onKernelRequest(RequestEvent $event)
    {
        // Store request for later use in onKernelController
        $event->getRequest()->attributes->set('_token_check_pending', true);
    }

    public function onKernelController(ControllerEvent $event)
    {
        $controller = $event->getController();

        // when a controller class defines multiple action methods, the controller
        // is returned as [$controllerInstance, 'methodName']
        if (is_array($controller)) {
            $controller = $controller[0];
        }

        if ($controller instanceof TokenAuthenticatedController) {
            $request = $event->getRequest();
            $request->attributes->remove('_token_check_pending');

            $tokenValue = $request->headers->get('X-AUTH-TOKEN');
            
            if (!$tokenValue) {
                $data = [
                    'errorCode' => CodeError::INVALID_TOKEN,
                    'errorMessage' => 'Invalid credentials.',
                    'code' => Response::HTTP_UNAUTHORIZED,
                    'status' => 'KO',
                ];
                $response = new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
                $event->setController(function() use ($response) {
                    return $response;
                });
                // Mark that we handled authentication
                $request->attributes->set('_token_authenticated', false);
                return;
            }

            $token = $this->repository->findOneBy(['token' => $tokenValue]);

            if (!$token) {
                $data = [
                    'errorCode' => CodeError::INVALID_TOKEN,
                    'errorMessage' => 'Invalid credentials.',
                    'code' => Response::HTTP_UNAUTHORIZED,
                    'status' => 'KO',
                ];
                $response = new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
                $event->setController(function() use ($response) {
                    return $response;
                });
                // Mark that we handled authentication
                $request->attributes->set('_token_authenticated', false);
                return;
            }

            // mark the request as having passed token authentication
            $request->attributes->set('auth_token', $token);
            $request->attributes->set('_token_authenticated', true);
        }
    }

    public function onKernelResponse(ResponseEvent $event)
    {
        // check to see if onKernelController marked this as a token "auth'ed" request
        if (!$token = $event->getRequest()->attributes->get('auth_token')) {
            return;
        }

        $response = $event->getResponse();

        // create a hash and set it as a response header

        $hash = sha1($response->getContent() . $token->getToken());
        $response->headers->set('X-CONTENT-HASH', $hash);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 9], // Before security (priority 8)
            KernelEvents::CONTROLLER => 'onKernelController',
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }
}
