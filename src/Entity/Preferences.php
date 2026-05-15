<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use App\Controller\ApiGetPreferencesController;
use App\Controller\ApiSavePreferencesController;
use App\Repository\PreferencesRepository;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/preferences',
            name: 'app_api_preferences_save',
            controller: ApiSavePreferencesController::class,
        ),
        new Get(
            uriTemplate: '/preferences',
            name: 'app_api_preferences_get',
            controller: ApiGetPreferencesController::class,
        ),
    ],
)]
#[ORM\Entity(repositoryClass: PreferencesRepository::class)]
class Preferences
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, unique: true, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: 'text')]
    private string $encryptedData;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $dateCreate;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $dateChange = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getEncryptedData(): string
    {
        return $this->encryptedData;
    }

    public function setEncryptedData(string $encryptedData): self
    {
        $this->encryptedData = $encryptedData;

        return $this;
    }

    public function getDateCreate(): \DateTimeImmutable
    {
        return $this->dateCreate;
    }

    public function setDateCreate(\DateTimeImmutable $dateCreate): self
    {
        $this->dateCreate = $dateCreate;

        return $this;
    }

    public function getDateChange(): ?\DateTime
    {
        return $this->dateChange;
    }

    public function setDateChange(?\DateTime $dateChange): self
    {
        $this->dateChange = $dateChange;

        return $this;
    }
}
