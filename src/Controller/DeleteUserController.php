<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;

class DeleteUserController extends AbstractApiController
{

    public function invoke(User $user, ManagerRegistry $doctrine)
    {
        // remove all user informations

        $user->setUsername('');
        $user->setPassword('');
        $user->setEmail('');
        $user->setRoles([]);

        $entityManager = $doctrine->getManager();
        $entityManager->persist($user);
        $entityManager->flush();
    }
}
