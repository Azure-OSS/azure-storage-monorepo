<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Blob;

use AzureOss\Storage\Blob\BlobContainerClient;
use AzureOss\Storage\Blob\BlobServiceClient;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\TestCase;

/** @mixin TestCase */
trait CreatesTempContainers
{
    /** @var list<BlobContainerClient> */
    private array $tempContainers = [];

    protected function service(
        bool $versions = false,
        bool $public = false,
        bool $softDeletes = false,
    ): BlobServiceClient {
        return BlobServiceClient::fromConnectionString($this->resolveConnectionString(
            $versions,
            $public,
            $softDeletes,
        ));
    }

    protected function tempContainer(
        string $prefix = 'test-',
        bool $versions = false,
        bool $public = false,
        bool $softDeletes = false,
    ): BlobContainerClient {
        $serviceClient = $this->service($versions, $public, $softDeletes);
        $containerClient = $serviceClient->getContainerClient($prefix.bin2hex(random_bytes(12)));
        $containerClient->create();

        $this->tempContainers[] = $containerClient;

        return $containerClient;
    }

    #[After]
    protected function cleanupTempContainers(): void
    {
        foreach ($this->tempContainers as $containerClient) {
            try {
                $containerClient->delete();
            } catch (\Throwable) {
            }
        }

        $this->tempContainers = [];
    }

    private static function envMap(): array
    {
        return [
            'default' => 'AZURE_STORAGE_CONNECTION_STRING',
            'public' => 'AZURE_STORAGE_CONNECTION_STRING_PUBLIC',
            'soft-deletes' => 'AZURE_STORAGE_CONNECTION_STRING_SOFT_DELETES',
            'versions' => 'AZURE_STORAGE_CONNECTION_STRING_VERSIONS',
            'public+soft-deletes' => 'AZURE_STORAGE_CONNECTION_STRING_PUBLIC_SOFT_DELETES',
            'public+versions' => 'AZURE_STORAGE_CONNECTION_STRING_PUBLIC_VERSIONS',
            'soft-deletes+versions' => 'AZURE_STORAGE_CONNECTION_STRING_SOFT_DELETES_VERSIONS',
            'public+soft-deletes+versions' => 'AZURE_STORAGE_CONNECTION_STRING_ALL',
        ];
    }

    private function resolveConnectionString(bool $versions, bool $public, bool $softDeletes): string
    {
        $parts = [];

        if ($public) {
            $parts[] = 'public';
        }
        if ($softDeletes) {
            $parts[] = 'soft-deletes';
        }
        if ($versions) {
            $parts[] = 'versions';
        }

        $key = $parts === [] ? 'default' : implode('+', $parts);

        $map = self::envMap();
        $envVar = $map[$key]
            ?? throw new \RuntimeException("No storage account configured for scenario: {$key}");

        $value = getenv($envVar);

        if ($value === false || $value === '') {
            self::markTestSkipped("Missing environment variable: {$envVar}");
        }

        return $value;
    }
}
