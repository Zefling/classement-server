<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Controller\ApiAddClassementController;
use App\Controller\ApiDeleteClassementsController;
use App\Controller\ApiGetCategoriesHomeController;
use App\Controller\ApiGetClassementController;
use App\Controller\ApiGetClassementsController;
use App\Utils\EntityCommon;

#[ApiResource(
    collectionOperations: [
        'post_publication' => [
            'method' => 'POST',
            'path' => '/classement/add',
            'name' => 'app_api_classement_add',
            'controller' => ApiAddClassementController::class,
        ],
        'get_publications' => [
            'method' => 'GET',
            'path' => '/classements',
            'name' => 'app_api_classements_get',
            'controller' => ApiGetClassementsController::class,
        ],
        'app_api_group_home_get' => [
            'method' => 'GET',
            'path' => '/groups/home',
            'name' => 'app_api_group_home_get',
            'controller' => ApiGetCategoriesHomeController::class,
        ],
    ],
    itemOperations: [
        'get_publication' => [
            'method' => 'GET',
            'path' => '/classement/{id}',
            'requirements' => ['id' => '\s+'],
            'name' => 'app_api_classement_get',
            'controller' => ApiGetClassementController::class,
        ],
        'delete_publication' => [
            'method' => 'DELETE',
            'path' => '/classement/{id}',
            'requirements' => ['id' => '\s+'],
            'name' => 'app_api_classement_delete',
            'controller' => ApiDeleteClassementsController::class,
        ],
    ],
    paginationEnabled: true,
)]
class ClassementSubmit extends EntityCommon
{

    protected $name;

    protected $category;

    protected $data = [];

    protected $templateId;

    #[\ApiPlatform\Core\Annotation\ApiProperty(identifier: true)]
    protected $rankingId;

    protected $banner;

    protected $dateCreate;

    protected $dateChange;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): self
    {
        $this->category = $category;

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

    public function getTemplateId(): ?string
    {
        return $this->templateId;
    }

    public function setTemplateId(string $templateId): self
    {
        $this->templateId = $templateId;

        return $this;
    }

    public function getRankingId(): ?string
    {
        return $this->rankingId;
    }

    public function setRankingId(string $rankingId): self
    {
        $this->rankingId = $rankingId;

        return $this;
    }

    public function getBanner(): ?string
    {
        return $this->banner;
    }

    public function setBanner(string $banner): self
    {
        $this->banner = $banner;

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
}
