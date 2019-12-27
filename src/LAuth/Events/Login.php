<?php
declare(strict_types=1);

namespace Sztyup\LAuth\Events;

use Sztyup\LAuth\ProviderUser;
use Sztyup\LAuth\UserInterface;

class Login
{
    /** @var UserInterface */
    public $user;

    /** @var ProviderUser */
    public $providerUser;

    public function __construct(UserInterface $user, ProviderUser $providerUser)
    {
        $this->user = $user;
        $this->providerUser = $providerUser;
    }
}
