<?php

namespace App\Controller\Common;

use App\Controller\Common\AbstractApiController;
use App\Controller\Common\CodeError;
use App\Controller\Common\TokenAuthenticatedController;
use App\Entity\Classement;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Error;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ClassementStatusController extends AbstractApiController implements TokenAuthenticatedController
{

    public function update(
        Classement $classement,
        Request $request,
        ObjectRepository $rep,
        ManagerRegistry $doctrine
    ): Response {
        if ($classement !== null) {

            $params = $request->toArray();

            $status = $params['status'] === true || $params['status'] === 'true' ? true : false;
            $type = $params['type'];

            $change = false;

            if ($type === 'delete') {
                $classement->setDeleted($status);
                if ($status) {
                    $classement->setParent(false);
                }
                $change = true;
            } else if ($type === 'hide') {
                $classement->setHidden($status);
                if ($status) {
                    $classement->setParent(false);
                }
                $change = true;
            } else {
                return $this->error(CodeError::STATUS_ERROR, 'Status in error');
            }

            try {
                //save db data
                $entityManager = $doctrine->getManager();
                $entityManager->persist($classement);
                $entityManager->flush();

                $resultChange = [$classement];

                if ($change) {

                    // remove parent

                    $classementTemplate = $rep->findByTemplateParent($classement->getTemplateId());

                    if (count($classementTemplate)) {
                        $classementTemplate[0]->setParent(false);
                        $entityManager->persist($classementTemplate[0]);
                        $entityManager->flush();

                        $resultChange[] = $classementTemplate[0];
                    }

                    // first new parent

                    $classementTemplateFirst = $rep->findByTemplateFirst($classement->getTemplateId());

                    if (count($classementTemplateFirst)) {
                        $classementTemplateFirst[0]->setParent(true);
                        $entityManager->persist($classementTemplateFirst[0]);
                        $entityManager->flush();

                        $resultChange[] = $classementTemplateFirst[0];
                    }
                };

                return $this->OK($this->mapClassements($resultChange, true));
            } catch (Error $e) {
                return $this->error(
                    CodeError::DB_SAVE_REQUEST_ERROR,
                    'DB save error',
                    Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }
        } else {
            return $this->error(CodeError::CLASSEMENT_NOT_FOUND, 'Classement not found', Response::HTTP_NOT_FOUND);
        }
    }
}
