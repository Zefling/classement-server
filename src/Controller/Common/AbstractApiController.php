<?php

namespace App\Controller\Common;

use App\Utils\Utils;
use App\Entity\Classement;
use App\Entity\ClassementSubmit;
use Error;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class AbstractApiController extends AbstractController
{

    public function error($code, $message, $codeHttp = Response::HTTP_BAD_REQUEST): Response
    {
        return $this->json(
            [
                'errorCode' => $code,
                'errorMessage' => $message,
                'status' => 'KO',
                'code' => $codeHttp
            ],
            $codeHttp
        );
    }

    public function OK($message = null): Response
    {
        return $message
            ? $this->json(
                [
                    'message' => $message,
                    'code' => Response::HTTP_OK,
                    'status' => 'OK'
                ],
                Response::HTTP_OK
            )
            : $this->json(
                [
                    'code' => Response::HTTP_OK,
                    'status' => 'OK'
                ],
                Response::HTTP_OK
            );
    }


    public function mapClassement(?Classement $classement, $withStatus = false): ?array
    {
        if (!$classement) {
            return null;
        }

        // mapping
        $classementSubmit = new ClassementSubmit();
        $classementSubmit->setTemplateId($classement->getTemplateId());
        $classementSubmit->setRankingId($classement->getRankingId());
        $classementSubmit->setParentId($classement->getParentId());
        $classementSubmit->setLocalId($classement->getLocalId());
        $classementSubmit->setData(Utils::formatData($classement->getData()));
        $classementSubmit->setBanner(Utils::siteURL() . $classement->getBanner());
        $classementSubmit->setName($classement->getName());
        $classementSubmit->setDateCreate($classement->getDateCreate());
        $classementSubmit->setDateChange($classement->getDateChange());
        $classementSubmit->setUser($classement->getUser()->getUsername());
        $classementSubmit->setTotalGroups($classement->getTotalGroups());
        $classementSubmit->setTotalItems($classement->getTotalItems());
        $classementSubmit->setTemplateTotal($classement->getTemplateTotal());
        $classementSubmit->setParent($classement->getParent());

        if ($withStatus) {
            $classementSubmit->setHidden($classement->getHidden());
            $classementSubmit->setDeleted($classement->getDeleted());
        }

        try {
            $classementSubmit->setCategory($classement->getCategory()->value);
        } catch (Error $e) {
            // ignore the category
        }

        return  $classementSubmit->toArray();
    }

    public function mapClassements(array &$classements, $withStatus = false): array
    {
        $list = [];
        if (!empty($classements)) {
            foreach ($classements as $classement) {
                $list[] = $this->mapClassement($classement, $withStatus);
            }
        }

        return $list;
    }
}
