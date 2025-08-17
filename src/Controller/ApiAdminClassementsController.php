<?php

namespace App\Controller;

use App\Controller\Common\AbstractApiController;
use App\Controller\Common\CodeError;
use App\Controller\Common\TokenAuthenticatedController;
use App\Entity\Classement;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[AsController]
class ApiAdminClassementsController extends AbstractApiController implements TokenAuthenticatedController
{

    // required API Platform 3.x
    public static function getName(): string
    {
        return 'app_api_admin_classements';
    }

    public function __invoke(#[CurrentUser] ?User $user, Request $request, ManagerRegistry $doctrine): Response
    {
        if (!($user?->isModerator())) {
            return $this->error(CodeError::USER_NO_PERMISSION, 'moderation role required', Response::HTTP_UNAUTHORIZED);
        }

        // list
        $page = $request->query->get('page') ?? 1;

        $order = trim($request->query->get('order') ?? '');
        $direction = trim($request->query->get('direction') ?? '') == 'ASC' ? 'ASC' : 'DESC';

        if ($order !== 'name' && $order !== 'category' && $order !== 'dateCreate') {
            $order = 'dateCreate';
            $direction = 'DESC';
        }

        $name = trim($request->query->get('name') ?? '');
        $category = trim($request->query->get('category') ?? '');
        $mode = trim($request->query->get('mode') ?? '');

        $params = [];
        if ($category) {
            $params['category'] = $category;
        }
        if ($mode) {
            $params['mode'] = $mode;
        }
        if ($name) {
            $params['name'] = "%$name%";
        }

        $rep = $doctrine->getRepository(Classement::class);

        $classements = $name
            ? $rep->findByKey($params, $order, $direction, $page, 25)
            : $rep->findBy($params, [$order => $direction], 25, ($page - 1) * 25);

        $classementSubmit = $this->mapClassements($classements, true);

        // total
        $total = $name
            ? $rep->countByKey($params)
            : $rep->count($params);

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
