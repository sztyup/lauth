<?php

declare(strict_types=1);

namespace Sztyup\LAuth\Events;

use Sztyup\LAuth\Entities\Account;
use Sztyup\LAuth\ProviderUser;

class ProviderLogin
{
    public $user;

    public $account;

    public function __construct(ProviderUser $user, Account $account)
    {
        $this->user    = $user;
        $this->account = $account;
    }
}
