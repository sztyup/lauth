<?php
declare(strict_types=1);

namespace Sztyup\LAuth;

use Doctrine\Common\Collections\ArrayCollection;
use Illuminate\Contracts\Auth\Authenticatable;
use Sztyup\LAuth\Entities\Account;

interface UserInterface extends Authenticatable
{
    /**
     * @return Account[]|ArrayCollection
     */
    public function getAccounts();

    public function addAccount(Account $account): UserInterface;
}
