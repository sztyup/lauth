<?php
declare(strict_types=1);

namespace Sztyup\Lauth\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(indexes={
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
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="User", inversedBy="accounts")
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

    public function getRefreshToken(): string
    {
        return $this->refreshToken;
    }

    public function setRefreshToken(string $refreshToken): Account
    {
        $this->refreshToken = $refreshToken;

        return $this;
    }
}