<?php

namespace App\Dto;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\ClassementVotesAdminStateProvider;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/admin/classement/{id}/votes',
            name: 'app_api_admin_classement_votes_get',
            requirements: ['id' => '\S+'],
            priority: 10,
            provider: ClassementVotesAdminStateProvider::class,
        ),
    ],
    formats: ['json' => ['application/json']],
)]
class ClassementVotesAdminDto
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

    #[ApiProperty(description: 'Total number of votes (only if details or byUser query param is true)')]
    public ?int $totalVotes = null;

    #[ApiProperty(
        description: 'Detailed vote information (only if details=true query param)',
        openapiContext: [
            'type' => 'array',
            'items' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'voteType' => ['type' => 'string'],
                    'userId' => ['type' => 'integer'],
                    'username' => ['type' => 'string'],
                    'dateCreate' => ['type' => 'string', 'format' => 'date-time'],
                ],
            ],
        ]
    )]
    public ?array $voteDetails = null;

    #[ApiProperty(description: 'Total number of users who voted (only if byUser=true query param)')]
    public ?int $totalUsers = null;

    #[ApiProperty(
        description: 'Votes grouped by user (only if byUser=true query param)',
        openapiContext: [
            'type' => 'array',
            'items' => [
                'type' => 'object',
                'properties' => [
                    'userId' => ['type' => 'integer'],
                    'username' => ['type' => 'string'],
                    'votes' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                    ],
                ],
            ],
        ]
    )]
    public ?array $votesByUser = null;
}
