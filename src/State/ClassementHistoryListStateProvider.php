<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Enum\CodeError;
use App\Entity\Classement;
use App\Entity\ClassementHistory;
use App\Service\EntityMapperService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;

class ClassementHistoryListStateProvider extends AbstractStateProvider implements ProviderInterface
{
    public function __construct(
        private ManagerRegistry $doctrine,
        private EntityMapperService $entityMapper,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $id = $uriVariables['id'] ?? null;

        $rep = $this->doctrine->getRepository(Classement::class);
        $classement = $rep->findOneBy(['rankingId' => $id, 'deleted' => 0]);

        $rep = $this->doctrine->getRepository(ClassementHistory::class);
        $classements = [$this->entityMapper->mapClassement($classement), ...$rep->findByHistory($id)];

        if (empty($classements)) {
            return $this->error(
                CodeError::CLASSEMENT_NOT_FOUND,
                'History not found',
                Response::HTTP_NOT_FOUND
            );
        }

        return $this->OK($classements);
    }
}
