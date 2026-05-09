<?php

namespace App\Dto;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\ClassementVotesStateProvider;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/classement/{id}/votes',
            name: 'app_api_classement_votes_get',
            requirements: ['id' => '\S+'],
            priority: 10,
            provider: ClassementVotesStateProvider::class,
        ),
    ],
    formats: ['json' => ['application/json']],
)]
class ClassementVotesDto
{
    #[ApiProperty(
        description: 'Vote counts by emoji type',
        openapiContext: [
            'type' => 'object',
            'example' => ['👍' => 5, '🥰' => 3, '🤣' => 2],
            'additionalProperties' => ['type' => 'integer'],
        ]
    )]
    public array $votes = [];

    #[ApiProperty(
        description: 'Current user\'s votes (array of emoji strings)',
        openapiContext: [
            'type' => 'array',
            'items' => ['type' => 'string'],
            'example' => ['👍', '🥰'],
        ]
    )]
    public array $userVotes = [];
}
