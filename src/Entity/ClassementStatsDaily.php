<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Controller\ApiGetClassementStatsController;
use App\Repository\ClassementStatsDailyRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/classement/{id}/stats',
            name: 'app_api_classement_stats_get',
            controller: ApiGetClassementStatsController::class,
            requirements: ['id' => '\S+'],
            priority: 10
        ),
    ],
)]
#[
    ORM\Entity(repositoryClass: ClassementStatsDailyRepository::class),
    ORM\Table(name: 'classement_stats_daily'),
    Index(
        name: "ranking_date_idx",
        columns: ["ranking_id", "date"]
    ),
    Index(
        name: "date_idx",
        columns: ["date"]
    ),
    ORM\UniqueConstraint(
        name: "unique_ranking_date",
        columns: ["ranking_id", "date"]
    )
]
class ClassementStatsDaily
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $rankingId;

    #[ORM\Column(type: 'date')]
    private \DateTimeInterface $date;

    #[ORM\Column(type: 'integer', options: ["default" => 0])]
    private int $viewCount = 0;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRankingId(): string
    {
        return $this->rankingId;
    }

    public function setRankingId(string $rankingId): self
    {
        $this->rankingId = $rankingId;
        return $this;
    }

    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;
        return $this;
    }

    public function getViewCount(): int
    {
        return $this->viewCount;
    }

    public function setViewCount(int $viewCount): self
    {
        $this->viewCount = $viewCount;
        return $this;
    }

    public function incrementViewCount(): self
    {
        $this->viewCount++;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}
