<?php

namespace App\Tests\EventSubscriber;

use App\Entity\Token;
use App\Entity\User;
use App\Enum\CodeError;
use App\EventSubscriber\TokenSubscriber;
use App\Controller\Common\TokenAuthenticatedController;
use DateInterval;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class TokenSubscriberTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;
    private EntityRepository&MockObject $tokenRepo;
    private TokenSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->tokenRepo = $this->createMock(EntityRepository::class);
        $this->doctrine  = $this->createMock(ManagerRegistry::class);
        $this->doctrine
            ->method('getRepository')
            ->willReturn($this->tokenRepo);

        $this->subscriber = new TokenSubscriber($this->doctrine);
    }

    // ─────────────────────── getSubscribedEvents ───────────────────────

    public function testSubscribedEventsContainsRequiredKeys(): void
    {
        $events = TokenSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(KernelEvents::REQUEST, $events);
        $this->assertArrayHasKey(KernelEvents::CONTROLLER, $events);
        $this->assertArrayHasKey(KernelEvents::RESPONSE, $events);
    }

    // ─────────────────────── onKernelRequest ───────────────────────

    public function testOnKernelRequestSetsPendingAttribute(): void
    {
        $request = new Request();
        $event   = $this->makeRequestEvent($request);

        $this->subscriber->onKernelRequest($event);

        $this->assertTrue($request->attributes->get('_token_check_pending'));
    }

    // ─────────────────────── onKernelController — non-token controller ───────────────────────

    public function testOnKernelControllerSkipsNonTokenAuthenticatedController(): void
    {
        $request    = new Request();
        $controller = function () { return new Response('ok'); };
        $event      = $this->makeControllerEvent($request, $controller);

        $this->subscriber->onKernelController($event);

        // Controller stays the same — no early exit response injected
        $this->assertSame($controller, $event->getController());
        $this->assertNull($request->attributes->get('_token_authenticated'));
    }

    // ─────────────────────── onKernelController — missing token ───────────────────────

    public function testOnKernelControllerReturns401WhenNoTokenHeader(): void
    {
        $request    = new Request(); // no X-AUTH-TOKEN header
        $controller = $this->makeTokenAuthenticatedController();
        $event      = $this->makeControllerEvent($request, $controller);

        $this->subscriber->onKernelController($event);

        // Controller should be replaced with a closure that returns 401
        $newController = $event->getController();
        $this->assertIsCallable($newController);

        $response = $newController();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());

        $body = json_decode($response->getContent(), true);
        $this->assertEquals(CodeError::INVALID_TOKEN->value, $body['errorCode']);
        $this->assertFalse($request->attributes->get('_token_authenticated'));
    }

    // ─────────────────────── onKernelController — token not found in DB ───────────────────────

    public function testOnKernelControllerReturns401WhenTokenNotFoundInDb(): void
    {
        $request = new Request([], [], [], [], [], ['HTTP_X-AUTH-TOKEN' => 'invalid-token']);
        $request->headers->set('X-AUTH-TOKEN', 'invalid-token');

        $this->tokenRepo
            ->method('findOneBy')
            ->willReturn(null);

        $controller = $this->makeTokenAuthenticatedController();
        $event      = $this->makeControllerEvent($request, $controller);

        $this->subscriber->onKernelController($event);

        $newController = $event->getController();
        $response      = $newController();

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        $this->assertEquals(CodeError::INVALID_TOKEN->value, $body['errorCode']);
        $this->assertFalse($request->attributes->get('_token_authenticated'));
    }

    // ─────────────────────── onKernelController — valid token ───────────────────────

    public function testOnKernelControllerSetsAuthTokenAttributeForValidToken(): void
    {
        $user  = new User(1);
        $token = new Token($user, new DateInterval('PT1H'), 'login');

        $request = new Request();
        $request->headers->set('X-AUTH-TOKEN', $token->getToken());

        $this->tokenRepo
            ->method('findOneBy')
            ->willReturn($token);

        $controller = $this->makeTokenAuthenticatedController();
        $event      = $this->makeControllerEvent($request, $controller);

        $this->subscriber->onKernelController($event);

        // Controller should NOT be replaced
        $this->assertSame($controller, $event->getController());
        $this->assertSame($token, $request->attributes->get('auth_token'));
        $this->assertTrue($request->attributes->get('_token_authenticated'));
    }

    // ─────────────────────── onKernelController — array controller ───────────────────────

    public function testOnKernelControllerHandlesArrayController(): void
    {
        // Arrays like [$object, 'method'] — first element is the controller instance
        // The subscriber extracts $controller[0] to check for TokenAuthenticatedController
        $request                 = new Request();
        $tokenAuthController     = $this->makeTokenAuthenticatedController();
        // Use ['__invoke'] so the array is truly callable
        $event = $this->makeControllerEvent($request, [$tokenAuthController, '__invoke']);

        // No token → should still redirect to 401
        $this->subscriber->onKernelController($event);

        $newController = $event->getController();
        $this->assertIsCallable($newController);

        $response = $newController();
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    // ─────────────────────── onKernelResponse — no auth_token ───────────────────────

    public function testOnKernelResponseSkipsWhenNoAuthToken(): void
    {
        $request  = new Request();
        $response = new Response('{}');
        $event    = $this->makeResponseEvent($request, $response);

        $this->subscriber->onKernelResponse($event);

        // No X-CONTENT-HASH header should have been set
        $this->assertFalse($response->headers->has('X-CONTENT-HASH'));
    }

    // ─────────────────────── onKernelResponse — with auth_token ───────────────────────

    public function testOnKernelResponseSetsContentHashHeaderWhenAuthTokenPresent(): void
    {
        $user  = new User(1);
        $token = new Token($user, new DateInterval('PT1H'), 'login');

        $request = new Request();
        $request->attributes->set('auth_token', $token);

        $response = new Response('{"status":"OK"}');
        $event    = $this->makeResponseEvent($request, $response);

        $this->subscriber->onKernelResponse($event);

        $this->assertTrue($response->headers->has('X-CONTENT-HASH'));
        $expectedHash = sha1($response->getContent() . $token->getToken());
        $this->assertEquals($expectedHash, $response->headers->get('X-CONTENT-HASH'));
    }

    // ─────────────────────── helpers ───────────────────────

    private function makeTokenAuthenticatedController(): object
    {
        return new class implements TokenAuthenticatedController {
            public function __invoke(): Response
            {
                return new Response('ok');
            }
        };
    }

    private function makeRequestEvent(Request $request): RequestEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        return new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
    }

    private function makeControllerEvent(Request $request, callable|array $controller): ControllerEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        // ControllerEvent requires a true callable — pass it directly
        return new ControllerEvent($kernel, $controller, $request, HttpKernelInterface::MAIN_REQUEST);
    }

    private function makeResponseEvent(Request $request, Response $response): ResponseEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        return new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);
    }
}
