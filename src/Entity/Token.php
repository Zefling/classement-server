<?php

namespace App\Entity;

use App\Repository\TokenRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TokenRepository::class)]
class Token
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', unique: true)]
    private $id;

    #[ORM\Column(type: 'integer')]
    private $userId;

    #[ORM\Column(type: 'datetime')]
    private $date;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private $token;

    public function __construct(User $user)
    {
        $this->userId = $user->getId();
        $this->reset();
    }

    public function reset()
    {
        $this->date = new \DateTime();
        $this->token = base_convert(sha1(uniqid(mt_rand(), true)), 16, 36);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): self
    {
        $this->token = $token;

        return $this;
    }
}
