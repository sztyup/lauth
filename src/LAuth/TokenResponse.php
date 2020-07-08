<?php

declare(strict_types=1);

namespace Sztyup\LAuth;

class TokenResponse
{
    public $accessToken;

    public $refreshToken;

    public $accessTokenExpiration;

    public $refreshTokenExpiration;
}
