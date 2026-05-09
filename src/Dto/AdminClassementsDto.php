<?php

namespace App\Dto;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\AdminClassementsStateProvider;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/admin/classements',
            name: 'app_api_admin_classements_list',
            provider: AdminClassementsStateProvider::class,
        ),
    ],
    formats: ['json' => ['application/json']],
)]
class AdminClassementsDto
{
    public array $list = [];
    public int $total = 0;
}
