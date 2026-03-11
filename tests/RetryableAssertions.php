<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests;

use PHPUnit\Framework\Assert;

trait RetryableAssertions
{
    /**
     * Retry a callback until it returns true or timeout is reached
     *
     * @param  callable  $callback  The condition to check
     * @param  int  $maxAttempts  Maximum number of retry attempts
     * @param  int  $delayMs  Delay between attempts in milliseconds
     * @param  string|null  $message  Failure message
     */
    protected static function assertEventually(
        callable $callback,
        int $maxAttempts = 10,
        int $delayMs = 100,
        ?string $message = null
    ): void {
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            $result = $callback();
            if ($result) {
                Assert::assertTrue($result);

                return;
            }
            usleep($delayMs * 1000);
            $attempt++;
        }

        $message = $message ?? sprintf(
            'Condition not met after %d attempts (%dms total)',
            $maxAttempts,
            $maxAttempts * $delayMs
        );

        Assert::fail($message);
    }
}
