<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\Controller\ApiUserUpdateEmailController;
use App\Utils\EntityCommon;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/user/update/email',
            name: 'app_api_user_update_mail',
            controller: ApiUserUpdateEmailController::class,
        ),
    ],
)]
class UserEmail extends EntityCommon
{
    protected string $emailOld;

    protected string $emailNew;

    public function getEmailOld(): string
    {
        return $this->emailOld;
    }

    public function setEmailOld(string $emailOld): self
    {
        $this->emailOld = $emailOld;

        return $this;
    }

    public function getEmailNew(): ?string
    {
        return $this->emailNew;
    }

    public function setEmailNew(string $emailNew): self
    {
        $this->emailNew = $emailNew;

        return $this;
    }
}
