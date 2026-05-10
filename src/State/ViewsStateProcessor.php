<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\State\ProviderInterface;
use App\Enum\CodeError;
use App\Repository\ClassementStatsRepository;
use App\Service\ViewTracker;
use Doctrine\Persistence\ManagerRegistry;
use App\State\AbstractStateProvider;

class ViewsStateProcessor extends AbstractStateProvider implements ProcessorInterface, ProviderInterface
{
    public function __construct(
        private ManagerRegistry $doctrine,
        private ViewTracker $viewTracker,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        // Return a dummy DTO object for API Platform
        $dto = new \App\Dto\ViewsDto();
        $dto->viewCount = 0;
        return $dto;
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $id = $uriVariables['id'] ?? null;

        if (empty($id) || !trim($id)) {
            return $this->error(
                CodeError::INVALID_DATA,
                'rankingId is required'
            );
        }

        $rankingId = trim($id);

        // Check if the ranking exists
        $classementRepository = $this->doctrine->getRepository(\App\Entity\Classement::class);
        if (!$classementRepository->exists($rankingId)) {
            return $this->error(CodeError::CLASSEMENT_NOT_FOUND, 'Classement not found');
        }

        $statsRepository = $this->doctrine->getRepository(\App\Entity\ClassementStats::class);

        if (!$statsRepository instanceof ClassementStatsRepository) {
            return $this->error(CodeError::STATS_ERROR, 'Repository not found');
        }

        // Increment view count only if not viewed recently by this user
        if ($this->viewTracker->shouldCountView($rankingId)) {
            $statsRepository->incrementViewCount($rankingId);
        }

        $viewCount = $statsRepository->getViewCount($rankingId);

        return $this->OK(['viewCount' => $viewCount]);
    }
}
