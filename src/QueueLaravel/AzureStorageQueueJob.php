<?php

declare(strict_types=1);

namespace AzureOss\Storage\QueueLaravel;

use AzureOss\Storage\Queue\Models\QueueMessage;
use AzureOss\Storage\Queue\QueueClient;
use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Queue\Jobs\Job;

/**
 * @internal
 */
final class AzureStorageQueueJob extends Job implements JobContract
{
    private string $popReceipt;

    public function __construct(
        Container $container,
        private readonly QueueClient $client,
        private readonly QueueMessage $message,
        string $connectionName,
        private readonly string $queueName,
    ) {
        $this->container = $container;
        $this->connectionName = $connectionName;
        $this->popReceipt = $message->popReceipt;
    }

    public function delete(): void
    {
        parent::delete();

        $this->client->deleteMessage($this->message->messageId, $this->popReceipt);
    }

    public function release($delay = 0): void
    {
        parent::release($delay);

        /** @var int $delay */
        $receipt = $this->client->updateMessage(
            $this->message->messageId,
            $this->popReceipt,
            $delay,
        );

        $this->popReceipt = $receipt->popReceipt;
    }

    public function attempts(): int
    {
        return $this->message->dequeueCount;
    }

    public function getQueue(): string
    {
        return $this->queueName;
    }

    public function getJobId(): string
    {
        return $this->message->messageId;
    }

    public function getRawBody(): string
    {
        return $this->message->messageText;
    }
}
