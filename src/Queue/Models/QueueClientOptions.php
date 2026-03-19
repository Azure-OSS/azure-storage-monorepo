<?php

declare(strict_types=1);

namespace AzureOss\Storage\Queue\Models;

use AzureOss\Storage\Common\Middleware\HttpClientOptions;

final class QueueClientOptions
{
    public function __construct(
        public readonly HttpClientOptions $httpClientOptions = new HttpClientOptions,
    ) {}
}
