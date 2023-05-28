<?php

namespace App\Controller;

use App\Controller\Common\CodeError;
use App\Controller\Common\AbstractApiController;
use App\Controller\Common\TokenAuthenticatedController;
use App\Entity\Classement;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[AsController]
class ApiAdminUsersController extends AbstractApiController implements TokenAuthenticatedController
{

    #[Route(
        '/api/admin/users',
        name: 'app_api_admin_users',
        methods: ['GET'],
        defaults: [
            '_api_resource_class' => User::class,
            '_api_collection_operations_name' => 'app_api_admin_users',
        ],
    )]
    public function __invoke(#[CurrentUser] ?User $user, Request $request, ManagerRegistry $doctrine): Response
    {
        if (!($user?->isModerator())) {
            return $this->error(CodeError::USER_NO_PERMISSION, 'moderation role required', Response::HTTP_UNAUTHORIZED);
        }

        // list
        $page = $request->query->get('page') ?? 1;
        $order = trim($request->query->get('order') ?? '');
        $direction = trim($request->query->get('direction') ?? '') === 'ASC' ? 'ASC' : 'DESC';

        if ($order !== 'username' && $order !== 'dateCreate') {
            $order = 'dateCreate';
            $direction = 'DESC';
        }

        $username = trim($request->query->get('username') ?? '');
        $params = [];

        if ($username) {
            $params['username'] = "%$username%";
        }

        $rep = $doctrine->getRepository(User::class);

        $users = $username
            ? $rep->findByKey($params, $order, $direction, $page, 25)
            : $rep->findBy($params, [$order => $direction], 25, ($page - 1) * 25);

        // total
        $total = $username
            ? $rep->countByKey($params)
            : $rep->count($params);

        if (!empty($users)) {

            // get classements
            $ids = [];
            foreach ($users as $user) {
                $ids[] = $user->getId();
            }
            $rep = $doctrine->getRepository(Classement::class);
            $classements = $rep->findByUserIds($users);

            $usersList = [];
            foreach ($users as $user) {
                $userArray = $user->toArray();

                // add classements for this user
                $userArray['classement'] = [];
                if (!empty($classements)) {
                    foreach ($classements as $classement) {
                        if ($classement->getUser()->getId() === $user->getId()) {
                            $userArray['classements'][] = $this->mapClassement($classement, true);
                        }
                    }
                }

                // remove password hash
                unset($userArray['password']);

                $usersList[] = $userArray;
            }

            return $this->OK([
                'list' => $usersList,
                'total' =>  $total
            ]);
        } else {
            return $this->error(CodeError::CLASSEMENT_NOT_FOUND, 'Classement not found', Response::HTTP_NOT_FOUND);
        }
    }
}
