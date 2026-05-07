<?php

namespace DreamTechnologies\TelebirrLaravelPlus\Exceptions;

use RuntimeException;

class TelebirrException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?string $telebirrCode = null,
        public readonly array $context = [],
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
