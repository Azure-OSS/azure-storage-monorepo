<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\BlobLaravel;

use AzureOss\Storage\BlobLaravel\AzureStorageBlobAdapter;
use AzureOss\Storage\BlobLaravel\AzureStorageBlobServiceProvider;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AzureStorageBlobServiceProviderTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [AzureStorageBlobServiceProvider::class];
    }

    #[Test]
    public function it_throws_when_no_credentials_provided(): void
    {
        config(['filesystems.disks.azure-invalid' => [
            'driver' => 'azure-storage-blob',
            'container' => 'test-container',
        ]]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Either [connection_string] or [endpoint, tenant_id, client_id, client_secret] must be provided');

        Storage::disk('azure-invalid');
    }

    #[Test]
    public function it_throws_when_both_connection_string_and_token_credentials_provided(): void
    {
        config(['filesystems.disks.azure-both' => [
            'driver' => 'azure-storage-blob',
            'connection_string' => 'DefaultEndpointsProtocol=https;AccountName=test;AccountKey=key;EndpointSuffix=core.windows.net',
            'endpoint' => 'https://test.blob.core.windows.net',
            'tenant_id' => 'tenant',
            'client_id' => 'client',
            'client_secret' => 'secret',
            'container' => 'test-container',
        ]]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot use both [connection_string] and token-based credentials');

        Storage::disk('azure-both');
    }

    #[Test]
    public function it_throws_when_container_missing(): void
    {
        config(['filesystems.disks.azure-no-container' => [
            'driver' => 'azure-storage-blob',
            'connection_string' => 'DefaultEndpointsProtocol=https;AccountName=test;AccountKey=key;EndpointSuffix=core.windows.net',
        ]]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The [container] must be a string');

        Storage::disk('azure-no-container');
    }

    #[Test]
    public function it_throws_when_container_has_wrong_type(): void
    {
        config(['filesystems.disks.azure' => [
            'driver' => 'azure-storage-blob',
            'connection_string' => 'DefaultEndpointsProtocol=https;AccountName=test;AccountKey=key;EndpointSuffix=core.windows.net',
            'container' => ['invalid'],
        ]]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The [container] must be a string');

        Storage::disk('azure');
    }

    #[Test]
    public function it_throws_when_prefix_has_wrong_type(): void
    {
        config(['filesystems.disks.azure' => [
            'driver' => 'azure-storage-blob',
            'connection_string' => 'DefaultEndpointsProtocol=https;AccountName=test;AccountKey=key;EndpointSuffix=core.windows.net',
            'container' => 'test-container',
            'prefix' => ['invalid'],
        ]]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The [prefix] must be a string');

        Storage::disk('azure');
    }

    #[Test]
    public function it_throws_when_root_has_wrong_type(): void
    {
        config(['filesystems.disks.azure' => [
            'driver' => 'azure-storage-blob',
            'connection_string' => 'DefaultEndpointsProtocol=https;AccountName=test;AccountKey=key;EndpointSuffix=core.windows.net',
            'container' => 'test-container',
            'root' => ['invalid'],
        ]]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The [root] must be a string');

        Storage::disk('azure');
    }

    #[Test]
    public function it_throws_when_connection_string_has_wrong_type(): void
    {
        config(['filesystems.disks.azure' => [
            'driver' => 'azure-storage-blob',
            'connection_string' => ['invalid'],
            'container' => 'test-container',
        ]]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The [connection_string] must be a string');

        Storage::disk('azure');
    }

    #[Test]
    public function it_throws_when_tenant_id_has_wrong_type(): void
    {
        config(['filesystems.disks.azure' => [
            'driver' => 'azure-storage-blob',
            'endpoint' => 'https://test.blob.core.windows.net',
            'tenant_id' => ['invalid'],
            'client_id' => 'client',
            'client_secret' => 'secret',
            'container' => 'test-container',
        ]]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The [tenant_id] must be a string');

        Storage::disk('azure');
    }

    #[Test]
    public function it_throws_when_client_id_has_wrong_type(): void
    {
        config(['filesystems.disks.azure' => [
            'driver' => 'azure-storage-blob',
            'endpoint' => 'https://test.blob.core.windows.net',
            'tenant_id' => 'tenant',
            'client_id' => ['invalid'],
            'client_secret' => 'secret',
            'container' => 'test-container',
        ]]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The [client_id] must be a string');

        Storage::disk('azure');
    }

    #[Test]
    public function it_throws_when_client_secret_has_wrong_type(): void
    {
        config(['filesystems.disks.azure' => [
            'driver' => 'azure-storage-blob',
            'endpoint' => 'https://test.blob.core.windows.net',
            'tenant_id' => 'tenant',
            'client_id' => 'client',
            'client_secret' => ['invalid'],
            'container' => 'test-container',
        ]]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The [client_secret] must be a string');

        Storage::disk('azure');
    }

    #[Test]
    public function it_throws_when_endpoint_has_wrong_type(): void
    {
        config(['filesystems.disks.azure' => [
            'driver' => 'azure-storage-blob',
            'endpoint' => ['invalid'],
            'tenant_id' => 'tenant',
            'client_id' => 'client',
            'client_secret' => 'secret',
            'container' => 'test-container',
        ]]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The [endpoint] must be a string');

        Storage::disk('azure');
    }

    #[Test]
    public function it_throws_when_endpoint_suffix_has_wrong_type(): void
    {
        config(['filesystems.disks.azure' => [
            'driver' => 'azure-storage-blob',
            'account_name' => 'testaccount',
            'endpoint_suffix' => ['invalid'],
            'tenant_id' => 'tenant',
            'client_id' => 'client',
            'client_secret' => 'secret',
            'container' => 'test-container',
        ]]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The [endpoint_suffix] must be a string');

        Storage::disk('azure');
    }

    #[Test]
    public function it_throws_when_account_name_has_wrong_type(): void
    {
        config(['filesystems.disks.azure' => [
            'driver' => 'azure-storage-blob',
            'account_name' => ['invalid'],
            'tenant_id' => 'tenant',
            'client_id' => 'client',
            'client_secret' => 'secret',
            'container' => 'test-container',
        ]]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The [account_name] must be a string');

        Storage::disk('azure');
    }

    #[Test]
    public function null_prefix_is_ignored(): void
    {
        config(['filesystems.disks.azure' => [
            'driver' => 'azure-storage-blob',
            'connection_string' => 'DefaultEndpointsProtocol=https;AccountName=test;AccountKey=key;EndpointSuffix=core.windows.net',
            'container' => 'test-container',
            'prefix' => null,
        ]]);

        self::assertInstanceOf(AzureStorageBlobAdapter::class, Storage::disk('azure'));
    }

    #[Test]
    public function null_root_is_ignored(): void
    {
        config(['filesystems.disks.azure' => [
            'driver' => 'azure-storage-blob',
            'connection_string' => 'DefaultEndpointsProtocol=https;AccountName=test;AccountKey=key;EndpointSuffix=core.windows.net',
            'container' => 'test-container',
            'root' => null,
        ]]);

        self::assertInstanceOf(AzureStorageBlobAdapter::class, Storage::disk('azure'));
    }

    #[Test]
    public function null_connection_string_is_ignored(): void
    {
        config(['filesystems.disks.azure' => [
            'driver' => 'azure-storage-blob',
            'connection_string' => null,
            'endpoint' => 'https://test.blob.core.windows.net',
            'tenant_id' => 'tenant',
            'client_id' => 'client',
            'client_secret' => 'secret',
            'container' => 'test-container',
        ]]);

        self::assertInstanceOf(AzureStorageBlobAdapter::class, Storage::disk('azure'));
    }

    #[Test]
    public function null_tenant_id_is_ignored(): void
    {
        config(['filesystems.disks.azure' => [
            'driver' => 'azure-storage-blob',
            'connection_string' => 'DefaultEndpointsProtocol=https;AccountName=test;AccountKey=key;EndpointSuffix=core.windows.net',
            'tenant_id' => null,
            'container' => 'test-container',
        ]]);

        self::assertInstanceOf(AzureStorageBlobAdapter::class, Storage::disk('azure'));
    }

    #[Test]
    public function null_client_id_is_ignored(): void
    {
        config(['filesystems.disks.azure' => [
            'driver' => 'azure-storage-blob',
            'connection_string' => 'DefaultEndpointsProtocol=https;AccountName=test;AccountKey=key;EndpointSuffix=core.windows.net',
            'client_id' => null,
            'container' => 'test-container',
        ]]);

        self::assertInstanceOf(AzureStorageBlobAdapter::class, Storage::disk('azure'));
    }

    #[Test]
    public function null_client_secret_is_ignored(): void
    {
        config(['filesystems.disks.azure' => [
            'driver' => 'azure-storage-blob',
            'connection_string' => 'DefaultEndpointsProtocol=https;AccountName=test;AccountKey=key;EndpointSuffix=core.windows.net',
            'client_secret' => null,
            'container' => 'test-container',
        ]]);

        self::assertInstanceOf(AzureStorageBlobAdapter::class, Storage::disk('azure'));
    }

    #[Test]
    public function null_endpoint_is_ignored(): void
    {
        config(['filesystems.disks.azure' => [
            'driver' => 'azure-storage-blob',
            'connection_string' => 'DefaultEndpointsProtocol=https;AccountName=test;AccountKey=key;EndpointSuffix=core.windows.net',
            'endpoint' => null,
            'container' => 'test-container',
        ]]);

        self::assertInstanceOf(AzureStorageBlobAdapter::class, Storage::disk('azure'));
    }

    #[Test]
    public function null_endpoint_suffix_is_ignored(): void
    {
        config(['filesystems.disks.azure' => [
            'driver' => 'azure-storage-blob',
            'connection_string' => 'DefaultEndpointsProtocol=https;AccountName=test;AccountKey=key;EndpointSuffix=core.windows.net',
            'endpoint_suffix' => null,
            'container' => 'test-container',
        ]]);

        self::assertInstanceOf(AzureStorageBlobAdapter::class, Storage::disk('azure'));
    }

    #[Test]
    public function null_account_name_is_ignored(): void
    {
        config(['filesystems.disks.azure' => [
            'driver' => 'azure-storage-blob',
            'connection_string' => 'DefaultEndpointsProtocol=https;AccountName=test;AccountKey=key;EndpointSuffix=core.windows.net',
            'account_name' => null,
            'container' => 'test-container',
        ]]);

        self::assertInstanceOf(AzureStorageBlobAdapter::class, Storage::disk('azure'));
    }
}
