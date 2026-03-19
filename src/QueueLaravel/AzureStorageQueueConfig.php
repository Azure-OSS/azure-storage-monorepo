<?php

declare(strict_types=1);

namespace AzureOss\Storage\QueueLaravel;

/**
 * @internal
 */
final class AzureStorageQueueConfig
{
    private function __construct() {}

    /**
     * @param  array<string, mixed>  $config
     *
     * @phpstan-assert array{
     *     connection_string?: string,
     *     protocol?: string,
     *     endpoint_suffix?: string,
     *     queue_endpoint?: string,
     *     account_name?: string,
     *     account_key?: string,
     *     queue: string,
     *     retry_after?: int,
     *     time_to_live?: int,
     *     timeout?: int,
     *     after_commit?: bool,
     *     create_queue?: bool
     * } $config
     */
    public static function validate(array &$config): void
    {
        self::assertString($config, 'connection_string');
        self::assertString($config, 'protocol');
        self::assertString($config, 'endpoint_suffix');
        self::assertString($config, 'queue_endpoint');
        self::assertString($config, 'account_name');
        self::assertString($config, 'account_key');
        self::assertString($config, 'queue', required: true);
        self::assertInt($config, 'retry_after');
        self::assertInt($config, 'time_to_live');
        self::assertInt($config, 'timeout');
        self::assertBool($config, 'after_commit');
        self::assertBool($config, 'create_queue');

        $hasConnectionString = isset($config['connection_string']) && $config['connection_string'] !== '';
        $hasSharedKey = isset($config['account_name'], $config['account_key'])
            && $config['account_name'] !== ''
            && $config['account_key'] !== '';

        if (! $hasConnectionString && ! $hasSharedKey) {
            throw new \InvalidArgumentException(
                'Either [connection_string] or [account_name] + [account_key] must be provided in the queue connection configuration.'
            );
        }
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private static function assertString(array $config, string $key, bool $required = false): void
    {
        if (! array_key_exists($key, $config) || $config[$key] === null) {
            if ($required) {
                throw new \InvalidArgumentException("The [{$key}] must be a string in the queue connection configuration.");
            }

            return;
        }

        if (! is_string($config[$key])) {
            throw new \InvalidArgumentException("The [{$key}] must be a string in the queue connection configuration.");
        }
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private static function assertInt(array $config, string $key, bool $required = false): void
    {
        if (! array_key_exists($key, $config) || $config[$key] === null) {
            if ($required) {
                throw new \InvalidArgumentException("The [{$key}] must be an integer in the queue connection configuration.");
            }

            return;
        }

        if (! is_int($config[$key])) {
            throw new \InvalidArgumentException("The [{$key}] must be an integer in the queue connection configuration.");
        }
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private static function assertBool(array $config, string $key, bool $required = false): void
    {
        if (! array_key_exists($key, $config) || $config[$key] === null) {
            if ($required) {
                throw new \InvalidArgumentException("The [{$key}] must be a boolean in the queue connection configuration.");
            }

            return;
        }

        if (! is_bool($config[$key])) {
            throw new \InvalidArgumentException("The [{$key}] must be a boolean in the queue connection configuration.");
        }
    }
}
