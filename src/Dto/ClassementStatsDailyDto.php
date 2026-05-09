<?php

namespace App\Dto;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\ClassementStatsDailyStateProvider;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/classement/{id}/stats',
            name: 'app_api_classement_stats_get',
            requirements: ['id' => '\S+'],
            provider: ClassementStatsDailyStateProvider::class,
        ),
    ],
    formats: ['json' => ['application/json']],
)]
class ClassementStatsDailyDto
{
    public string $rankingId;
    public string $name;
    public string $period;
    public string $granularity;
    public string $startDate;
    public string $endDate;
    public int $totalViews;
    public int $periodViews;
    public array $stats = [];
}
