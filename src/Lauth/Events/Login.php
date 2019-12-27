<?php
declare(strict_types=1);

namespace Sztyup\Lauth\Events;

use Sztyup\Lauth\ProviderUser;
use Sztyup\Lauth\UserInterface;

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
