<?php

declare(strict_types=1);

namespace Exan\Retrier\Exceptions;

use Exception;
use Throwable;

class TooManyRetriesException extends Exception
{
    /**
     * @param Throwable[] $exceptions
     */
    public function __construct(string $message = '', int $code = 0, public array $exceptions = [])
    {
        parent::__construct($message, $code, $exceptions[count($exceptions) - 1]);
    }
}
