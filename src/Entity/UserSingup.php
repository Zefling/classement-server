<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Controller\ApiSignupController;

#[ApiResource(
    collectionOperations: [
        'app_api_signup' => [
            'method' => 'POST',
            'path' => '/singup',
            'name' => 'app_api_signup',
            'controller' => ApiSignupController::class
        ],
    ],
    itemOperations: []
)]
class UserSingup
{
    private $password;

    private $username;

    private $email;

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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }
}
