<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Controller\ApiClassementController;
use App\Utils\EntityCommon;

#[ApiResource(
    collectionOperations: [
        'post_publication' => [
            'method' => 'POST',
            'path' => '/classement/add',
            'controller' => ApiClassementController::class,
        ],
    ],
    itemOperations: []
)]
class ClassementSubmit extends EntityCommon
{

    protected $name;

    protected $groupName;

    protected $picutre;

    protected $data = [];

    protected $templateId;

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

    public function getPicutre(): ?string
    {
        return $this->picutre;
    }

    public function setPicutre(string $picutre): self
    {
        $this->picutre = $picutre;

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
}
