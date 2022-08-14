<?php

namespace App\Entity;

use App\Repository\TokenRepository;
use DateInterval;
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

    #[ORM\Column(type: 'datetime')]
    private $validity;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private $token;

    #[ORM\Column(type: 'string', length: 10)]
    private $role;

    public function __construct(User $user, DateInterval $duration, string $role)
    {
        $this->userId = $user->getId();
        $this->role = $role;
        $this->resetDate($duration);
        $this->renewToken();
    }

    public function resetDate(DateInterval $duration)
    {
        $this->date = new \DateTime();
        $this->validity = new \DateTime();
        $this->validity->add($duration);
    }

    public function renewToken() {
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

    public function getValidity(): ?\DateTimeInterface
    {
        return $this->validity;
    }

    public function setValidity(\DateTimeInterface $validity): self
    {
        $this->validity = $validity;

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

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;

        return $this;
    }
}
