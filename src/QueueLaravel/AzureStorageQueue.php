<?php

declare(strict_types=1);

namespace AzureOss\Storage\QueueLaravel;

use AzureOss\Storage\Queue\QueueClient;
use AzureOss\Storage\Queue\QueueServiceClient;
use Illuminate\Contracts\Queue\ClearableQueue;
use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Queue\Queue;

/**
 * @internal
 */
final class AzureStorageQueue extends Queue implements ClearableQueue, QueueContract
{
    /** @var array<string, QueueClient> */
    private array $queueClients = [];

    public function __construct(
        private readonly QueueServiceClient $serviceClient,
        private readonly string $default,
        private readonly int $visibilityTimeout = 60,
        private readonly ?int $timeToLive = null,
        bool $dispatchAfterCommit = false,
    ) {
        $this->dispatchAfterCommit = $dispatchAfterCommit;
    }

    /**
     * @internal
     */
    public function getQueueClient(string $queue): QueueClient
    {
        return $this->queueClients[$queue] ??= $this->serviceClient->getQueueClient($queue);
    }

    public function size($queue = null): int
    {
        return $this->getQueueClient($this->getQueue($queue))->getProperties()->approximateMessagesCount;
    }

    public function push($job, $data = '', $queue = null)
    {
        $queueName = $this->getQueue($queue);

        return $this->enqueueUsing(
            $job,
            $this->createPayload($job, $queueName, $data),
            $queueName,
            null,
            fn (string $payload, string $queue) => $this->pushRaw($payload, $queue),
        );
    }

    /**
     * @param  array<mixed>  $options
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        $queueName = $this->getQueue($queue);

        if (array_key_exists('retry_after', $options) && $options['retry_after'] !== null && ! is_int($options['retry_after'])) {
            throw new \InvalidArgumentException('The [retry_after] option must be an integer.');
        }
        if (array_key_exists('time_to_live', $options) && $options['time_to_live'] !== null && ! is_int($options['time_to_live'])) {
            throw new \InvalidArgumentException('The [time_to_live] option must be an integer.');
        }

        $visibilityTimeout = is_int($options['retry_after'] ?? null) ? $options['retry_after'] : null;
        $timeToLive = is_int($options['time_to_live'] ?? null) ? $options['time_to_live'] : $this->timeToLive;

        $receipt = $this->getQueueClient($queueName)->sendMessage($payload, $visibilityTimeout, $timeToLive);

        return $receipt->messageId;
    }

    public function later($delay, $job, $data = '', $queue = null)
    {
        $queueName = $this->getQueue($queue);

        return $this->enqueueUsing(
            $job,
            $this->createPayload($job, $queueName, $data),
            $queueName,
            $delay,
            function (string $payload, string $queue, \DateTimeInterface|\DateInterval|int $delay) {
                $receipt = $this->getQueueClient($queue)->sendMessage(
                    $payload,
                    visibilityTimeout: $this->secondsUntil($delay),
                    timeToLive: $this->timeToLive,
                );

                return $receipt->messageId;
            },
        );
    }

    /**
     * @param  array<mixed>  $jobs
     */
    public function bulk($jobs, $data = '', $queue = null): void
    {
        foreach ($jobs as $job) {
            if (! is_string($job) && ! is_object($job)) {
                throw new \InvalidArgumentException('Jobs must be strings or objects.');
            }

            if (! is_object($job) || ! property_exists($job, 'delay')) {
                $this->push($job, $data, $queue);

                continue;
            }

            $delay = $job->delay;

            if (! is_int($delay) && ! $delay instanceof \DateTimeInterface && ! $delay instanceof \DateInterval) {
                throw new \InvalidArgumentException(
                    'Job delay must be an int, DateTimeInterface, or DateInterval.'
                );
            }

            $this->later($delay, $job, $data, $queue);
        }
    }

    public function pop($queue = null): ?AzureStorageQueueJob
    {
        $queue = $this->getQueue($queue);

        $message = $this->getQueueClient($queue)->receiveMessage($this->visibilityTimeout);
        if ($message === null) {
            return null;
        }

        return new AzureStorageQueueJob($this->container, $this->getQueueClient($queue), $message, $this->connectionName, $queue);
    }

    public function clear($queue): int
    {
        $count = $this->size($queue);
        $this->getQueueClient($this->getQueue($queue))->clearMessages();

        return $count;
    }

    public function getQueue(?string $queue): string
    {
        return ($queue !== null && $queue !== '') ? $queue : $this->default;
    }
}
