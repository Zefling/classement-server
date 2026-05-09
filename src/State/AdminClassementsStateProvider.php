<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Enum\CodeError;
use App\Entity\Classement;
use App\Entity\User;
use App\Service\EntityMapperService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class AdminClassementsStateProvider extends AbstractStateProvider implements ProviderInterface
{
    public function __construct(
        private ManagerRegistry $doctrine,
        private Security $security,
        private RequestStack $requestStack,
        private EntityMapperService $entityMapper,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $user = $this->security->getUser();

        if (!($user instanceof User) || !$user->isModerator()) {
            return $this->error(
                CodeError::USER_NO_PERMISSION,
                'moderation role required',
                Response::HTTP_UNAUTHORIZED
            );
        }

        $request = $this->requestStack->getCurrentRequest();
        $page = $request?->query->get('page') ?? 1;

        $order = trim($request?->query->get('order') ?? '');
        $direction = trim($request?->query->get('direction') ?? '') == 'ASC' ? 'ASC' : 'DESC';

        if ($order !== 'name' && $order !== 'category' && $order !== 'dateCreate') {
            $order = 'dateCreate';
            $direction = 'DESC';
        }

        $name = trim($request?->query->get('name') ?? '');
        $category = trim($request?->query->get('category') ?? '');
        $mode = trim($request?->query->get('mode') ?? '');

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

        $rep = $this->doctrine->getRepository(Classement::class);

        $classements = $name
            ? $rep->findByKey($params, $order, $direction, $page, 25)
            : $rep->findBy($params, [$order => $direction], 25, ($page - 1) * 25);

        $classementSubmit = $this->entityMapper->mapClassements($classements, true);

        $total = $name
            ? $rep->countByKey($params)
            : $rep->count($params);

        if (empty($classementSubmit)) {
            return $this->error(
                CodeError::CLASSEMENT_NOT_FOUND,
                'Classement not found',
                Response::HTTP_NOT_FOUND
            );
        }

        return $this->OK([
            'list' => $classementSubmit,
            'total' => $total
        ]);
    }
}
