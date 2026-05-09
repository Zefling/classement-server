<?php

namespace App\Dto;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\StatsStateProvider;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/admin/stats',
            name: 'app_api_admin_classements',
            provider: StatsStateProvider::class,
        ),
    ],
    formats: ['json' => ['application/json']],
)]
class StatsDto
{
    #[ApiProperty(
        description: 'Array of statistics by date',
        openapiContext: [
            'type' => 'array',
            'items' => [
                'type' => 'object',
                'properties' => [
                    'date' => ['type' => 'string', 'format' => 'date'],
                    'count' => ['type' => 'integer'],
                    'validated' => ['type' => 'integer'],
                    'deleted' => ['type' => 'integer'],
                    'hide' => ['type' => 'integer'],
                    'parent' => ['type' => 'integer'],
                ],
            ],
        ]
    )]
    public array $stats = [];
}
