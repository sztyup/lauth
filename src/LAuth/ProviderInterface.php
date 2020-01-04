<?php
declare(strict_types=1);

namespace Sztyup\LAuth;

use Illuminate\Http\RedirectResponse;
use Sztyup\LAuth\Entities\Account;

interface ProviderInterface
{
    public function getName(): string;

    public function redirect(): RedirectResponse;

    public function callback(): Account;

    public function refresh(Account $account): void;

    public static function getEntitiesPath(): string;

    public static function getAccountEntity(): string;
}
