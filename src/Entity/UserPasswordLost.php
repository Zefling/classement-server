<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\Controller\ApiPasswordLostController;
use App\Utils\EntityCommon;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/{_locale}/password-lost',
            name: 'app_api_password_lost',
            controller: ApiPasswordLostController::class,
            requirements: ['_locale' => '%app.supported_locales%'],
        ),
    ],
)]
class UserPasswordLost extends EntityCommon
{
    protected string $identifier;

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }
}
