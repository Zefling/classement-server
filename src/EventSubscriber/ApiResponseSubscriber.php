<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\JsonResponse;

class ApiResponseSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => ['onKernelView', 16], // Before API Platform serialization
        ];
    }

    public function onKernelView(ViewEvent $event): void
    {
        $result = $event->getControllerResult();

        // Check if result is in our standard format (success or error)
        if (is_array($result) && isset($result['code']) && isset($result['status'])) {
            // Convert to JsonResponse directly
            $response = new JsonResponse($result, $result['code']);
            $event->setResponse($response);
        }
    }
}
