<?php

namespace App\Controller;

use App\Controller\Common\CodeError;
use App\Controller\Common\AbstractApiController;
use App\Entity\Classement;
use App\Entity\ClassementHistory;
use App\Entity\ClassementHistoryList;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
class ApiGetClassementHistoryController extends AbstractApiController
{

    #[Route(
        '/api/classement/history/{id}',
        name: 'app_api_classement_history_get',
        methods: ['GET'],
        defaults: [
            '_api_resource_class' => ClassementHistoryList::class,
            '_api_item_operations_name' => 'app_api_classement_history_get',
        ],
    )]
    public function __invoke(string $id, ManagerRegistry $doctrine): Response
    {

        $rep = $doctrine->getRepository(Classement::class);
        $classement = $rep->findOneBy(['rankingId' => $id, 'deleted' => 0]);

        $rep = $doctrine->getRepository(ClassementHistory::class);
        $classements = [$this->mapClassement($classement), ...$rep->findByHistory($id)];

        return !empty($classements)
            ? $this->OK($classements)
            : $this->error(CodeError::CLASSEMENT_NOT_FOUND, 'History not found', Response::HTTP_NOT_FOUND);
    }
}
