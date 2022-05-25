<?php

namespace App\EventSubscriber;

use App\Controller\TokenAuthenticatedController;
use App\Entity\Token;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class TokenSubscriber implements EventSubscriberInterface
{
    private $repository;

    public function __construct(private ManagerRegistry $doctrine)
    {
        $this->repository = $doctrine->getRepository(Token::class);
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

            $token = $this->repository->findOneBy([
                'token' => $event->getRequest()->headers->get('X-AUTH-TOKEN')
            ]);

            if (!$token) {
                throw new AccessDeniedHttpException('This action needs a valid token!');
            }

            // mark the request as having passed token authentication
            $event->getRequest()->attributes->set('auth_token', $token);
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

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }
}
