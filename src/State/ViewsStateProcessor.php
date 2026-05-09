<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Enum\CodeError;
use App\Repository\ClassementStatsRepository;
use App\Service\ViewTracker;
use Doctrine\Persistence\ManagerRegistry;
use App\State\AbstractStateProvider;

class ViewsStateProcessor extends AbstractStateProvider implements ProcessorInterface
{
    public function __construct(
        private ManagerRegistry $doctrine,
        private ViewTracker $viewTracker,
    ) {}

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
