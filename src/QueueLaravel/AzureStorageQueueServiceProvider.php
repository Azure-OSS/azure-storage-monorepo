<?php

declare(strict_types=1);

namespace AzureOss\Storage\QueueLaravel;

use Illuminate\Queue\QueueManager;
use Illuminate\Support\ServiceProvider;

/**
 * @internal
 */
final class AzureStorageQueueServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        /** @var QueueManager $manager */
        $manager = $this->app->make('queue');

        $manager->addConnector('azure-storage-queue', fn () => new AzureStorageQueueConnector);
    }
}
