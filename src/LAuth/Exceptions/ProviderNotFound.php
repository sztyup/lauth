<?php
declare(strict_types=1);

namespace Sztyup\LAuth\Exceptions;

class ProviderNotFound extends LauthException
{
    public function __construct(string $provider)
    {
        parent::__construct(sprintf('Provider [%s] not found', $provider));
    }
}