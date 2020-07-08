<?php
declare(strict_types=1);

namespace Sztyup\LAuth;

use Illuminate\Http\RedirectResponse;
use Sztyup\LAuth\Entities\Account;

interface ProviderInterface
{
    public function getName(): string;

    public function redirect(): RedirectResponse;

    public function callback(bool $forceRefresh = false): ?Account;

    public function refresh(Account $account, bool $forceRefresh = false): Account;

    public function getProviderUser(Account $account, bool $forceRefresh = false): ProviderUser;

    public static function getEntitiesPath(): string;

    public static function getAccountEntity(): string;
}
