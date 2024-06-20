<?php

declare(strict_types=1);

namespace Exan\Retrier;

use Exan\Retrier\Exceptions\TooManyRetriesException;
use React\Promise\Promise;
use React\Promise\PromiseInterface;

class Retrier
{
    public static function retry(
        int      $attempts,
        callable $action
    ): PromiseInterface
    {
        return new Promise(static function (callable $resolve, callable $reject) use ($attempts, $action) {
            $retries = 0;
            $shouldReject = static function () use (&$retries, $attempts) {
                return ++$retries >= $attempts;
            };

            $executeAction = static function (
                callable $action
            ) use (
                &$retries,
                $resolve,
                $shouldReject,
                $reject,
                &$executeAction
            ) {
                $action($retries)
                    ->then($resolve)
                    ->catch(static fn(\Throwable $e) => $shouldReject()
                        ? $reject(new TooManyRetriesException(sprintf('Too many retries of #%d for exception: %s', $retries, $e->getMessage()), $e->getCode(), $e)) //TODO: maybe pass an array of occurred exceptions and not just the last one
                        : $executeAction($action));
            };

            $executeAction($action);
        });
    }
}
