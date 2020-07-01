<?php
declare(strict_types=1);

namespace Sztyup\LAuth\Entities;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="lauth_accounts", indexes={
 *      @ORM\Index(columns={"user_id"}),
 *      @ORM\Index(columns={"provider_user_id"}),
 *      @ORM\Index(columns={"provider"}),
 * })
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="provider", type="string")
 */
abstract class Account
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Sztyup\LAuth\Entities\User", inversedBy="accounts")
     */
    protected $user;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    protected $providerUserId;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $accessToken;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $refreshToken;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    protected $email;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime")
     */
    protected $lastSignedIn;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime")
     */
    protected $createdAt;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime")
     */
    protected $updatedAt;

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): Account
    {
        $this->user = $user;

        return $this;
    }

    public function getProviderUserId(): string
    {
        return $this->providerUserId;
    }

    public function setProviderUserId(string $providerUserId): Account
    {
        $this->providerUserId = $providerUserId;

        return $this;
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function setAccessToken(string $accessToken): Account
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function setRefreshToken(string $refreshToken): Account
    {
        $this->refreshToken = $refreshToken;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Account
    {
        $this->name = $name;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): Account
    {
        $this->email = $email;

        return $this;
    }

    public function setLastSignedIn(DateTime $lastSignedIn): Account
    {
        $this->lastSignedIn = $lastSignedIn;

        return $this;
    }

    public function getLastSignedIn(): DateTime
    {
        return $this->lastSignedIn;
    }

    public function setCreatedAt(DateTime $createdAt): Account
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setUpdatedAt(DateTime $updatedAt): Account
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }
}
