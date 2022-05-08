<?php

namespace App\Controller;

use App\Entity\Token;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User;
use App\EventSubscriber\TokenSubscriber;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class ApiLoginUpdateController extends AbstractApiController implements TokenAuthenticatedController
{
    public function __construct(private TokenSubscriber $tokenSubscriber)
    {
    }

    #[Route(
        '/api/login/update',
        name: 'app_api_login_update',
        methods: ['POST']
    )]
    public function update(#[CurrentUser] ?User $user, ManagerRegistry $doctrine): Response
    {
        if (null === $user) {
            return $this->json([
                'message' => 'missing credentials',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $entityManager = $doctrine->getManager();

        $token = new Token($user->id);

        $entityManager->persist($token);
        $entityManager->flush();

        return $this->json([
            'user'  => $user->getUserIdentifier(),
            'token' => $token,
        ]);
    }
}
