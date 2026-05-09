<?php

namespace App\Dto;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\ClassementHistoryListStateProvider;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/classement/history/{id}',
            name: 'app_api_classement_history_get',
            requirements: ['id' => '\S+'],
            provider: ClassementHistoryListStateProvider::class,
        ),
    ],
    formats: ['json' => ['application/json']],
)]
class ClassementHistoryListDto
{
    public array $classements = [];
}
