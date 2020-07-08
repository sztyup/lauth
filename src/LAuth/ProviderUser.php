<?php

declare(strict_types=1);

namespace Sztyup\LAuth;

class ProviderUser
{
    /** @var string */
    public $providerId;

    /** @var string */
    public $name;

    /** @var string */
    public $email;

    /** @var array */
    public $data;
}
