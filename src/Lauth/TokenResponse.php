<?php
declare(strict_types=1);

namespace Sztyup\Lauth;

class TokenResponse
{
    public $accessToken;

    public $refreshToken;

    public $accessTokenExpiration;

    public $refreshTokenExpiration;
}
