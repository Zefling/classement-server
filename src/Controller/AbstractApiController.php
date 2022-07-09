<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Classement;
use App\Entity\ClassementSubmit;
use Error;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class AbstractApiController extends AbstractController
{

    public function error($code, $message, $codeHttp = Response::HTTP_INTERNAL_SERVER_ERROR): Response
    {
        return $this->json(
            [
                'errorCode' => $code,
                'errorMessage' => $message,
                'status' => 'KO',
                'code' => $codeHttp,
            ],
            $codeHttp
        );
    }


    public function mapClassement(?Classement $classement): ?array
    {
        if (!$classement) {
            return null;
        }

        // mapping
        $classementSubmit = new ClassementSubmit();
        $classementSubmit->setTemplateId($classement->getTemplateId());
        $classementSubmit->setRankingId($classement->getRankingId());
        $classementSubmit->setData(Utils::formatData($classement->getData()));
        $classementSubmit->setBanner(Utils::siteURL() . $classement->getBanner());
        $classementSubmit->setName($classement->getName());
        $classementSubmit->setDateCreate($classement->getDateCreate());
        $classementSubmit->setUser($classement->getUser()->getUsername());

        try {
            $classementSubmit->setCategory($classement->getCategory()->value);
        } catch (Error $e) {
            // ignore the category
        }

        return  $classementSubmit->toArray();
    }

    public function mapClassements(array &$classements): array
    {
        $list = [];
        if (!empty($classements)) {
            foreach ($classements as $classement) {
                $list[] = $this->mapClassement($classement);
            }
        }

        return $list;
    }
}
