<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Controller\ApiUserUpdateEmailController;
use App\Utils\EntityCommon;

#[ApiResource(
    collectionOperations: [
        'app_api_user_update_mail' => [
            'method' => 'POST',
            'path' => '/user/update/email',
            'name' => 'app_api_user_update_mail',
            'controller' => ApiUserUpdateEmailController::class,
        ],
    ],
    itemOperations: []
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
