<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Controller\ApiPasswordLostController;
use App\Utils\EntityCommon;

#[ApiResource(
    collectionOperations: [
        'app_api_password_lost' => [
            'method' => 'POST',
            'path' => '/{_locale<%app.supported_locales%>}/password-lost',
            'name' => 'app_api_password_lost',
            'controller' => ApiPasswordLostController::class,
        ],
    ],
    itemOperations: []
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
