<?php
declare(strict_types=1);

namespace Sztyup\LAuth;

class ProviderUser
{
    /** @var string */
    public $providerId;

    /** @var string */
    public $name;

    /** @var string */
    public $email;

    /** @var string */
    public $accessToken;

    /** @var string|null */
    public $refreshToken;

    public function setAccessToken(string $accessToken): ProviderUser
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function setRefreshToken(?string $refreshToken): ProviderUser
    {
        $this->refreshToken = $refreshToken;

        return $this;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }
}
