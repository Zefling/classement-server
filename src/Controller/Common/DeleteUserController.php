<?php

namespace App\Controller\Common;

use App\Entity\Token;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;

class DeleteUserController extends AbstractApiController
{

    public function invoke(User $user, ManagerRegistry $doctrine)
    {
        // remove tokens

        $rep = $doctrine->getRepository(Token::class);
        $rep->removeByUser($user);

        // remove all user informations

        $user->setUsername('');
        $user->setPassword('');
        $user->setEmail('');
        $user->setRoles([]);
        $user->setDeleted(true);

        $entityManager = $doctrine->getManager();
        $entityManager->persist($user);
        $entityManager->flush();
    }
}
