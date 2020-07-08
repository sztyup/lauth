<?php

declare(strict_types=1);

namespace Sztyup\LAuth\Exceptions;

class InvalidStateException extends LauthException
{
    public function __construct(?string $expected, ?string $received)
    {
        parent::__construct(sprintf('Expected state: %s, received: %s', $expected, $received));
    }
}
