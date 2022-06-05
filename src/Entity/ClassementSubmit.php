<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Controller\ApiClassementController;
use App\Controller\ApiGetClassementController;
use App\Controller\ApiGetClassementsController;
use App\Utils\EntityCommon;

#[ApiResource(
    collectionOperations: [
        'post_publication' => [
            'method' => 'POST',
            'path' => '/classement/add',
            'name' => 'app_api_classement_add',
            'controller' => ApiClassementController::class,
        ],
        'get_publications' => [
            'method' => 'GET',
            'path' => '/classements',
            'name' => 'app_api_classements_get',
            'controller' => ApiGetClassementsController::class,
        ],
    ],
    itemOperations: [
        'get_publication' => [
            'method' => 'GET',
            'path' => '/classement/{id}',
            'requirements' => ['id' => '\d+'],
            'name' => 'app_api_classement_get',
            'controller' => ApiGetClassementController::class,
        ],
    ],
    paginationEnabled: true,
)]
class ClassementSubmit extends EntityCommon
{

    protected $name;

    protected $groupName;

    protected $data = [];

    protected $templateId;

    protected $templateMode;

    #[\ApiPlatform\Core\Annotation\ApiProperty(identifier: true)]
    protected $rankingId;

    protected $banner;

    protected $parentId;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getGroupName(): ?string
    {
        return $this->groupName;
    }

    public function setGroupName(string $groupName): self
    {
        $this->groupName = $groupName;

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

    public function getParentId(): ?string
    {
        return $this->parentId;
    }

    public function setParentId(string $parentId): self
    {
        $this->parentId = $parentId;

        return $this;
    }

    public function getTemplateMode(): ?bool
    {
        return $this->templateMode;
    }

    public function setTemplateMode(bool $templateMode): self
    {
        $this->templateMode = $templateMode;

        return $this;
    }
}
