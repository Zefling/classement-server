<?php

namespace App\Controller;

use App\Controller\Common\AbstractApiController;
use App\Controller\Common\CodeError;
use App\Controller\Common\TokenAuthenticatedController;
use App\Entity\Classement;
use App\Entity\ClassementSubmit;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[AsController]
class ApiAdminClassementsController extends AbstractApiController implements TokenAuthenticatedController
{

    #[Route(
        '/api/admin/classements',
        name: 'app_api_admin_classements',
        methods: ['GET'],
        defaults: [
            '_api_resource_class' => ClassementSubmit::class,
            '_api_item_operations_name' => 'app_api_admin_classements',
        ],
    )]
    public function __invoke(#[CurrentUser] ?User $user, Request $request, ManagerRegistry $doctrine): Response
    {
        if (!($user?->isModerator())) {
            return $this->error(CodeError::USER_NO_PERMISSION, 'moderation role required', Response::HTTP_UNAUTHORIZED);
        }

        // list
        $page = $request->query->get('page') ?? 1;
        $order = $request->query->get('order');
        $direction = trim($request->query->get('direction')) == 'ASC' ? 'ASC' : 'DESC';

        if ($order !== 'name' && $order !== 'category' && $order !== 'dateCreate') {
            $order = 'dateCreate';
            $direction = 'DESC';
        }

        $rep = $doctrine->getRepository(Classement::class);
        $classements = $rep->findBy([], [$order => $direction], 25, ($page - 1) * 25);

        $classementSubmit = $this->mapClassements($classements, true);

        // total
        $total = $rep->count([]);

        if (!empty($classementSubmit)) {
            return $this->OK([
                'list' => $classementSubmit,
                'total' =>  $total
            ]);
        } else {
            return $this->error(CodeError::CLASSEMENT_NOT_FOUND, 'Classement not found', Response::HTTP_NOT_FOUND);
        }
    }
}
