<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Controller\ApiLoginController;
use App\Utils\EntityCommon;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ApiResource(
    collectionOperations: [
        'app_api_login' => [
            'method' => 'POST',
            'path' => '/login',
            'name' => 'app_api_login',
            'controller' => ApiLoginController::class,
        ],
    ],
    itemOperations: []
)]
class UserLogin extends EntityCommon implements PasswordAuthenticatedUserInterface
{
    protected string $username;

    protected string $password;

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }
}
