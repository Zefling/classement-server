<?php

namespace App\Controller\Common;

use App\Controller\Common\CodeError;
use App\Entity\Classement;
use App\Entity\ClassementHistory;
use App\Entity\Theme;
use App\Entity\User;
use App\Utils\Utils;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;


class GetUserController extends AbstractApiController
{

    /**
     * @param $username username
     * @param $doctrine
     * @param $hidden include hidden data
     * @param $email include email data
     */
    public function invoke(
        string $username,
        ManagerRegistry $doctrine,
        bool $hidden = true,
        bool $email = true
    ): Response {
        // control db
        $repUser = $doctrine->getRepository(User::class);
        $user = $repUser->findOneBy(['username' => $username, 'deleted' => false]);

        if ($user !== null) {

            // control db
            $repClassement = $doctrine->getRepository(Classement::class);
            $classements = $repClassement->findBy([
                'User'    => $user,
                'deleted' => false,
                ...($hidden ? [] : ['hidden' => false])
            ]);

            if ($hidden) {
                $repTheme = $doctrine->getRepository(Theme::class);
                $themes = $repTheme->findBy([
                    'User'    => $user,
                    'deleted' => false,
                ]);
            }

            // add total ranking by template
            if (!empty($classements)) {
                $listTemplateIds = [];
                foreach ($classements as $classement) {
                    $listTemplateIds[] = $classement->getTemplateId();
                }
                if (!empty($listTemplateIds)) {
                    $counts = $doctrine->getRepository(Classement::class)->countByTemplateId($listTemplateIds);

                    foreach ($classements as $classement) {
                        if (isset($counts[$classement->getTemplateId()])) {
                            $classement->setTemplateTotal($counts[$classement->getTemplateId()]);
                        }
                    }
                }
            }

            // add total history by ranking
            if (!empty($classements)) {
                $listRankingIds = [];
                foreach ($classements as $classement) {
                    $listRankingIds[] = $classement->getRankingId();
                }
                if (!empty($listTemplateIds)) {
                    $counts = $doctrine->getRepository(ClassementHistory::class)->countByRankingId($listRankingIds);

                    foreach ($classements as $classement) {
                        if (isset($counts[$classement->getRankingId()])) {
                            $classement->setWithHistory($counts[$classement->getRankingId()]);
                        }
                    }
                }
            }

            $userArray = $user->toArray();
            $userArray['classements'] = $this->mapClassements($classements, $hidden);
            if ($hidden) {
                $userArray['themes'] = $this->mapThemes($themes);
            }

            // remove unnecessary data
            unset(
                $userArray['password'],
                $userArray['plainPassword'],
                $userArray['isValidated'],
                $userArray['deleted']
            );
            if (!$email) {
                unset($userArray['email']);
            }

            if ($userArray['avatar']) {
                $userArray['avatarUrl'] = Utils::siteURL() . "/images/avatar/{$user->getId()}.webp";
            }

            // return updated data
            return $this->OK($userArray);
        } else {
            return $this->error(CodeError::USER_NOT_FOUND, 'User not found', Response::HTTP_NOT_FOUND);
        }
    }
}
