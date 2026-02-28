<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Blob;

use AzureOss\Storage\Blob\BlobServiceClient;
use AzureOss\Storage\Blob\Helpers\BlobUriParserHelper;
use PHPUnit\Framework\TestCase;

abstract class BlobFeatureTestCase extends TestCase
{
    protected BlobServiceClient $serviceClient;

    protected ?BlobServiceClient $secondaryServiceClient = null;

    protected function setUp(): void
    {
        $connectionString = getenv('AZURE_STORAGE_CONNECTION_STRING');

        if (! is_string($connectionString)) {
            self::fail('Invalid connection string. Please set AZURE_STORAGE_CONNECTION_STRING environment variable.');
        }

        $this->serviceClient = BlobServiceClient::fromConnectionString($connectionString);

        $secondaryConnectionString = getenv('AZURE_STORAGE_CONNECTION_STRING_SECONDARY');
        if (is_string($secondaryConnectionString) && $secondaryConnectionString !== '') {
            $this->secondaryServiceClient = BlobServiceClient::fromConnectionString($secondaryConnectionString);
        }
    }

    /**
     * @phpstan-assert-if-true !null $this->secondaryServiceClient
     */
    protected function hasSecondaryStorageAccount(): bool
    {
        return $this->secondaryServiceClient !== null;
    }

    /**
     * @phpstan-assert !null $this->secondaryServiceClient
     */
    protected function requireSecondaryStorageAccount(): void
    {
        if (! $this->hasSecondaryStorageAccount()) {
            self::markTestSkipped('Secondary storage account required. Please set AZURE_STORAGE_CONNECTION_STRING_SECONDARY environment variable.');
        }
    }

    protected function randomContainerName(): string
    {
        return substr(md5((string) mt_rand()), 0, 7);
    }

    protected function cleanContainer(string $containerName, ?BlobServiceClient $serviceClient = null): void
    {
        $client = $serviceClient ?? $this->serviceClient;
        $containerClient = $client->getContainerClient($containerName);

        $containerClient->createIfNotExists();

        foreach ($containerClient->getBlobs() as $blob) {
            $containerClient->getBlobClient($blob->name)->delete();
        }
    }

    protected function isUsingSimulator(): bool
    {
        return BlobUriParserHelper::isDevelopmentUri($this->serviceClient->uri);
    }

    protected function markTestSkippedWhenUsingSimulator(): void
    {
        if ($this->isUsingSimulator()) {
            self::markTestSkipped('API unsupported in Azurite.');
        }
    }
}
