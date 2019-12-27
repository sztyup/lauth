<?php
declare(strict_types=1);

namespace Sztyup\LAuth;

use Illuminate\Http\RedirectResponse;
use Sztyup\LAuth\Entities\Account;

interface ProviderInterface
{
    public function getName(): string;

    public function redirect(): RedirectResponse;

    public function callback(): ProviderUser;

    public function refresh(Account $account);

    public function createAccount(ProviderUser $providerUser): Account;

    public function refreshAccount(Account $account, ProviderUser $providerUser): void;
}
