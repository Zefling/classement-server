<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Enum\CodeError;
use App\Entity\Classement;
use App\Entity\User;
use DateTime;
use DateTimeInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

use App\State\AbstractStateProvider;

class StatsStateProvider extends AbstractStateProvider implements ProviderInterface
{
    public function __construct(
        private ManagerRegistry $doctrine,
        private Security $security,
        private RequestStack $requestStack,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $user = $this->security->getUser();

        if (!($user instanceof User) || !$user->isAdmin()) {
            return $this->error(
                CodeError::USER_NO_PERMISSION,
                'moderation role required',
                Response::HTTP_UNAUTHORIZED
            );
        }

        $request = $this->requestStack->getCurrentRequest();
        $target = $request?->query->get('target') ?? 'user';
        $period = $request?->query->get('period') ?? 'day';

        if ($target === 'classement') {
            $rep = $this->doctrine->getRepository(Classement::class);
        } else {
            $rep = $this->doctrine->getRepository(User::class);
        }

        $startDate = $request?->query->get('startDate') ?? null;
        $endDate = $request?->query->get('endDate') ?? null;

        $today = new DateTime();
        $oneYearAgo = (clone $today)->modify('-1 year');

        $startDate = $startDate !== null ? new DateTime($startDate) : $oneYearAgo;
        $endDate = $endDate !== null ? new DateTime($endDate) : $today;

        $this->correctDates($startDate, $endDate);

        if ($period === 'month') {
            $stats = $rep->getStatsByMonth($startDate, $endDate);
        } else if ($period === 'week') {
            $stats = $rep->getStatsByWeek($startDate, $endDate);
        } else {
            $stats = $rep->getStatsByDay($startDate, $endDate);
        }

        if (!empty($stats)) {
            if ($target === 'classement') {
                foreach ($stats as &$result) {
                    $result['deleted'] = (int) $result['deleted'];
                    $result['hide'] = (int) $result['hide'];
                    $result['parent'] = (int) $result['parent'];
                }
            } else {
                foreach ($stats as &$result) {
                    $result['deleted'] = (int) $result['deleted'];
                    $result['validated'] = (int) $result['validated'];
                }
            }

            return $this->OK(['stats' => $stats]);
        } else {
            return $this->error(CodeError::STATS_ERROR, 'Stats error', Response::HTTP_NOT_FOUND);
        }
    }

    private function correctDates(DateTimeInterface &$startDate, DateTimeInterface &$endDate): void
    {
        $today = new DateTime();
        $oneMonthAgo = (clone $today)->modify('-1 month');

        if ($endDate > $today) {
            $endDate = $today;
        }

        if ($startDate > $oneMonthAgo) {
            $startDate = $oneMonthAgo;
        }

        if ($startDate >= $endDate) {
            $startDate = (clone $endDate)->modify('-1 month');
        }

        $interval = $startDate->diff($endDate);
        $months = $interval->y * 12 + $interval->m;

        if ($months < 1) {
            $startDate = (clone $endDate)->modify('-1 month');
        }

        if ($months > 12) {
            $startDate = (clone $endDate)->modify('-1 year');
        }
    }
}
