<?php

declare(strict_types=1);

namespace Tests\Exan\Retrier;

use Exan\Retrier\Exceptions\TooManyRetriesException;
use Exan\Retrier\Retrier;
use Exception;
use PHPUnit\Framework\TestCase;
use React\Promise\Promise;
use React\Promise\PromiseInterface;

use function React\Async\await;

class RetrierTest extends TestCase
{
    private function getResolvedPromise(mixed $result = null): PromiseInterface
    {
        return new Promise(fn ($resolve) => $resolve($result));
    }

    private function getRejectedPromise(Exception $e = new Exception()): PromiseInterface
    {
        return new Promise(fn ($resolve, $reject) => $reject($e));
    }

    public function testItDoesNotRunTheActionMultipleTimesForHappyFlowAsync(): void
    {
        $retrier = new Retrier();
        $times = 0;

        $result = await($retrier->retry(3, function () use (&$times) {
            $times++;
            return $this->getResolvedPromise('Success');
        }));

        $this->assertEquals('Success', $result);
        $this->assertEquals(1, $times);
    }

    public function testItRetriesTheSetNumberOfTimesAsync(): void
    {
        $retrier = new Retrier();
        $times = 0;

        $result = await($retrier->retry(3, function () use (&$times) {
            $times++;

            if ($times < 3) {
                return $this->getRejectedPromise(new Exception('Oh no, it went wrong :('));
            }

            return $this->getResolvedPromise('Woop woop');
        }));

        $this->assertEquals('Woop woop', $result);
        $this->assertEquals(3, $times);
    }

    public function testItThrowsAnErrorAfterSetAttempts(): void
    {
        $retrier = new Retrier();
        $times = 0;


        await($retrier->retry(3, function () use (&$times) {
            $times++;
            $this->assertLessThan(4, $times);

            if ($times === 3) {
                $this->expectException(TooManyRetriesException::class);
            }

            return $this->getRejectedPromise(new Exception('Error'));
        }));
    }

    public function testItIndicatesWhatAttemptItIsOn(): void
    {
        $retrier = new Retrier();
        $times = 0;


        await($retrier->retry(3, function (int $attempt) use (&$times) {
            $this->assertEquals($attempt, $times);
            $times++;

            if ($times === 3) {
                $this->expectException(TooManyRetriesException::class);
            }

            return $this->getRejectedPromise(new Exception('Sad times'));
        }));
    }
}
