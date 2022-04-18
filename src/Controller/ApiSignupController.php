<?php

namespace App\Controller;

use App\Entity\User;
use DateTimeImmutable;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class ApiSignupController extends AbstractController
{
    #[Route(
        '/api/signup', 
        name: 'app_api_signup',
        methods: ['POST'],

    )]
    public function index(Request $request, ManagerRegistry $doctrine, UserPasswordHasherInterface $passwordHasher): Response
    {
        $entityManager = $doctrine->getManager();
        $content = $request->toArray();

        $user = new User();

        $username = trim($content['username']);
        if (!empty($username)) {
            $user->setUsername($username);
        }

        $password = $content['password'];
        if (strlen($password) > 8) {
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $password
            );
            $user->setPassword($hashedPassword);
        }

        $email = trim($content['email']);
        if (!empty($email)) {
            $user->setEmail($email);
        }

        $user->setRoles(['ROLE_USER']);
        $user->setDateCreate(new DateTimeImmutable());
        $user->setIsValidated(false);

        $entityManager->persist($user);
        $entityManager->flush();

        return $this->json($content);
    }
}
