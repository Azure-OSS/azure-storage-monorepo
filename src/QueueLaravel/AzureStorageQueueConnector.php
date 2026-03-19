<?php

declare(strict_types=1);

namespace AzureOss\Storage\QueueLaravel;

use AzureOss\Storage\Common\Auth\StorageSharedKeyCredential;
use AzureOss\Storage\Queue\QueueServiceClient;
use GuzzleHttp\Psr7\Uri;
use Illuminate\Queue\Connectors\ConnectorInterface;

/**
 * @internal
 */
final class AzureStorageQueueConnector implements ConnectorInterface
{
    /**
     * @param  array<mixed>  $config
     */
    public function connect(array $config): AzureStorageQueue
    {
        /** @var array<string, mixed> $typedConfig */
        $typedConfig = $config;
        AzureStorageQueueConfig::validate($typedConfig);
        $config = $typedConfig;

        $connectionString = $config['connection_string'] ?? null;
        if ($connectionString !== null && $connectionString !== '') {
            $client = QueueServiceClient::fromConnectionString($connectionString);
        } else {
            $accountName = $config['account_name'] ?? null;
            $accountKey = $config['account_key'] ?? null;
            if ($accountName === null || $accountKey === null) {
                throw new \InvalidArgumentException('Missing [account_name] or [account_key] in the queue connection configuration.');
            }

            $queueEndpoint = $config['queue_endpoint'] ?? null;
            if ($queueEndpoint !== null && $queueEndpoint !== '') {
                $endpoint = new Uri($queueEndpoint);
            } else {
                $scheme = $config['protocol'] ?? 'https';
                $endpointSuffix = $config['endpoint_suffix'] ?? 'core.windows.net';
                $endpoint = new Uri("{$scheme}://{$accountName}.queue.{$endpointSuffix}");
            }

            $client = new QueueServiceClient($endpoint, new StorageSharedKeyCredential($accountName, $accountKey));
        }

        $queueName = $config['queue'];
        $visibilityTimeout = $config['retry_after'] ?? 60;
        $timeToLive = $config['time_to_live'] ?? null;
        $dispatchAfterCommit = $config['after_commit'] ?? false;

        $queue = new AzureStorageQueue($client, $queueName, $visibilityTimeout, $timeToLive, $dispatchAfterCommit);

        if (($config['create_queue'] ?? false) === true) {
            $queue->getQueueClient($queue->getQueue($queueName))->createIfNotExists();
        }

        return $queue;
    }
}
