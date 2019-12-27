<?php
declare(strict_types=1);

namespace Sztyup\Lauth;

use Doctrine\Common\Collections\ArrayCollection;
use Illuminate\Contracts\Auth\Authenticatable;
use Sztyup\Lauth\Entities\Account;

interface UserInterface extends Authenticatable
{
    /**
     * @return Account[]|ArrayCollection
     */
    public function getAccounts();

    public function addAccount(Account $account): UserInterface;
}
