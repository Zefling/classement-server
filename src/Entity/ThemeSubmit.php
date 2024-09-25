<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Controller\ApiAddThemeController;
use App\Utils\EntityCommon;

#[ApiResource(
    collectionOperations: [
        'post_publication' => [
            'method' => 'POST',
            'path' => '/theme',
            'name' => 'app_api_theme_add',
            'controller' => ApiAddThemeController::class,
        ],
    ],
    itemOperations: [],
    paginationEnabled: true,
)]
class ThemeSubmit extends EntityCommon
{

    private $name;

    private $mode;

    private $data = [];

    private $dateCreate;

    private $dateChange;

    #[\ApiPlatform\Core\Annotation\ApiProperty(identifier: true)]
    private $themeId;

    private $hidden;

    private $deleted;

    private $withHistory;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getMode(): ?string
    {
        return $this->mode;
    }

    public function setMode(string $mode): self
    {
        $this->mode = $mode;

        return $this;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function getThemeId(): ?string
    {
        return $this->themeId;
    }

    public function setThemeId(?string $themeId): self
    {
        $this->themeId = $themeId;

        return $this;
    }

    public function getDateCreate(): ?\DateTimeInterface
    {
        return $this->dateCreate;
    }

    public function setDateCreate(\DateTimeInterface $dateCreate): self
    {
        $this->dateCreate = $dateCreate;

        return $this;
    }

    public function getDateChange(): ?\DateTimeInterface
    {
        return $this->dateChange;
    }

    public function setDateChange(?\DateTimeInterface $dateChange): self
    {
        $this->dateChange = $dateChange;

        return $this;
    }

    public function getHidden(): ?bool
    {
        return $this->hidden;
    }

    public function setHidden(?bool $hidden): self
    {
        $this->hidden = $hidden;

        return $this;
    }

    public function getDeleted(): ?bool
    {
        return $this->deleted;
    }

    public function setDeleted(?bool $deleted): self
    {
        $this->deleted = $deleted;

        return $this;
    }

    public function getWithHistory(): ?int
    {
        return $this->withHistory;
    }

    public function setWithHistory(?int $withHistory): self
    {
        $this->withHistory = $withHistory;

        return $this;
    }
}
