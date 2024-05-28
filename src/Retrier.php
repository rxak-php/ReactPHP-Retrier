<?php

declare(strict_types=1);

namespace Exan\Retrier;

use Exan\Retrier\Exceptions\TooManyRetriesException;
use React\Promise\Promise;
use React\Promise\PromiseInterface;

class Retrier
{
    public function retry(
        int $attempts,
        callable $action
    ): PromiseInterface {
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
                    ->otherwise(static fn () => $shouldReject()
                        ? $reject(new TooManyRetriesException())
                        : $executeAction($action));
            };

            $executeAction($action);
        });
    }
}
