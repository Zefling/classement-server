<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Controller\ApiGetClassementHistoryController;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/classement/history/{id}',
            name: 'app_api_classement_history_get',
            controller: ApiGetClassementHistoryController::class,
            requirements: ['id' => '\S+'],
            priority: 1
        ),
    ],
)]
class ClassementHistoryList
{

    private $id;

    private $name;

    private $date;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): ?int
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDate(): ?int
    {
        return $this->date;
    }

    public function setDate(string $date): self
    {
        $this->date = $date;

        return $this;
    }
}
