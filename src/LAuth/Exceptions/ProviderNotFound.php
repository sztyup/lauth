<?php

declare(strict_types=1);

namespace Sztyup\LAuth\Exceptions;

use Throwable;

class ProviderNotFound extends LauthException
{
    public function __construct(string $provider, Throwable $previous = null)
    {
        parent::__construct(sprintf('Provider [%s] not found', $provider), 0, $previous);
    }
}
