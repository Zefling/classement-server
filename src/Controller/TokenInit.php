<?php

namespace App\Controller;

use App\Entity\Token;
use App\Entity\User;
use DateInterval;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;

class TokenInit extends AbstractApiController
{

    public function initToken(
        User $user,
        ManagerRegistry $doctrine,
        string $origin = 'login',
        string $duration = '1 week'
    ) {

        $tokenRep = $doctrine->getRepository(Token::class);
        $token = $tokenRep->findOneBy(['userId' => $user->getId(), 'role' => $origin]);

        $tokenDuration = DateInterval::createFromDateString($duration);

        if ($token === null) {
            $token = new Token($user, $tokenDuration, $origin);
        } else {
            $date = $token->getDate();
            $validity = $token->getValidity();
            if ($date && $validity) {
                if ($validity->getTimestamp() - (new DateTime())->getTimestamp() < 0) {
                    $token->renewToken();
                }
                $token->resetDate($tokenDuration);
            } else {
                $token->renewToken();
                $token->reset($tokenDuration);
            }
        }

        $entityManager = $doctrine->getManager();
        $entityManager->persist($token);
        $entityManager->flush();

        return $token;
    }
}
