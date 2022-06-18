<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Controller\ApiGetCurrentUserController;
use App\Controller\ApiGetUserController;
use App\Repository\UserRepository;
use App\Utils\EntityCommon;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    collectionOperations: [
        'app_api_user_current' => [
            'method' => 'GET',
            'path' => '/user/current',
            'name' => 'app_api_user_current',
            'controller' => ApiGetCurrentUserController::class,
        ]
    ],
    itemOperations: [
        'app_api_user_get' => [
            'method' => 'GET',
            'path' => '/user/{id}',
            'requirements' => ['id' => '\s+'],
            'name' => 'app_api_user_get',
            'controller' => ApiGetUserController::class,
        ],
    ],
    paginationEnabled: true,
)]
#[ORM\Entity(repositoryClass: UserRepository::class)]
class User extends EntityCommon implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['user:list', 'user:item'])]
    protected $id;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    protected $email;

    #[ORM\Column(type: 'json')]
    protected $roles = [];

    #[ORM\Column(type: 'string')]
    protected $password;

    #[ORM\Column(type: 'string', length: 50, unique: true)]
    #[Groups(['user:list', 'user:item'])]
    #[\ApiPlatform\Core\Annotation\ApiProperty(identifier: true)]
    protected $username;

    #[ORM\Column(type: 'datetime_immutable', options: ['default' => 'CURRENT_TIMESTAMP'])]
    protected $dateCreate;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    protected $isValidated;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    protected $deleted;

    public function getId(): ?int
    {
        return $this->id;
    }


    public function isAdmin(): bool
    {
        return in_array('ROLE_USER', $this->roles);
    }

    public function isModerator(): bool
    {
        return in_array('ROLE_MODERATOR', $this->roles)
            || $this->isAdmin();
    }

    public function isUser(): bool
    {
        return in_array('ROLE_USER', $this->roles)
            || $this->isModerator();
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        $this->plainPassword = null;
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

    public function getDateCreate(): ?\DateTimeInterface
    {
        return $this->dateCreate;
    }

    public function setDateCreate(\DateTimeInterface $dateCreate): self
    {
        $this->dateCreate = $dateCreate;

        return $this;
    }

    public function getIsValidated(): ?bool
    {
        return $this->isValidated;
    }

    public function setIsValidated(bool $isValidated): self
    {
        $this->isValidated = $isValidated;

        return $this;
    }

    public function getDeleted(): ?bool
    {
        return $this->deleted;
    }

    public function setDeleted(bool $deleted): self
    {
        $this->deleted = $deleted;

        return $this;
    }
}
