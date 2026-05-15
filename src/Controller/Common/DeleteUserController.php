<?php

namespace App\Controller\Common;

use App\Entity\Preferences;
use App\Entity\Token;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;

class DeleteUserController extends AbstractApiController
{

    public function invoke(User $user, ManagerRegistry $doctrine)
    {
        $entityManager = $doctrine->getManager();

        // remove tokens

        $rep = $doctrine->getRepository(Token::class);
        $rep->removeByUser($user);

        // remove preferences

        $preferencesRep = $doctrine->getRepository(Preferences::class);
        $preferences = $preferencesRep->findByUser($user);
        if ($preferences) {
            $entityManager->remove($preferences);
        }

        // remove all user informations

        $user->setUsername('')
            ->setPassword('')
            ->setEmail('')
            ->setRoles([])
            ->setDeleted(true);

        $entityManager->persist($user);
        $entityManager->flush();
    }
}
