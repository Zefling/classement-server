<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Delete;
use App\Controller\ApiAdminDeleteUserController;
use App\Controller\ApiAdminUsersController;
use App\Controller\ApiAdminUserUpdateController;
use App\Controller\ApiDeleteUserController;
use App\Controller\ApiGetCurrentUserController;
use App\Controller\ApiGetUserController;
use App\Controller\ApiTestUserController;
use App\Repository\UserRepository;
use App\Utils\EntityCommon;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    operations: [
        // CollectionOperations
        new Get(
            uriTemplate: '/user/current',
            name: 'app_api_user_current',
            controller: ApiGetCurrentUserController::class,
        ),
        new Post(
            uriTemplate: '/test',
            name: 'app_api_user_test',
            controller: ApiTestUserController::class,
            normalizationContext: ['groups' => ['isValidated']],
        ),
        new GetCollection(
            uriTemplate: '/admin/users',
            name: 'app_api_admin_users',
            controller: ApiAdminUsersController::class,
        ),
        new Delete(
            uriTemplate: '/user',
            name: 'app_api_user_delete',
            controller: ApiDeleteUserController::class,
        ),
        // ItemOperations
        new Get(
            uriTemplate: '/user/{id}',
            name: 'app_api_user_get',
            controller: ApiGetUserController::class,
            requirements: ['id' => '\s+'],
        ),
        new Post(
            uriTemplate: '/admin/user/{id}',
            name: 'app_api_admin_user_update',
            controller: ApiAdminUserUpdateController::class,
            requirements: ['id' => '\s+'],
        ),
        new Delete(
            uriTemplate: '/admin/user/{id}',
            name: 'app_api_admin_user_delete',
            controller: ApiAdminDeleteUserController::class,
            requirements: ['id' => '\s+'],
        ),
    ],
)]
#[ORM\Entity(repositoryClass: UserRepository::class)]
class User extends EntityCommon implements UserInterface, PasswordAuthenticatedUserInterface
{

    public function __construct(?int $id = null)
    {
        if ($id !== null) {
            $this->id = $id;
        }
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected $id;

    #[ORM\Column(type: 'string', length: 180, nullable: true)]
    protected $email;

    #[ORM\Column(type: 'json')]
    protected $roles = [];

    #[ORM\Column(type: 'string', nullable: true)]
    protected $password;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    #[\ApiPlatform\Metadata\ApiProperty(identifier: true)]
    protected $username;

    #[ORM\Column(type: 'datetime_immutable', options: ['default' => 'CURRENT_TIMESTAMP'], nullable: true)]
    #[Groups(['isValidated'])]
    protected $dateCreate;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    protected $isValidated;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    protected $avatar;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    protected $deleted;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isBanned(): bool
    {
        return in_array('ROLE_BANNED', $this->roles);
    }

    public function isAdmin(): bool
    {
        return in_array('ROLE_USER', $this->roles) && !$this->isBanned();
    }

    public function isModerator(): bool
    {
        return (in_array('ROLE_MODERATOR', $this->roles) || $this->isAdmin()) && !$this->isBanned();
    }

    public function isUser(): bool
    {
        return (in_array('ROLE_USER', $this->roles) || $this->isModerator()) && !$this->isBanned();
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
        if ($this->isValidated && !$this->deleted) {
            $roles[] = 'ROLE_USER';
        }

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
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
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

    public function getAvatar(): ?bool
    {
        return $this->avatar;
    }

    public function setAvatar(bool $avatar): self
    {
        $this->avatar = $avatar;

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
