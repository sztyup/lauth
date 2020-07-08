<?php

declare(strict_types=1);

namespace Sztyup\LAuth\Events;

use Sztyup\LAuth\UserInterface;

class Login
{
    /** @var UserInterface */
    public $user;

    public function __construct(UserInterface $user)
    {
        $this->user = $user;
    }
}
