<?php

namespace App\Controller;

use App\Controller\Common\CodeError;
use App\Controller\Common\AbstractApiController;
use App\Entity\Classement;
use App\Entity\ClassementSubmit;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
class ApiGetClassementController extends AbstractApiController
{

    #[Route(
        '/api/classement/{id}',
        name: 'app_api_classement_get',
        methods: ['GET'],
        defaults: [
            '_api_resource_class' => ClassementSubmit::class,
            '_api_item_operations_name' => 'get_publication',
        ],
    )]
    public function __invoke(string $id, ManagerRegistry $doctrine): Response
    {
        // control db
        $rep = $doctrine->getRepository(Classement::class);
        $classement = $rep->findOneBy(['rankingId' => $id, 'deleted' => false]);

        if ($classement !== null) {
            // add total ranking by template
            $counts = $doctrine->getRepository(Classement::class)->countByTemplateId([$classement->getTemplateId()]);
            if (isset($counts[$classement->getTemplateId()])) {
                $classement->setTemplateTotal($counts[$classement->getTemplateId()]);
            }

            $classementSubmit = $this->mapClassement($classement);

            // return updated data
            return $this->OK($classementSubmit);
        } else {
            return $this->error(CodeError::CLASSEMENT_NOT_FOUND, 'Classement not found', Response::HTTP_NOT_FOUND);
        }
    }
}
