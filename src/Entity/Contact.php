<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\Controller\ApiContactController;
use App\Utils\EntityCommon;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/contact',
            name: 'app_api_contact',
            controller: ApiContactController::class,
        ),
    ],
)]
class Contact extends EntityCommon
{
    protected string $username;

    protected string $email;

    protected string $message;

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

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

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }
}
