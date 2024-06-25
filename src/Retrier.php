<?php

declare(strict_types=1);

namespace Exan\Retrier;

use Exan\Retrier\Exceptions\TooManyRetriesException;
use React\Promise\Promise;
use React\Promise\PromiseInterface;
use Throwable;

class Retrier
{
    public static function attempt(int $attempts, callable $action): PromiseInterface
    {
        return new Promise(static function (callable $resolve, callable $reject) use ($attempts, $action) {
            $exceptions = [];
            $retries = 0;
            $shouldReject = static function (Throwable $e) use (&$retries, &$exceptions, $attempts) {
                $exceptions[] = $e;
                return ++$retries >= $attempts;
            };

            $executeAction = static function (
                callable $action
            ) use (
                &$retries,
                $resolve,
                $shouldReject,
                $reject,
                &$executeAction,
                &$exceptions
            ) {
                $action($retries)
                    ->then($resolve)
                    ->otherwise(static fn(\Throwable $e) => $shouldReject($e)
                        ? $reject(new TooManyRetriesException(
                            sprintf('Max attempts of %d reached', $retries),
                            exceptions: $exceptions,
                        ))
                        : $executeAction($action));
            };

            $executeAction($action);
        });
    }

    public function retry(int $attempts, callable $action): PromiseInterface
    {
        return self::attempt($attempts, $action);
    }
}
