<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\BlobLaravel;

use AzureOss\Storage\Blob\BlobContainerClient;
use AzureOss\Storage\Blob\BlobServiceClient;
use AzureOss\Storage\BlobLaravel\AzureStorageBlobAdapter;
use AzureOss\Storage\BlobLaravel\AzureStorageBlobServiceProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AzureStorageBlobAdapterTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [AzureStorageBlobServiceProvider::class];
    }

    private static function createContainerClientFromConnectionString(): BlobContainerClient
    {
        $connectionString = getenv('AZURE_STORAGE_CONNECTION_STRING');
        $container = getenv('AZURE_STORAGE_CONTAINER');

        if (! is_string($connectionString) || $connectionString === '') {
            self::markTestSkipped('AZURE_STORAGE_CONNECTION_STRING is not provided.');
        }

        if (! is_string($container)) {
            self::markTestSkipped('AZURE_STORAGE_CONTAINER is not provided.');
        }

        return BlobServiceClient::fromConnectionString($connectionString)->getContainerClient($container);
    }

    private static function createContainerClientFromToken(): BlobContainerClient
    {
        $endpoint = getenv('AZURE_STORAGE_BLOB_ENDPOINT');
        $accountName = getenv('AZURE_STORAGE_BLOB_ACCOUNT_NAME');
        $tenantId = getenv('AZURE_STORAGE_BLOB_TENANT_ID');
        $clientId = getenv('AZURE_STORAGE_BLOB_CLIENT_ID');
        $clientSecret = getenv('AZURE_STORAGE_BLOB_CLIENT_SECRET');
        $container = getenv('AZURE_STORAGE_CONTAINER');

        $hasEndpoint = is_string($endpoint) && $endpoint !== '';
        $hasAccountName = is_string($accountName) && $accountName !== '';

        if (! $hasEndpoint && ! $hasAccountName) {
            self::markTestSkipped('AZURE_STORAGE_BLOB_ENDPOINT or AZURE_STORAGE_BLOB_ACCOUNT_NAME is not provided.');
        }

        if (! is_string($tenantId) || ! is_string($clientId) || ! is_string($clientSecret) || ! is_string($container)) {
            self::markTestSkipped('AZURE_STORAGE_BLOB_TENANT_ID, AZURE_STORAGE_BLOB_CLIENT_ID, AZURE_STORAGE_BLOB_CLIENT_SECRET, AZURE_STORAGE_CONTAINER must be provided.');
        }

        $uri = $hasEndpoint
            ? new \GuzzleHttp\Psr7\Uri(rtrim($endpoint, '/').'/')
            : new \GuzzleHttp\Psr7\Uri(sprintf('https://%s.blob.core.windows.net/', $accountName));

        $credential = new \AzureOss\Storage\Common\Auth\ClientSecretCredential($tenantId, $clientId, $clientSecret);
        $serviceClient = new BlobServiceClient($uri, $credential);

        return $serviceClient->getContainerClient($container);
    }

    #[Test]
    public function it_resolves_from_manager(): void
    {
        $connectionString = getenv('AZURE_STORAGE_CONNECTION_STRING');
        $container = getenv('AZURE_STORAGE_CONTAINER');

        if (! is_string($connectionString) || $connectionString === '' || ! is_string($container)) {
            self::markTestSkipped('AZURE_STORAGE_CONNECTION_STRING and AZURE_STORAGE_CONTAINER are required.');
        }

        config(['filesystems.disks.azure' => [
            'driver' => 'azure-storage-blob',
            'connection_string' => $connectionString,
            'container' => $container,
        ]]);

        self::assertInstanceOf(AzureStorageBlobAdapter::class, Storage::disk('azure'));
    }

    #[Test]
    public function url_uses_sas_by_default_when_using_connection_string(): void
    {
        $connectionString = getenv('AZURE_STORAGE_CONNECTION_STRING');
        $container = getenv('AZURE_STORAGE_CONTAINER');

        if (! is_string($connectionString) || $connectionString === '' || ! is_string($container)) {
            self::markTestSkipped('AZURE_STORAGE_CONNECTION_STRING and AZURE_STORAGE_CONTAINER are required.');
        }

        config(['filesystems.disks.azure' => [
            'driver' => 'azure-storage-blob',
            'connection_string' => $connectionString,
            'container' => $container,
        ]]);

        /** @phpstan-ignore-next-line */
        $url = Storage::disk('azure')->url('file.txt');
        self::assertIsString($url);
        self::assertStringContainsString('sig=', $url);
    }

    #[Test]
    public function url_uses_direct_public_url_when_is_public_container_is_enabled(): void
    {
        $connectionString = getenv('AZURE_STORAGE_CONNECTION_STRING');
        $container = getenv('AZURE_STORAGE_CONTAINER');

        if (! is_string($connectionString) || $connectionString === '' || ! is_string($container)) {
            self::markTestSkipped('AZURE_STORAGE_CONNECTION_STRING and AZURE_STORAGE_CONTAINER are required.');
        }

        config(['filesystems.disks.azure' => [
            'driver' => 'azure-storage-blob',
            'connection_string' => $connectionString,
            'container' => $container,
            'is_public_container' => true,
        ]]);

        /** @phpstan-ignore-next-line */
        $url = Storage::disk('azure')->url('file.txt');
        self::assertIsString($url);
        self::assertStringNotContainsString('sig=', $url);
    }

    #[Test]
    public function driver_works_with_connection_string(): void
    {
        $connectionString = getenv('AZURE_STORAGE_CONNECTION_STRING');
        $container = getenv('AZURE_STORAGE_CONTAINER');

        if (! is_string($connectionString) || $connectionString === '' || ! is_string($container)) {
            self::markTestSkipped('AZURE_STORAGE_CONNECTION_STRING and AZURE_STORAGE_CONTAINER are required.');
        }

        config(['filesystems.disks.azure' => [
            'driver' => 'azure-storage-blob',
            'connection_string' => $connectionString,
            'container' => $container,
        ]]);

        $containerClient = self::createContainerClientFromConnectionString();
        $containerClient->deleteIfExists();
        $containerClient->create();

        $driver = Storage::disk('azure');

        $driver->deleteDirectory('');
        self::assertFalse($driver->exists('file.text'));

        $driver->put('file.txt', 'content');
        self::assertTrue($driver->exists('file.txt'));
        self::assertEquals('content', $driver->get('file.txt'));

        $driver->put('cache-control.txt', 'content', [
            'httpHeaders' => [
                'cacheControl' => 'public, max-age=31536000',
            ],
        ]);
        $properties = $containerClient->getBlobClient('cache-control.txt')->getProperties();
        self::assertSame('public, max-age=31536000', $properties->cacheControl);

        /** @phpstan-ignore-next-line */
        $temporaryUrl = $driver->temporaryUrl('file.txt', now()->addMinute());
        self::assertIsString($temporaryUrl);
        self::assertEquals('content', Http::get($temporaryUrl)->body());

        /** @phpstan-ignore-next-line */
        $url = $driver->url('file.txt');
        self::assertIsString($url);
        self::assertEquals('content', Http::get($url)->body());

        $driver->copy('file.txt', 'file2.txt');
        self::assertTrue($driver->exists('file2.txt'));

        $driver->move('file2.txt', 'file3.txt');
        self::assertFalse($driver->exists('file2.txt'));
        self::assertTrue($driver->exists('file3.txt'));

        /** @phpstan-ignore-next-line */
        $uploadData = $driver->temporaryUploadUrl('temp-upload-test.txt', now()->addMinutes(5), [
            'content-type' => 'text/plain',
        ]);
        self::assertIsArray($uploadData);
        self::assertIsString($uploadData['url']);
        self::assertIsArray($uploadData['headers']);

        $content = 'This content was uploaded directly to a temporary URL';
        $response = Http::withHeaders($uploadData['headers'])
            ->withBody($content, 'text/plain')
            ->put($uploadData['url']);
        self::assertTrue($response->successful());

        self::assertTrue($driver->exists('temp-upload-test.txt'));
        self::assertEquals($content, $driver->get('temp-upload-test.txt'));

        self::assertCount(4, $driver->allFiles());
        $driver->deleteDirectory('');
        self::assertCount(0, $driver->allFiles());
    }

    #[Test]
    public function driver_works_with_token(): void
    {
        $endpoint = getenv('AZURE_STORAGE_BLOB_ENDPOINT');
        $accountName = getenv('AZURE_STORAGE_BLOB_ACCOUNT_NAME');
        $tenantId = getenv('AZURE_STORAGE_BLOB_TENANT_ID');
        $clientId = getenv('AZURE_STORAGE_BLOB_CLIENT_ID');
        $clientSecret = getenv('AZURE_STORAGE_BLOB_CLIENT_SECRET');
        $container = getenv('AZURE_STORAGE_CONTAINER');

        $hasEndpoint = is_string($endpoint) && $endpoint !== '';
        $hasAccountName = is_string($accountName) && $accountName !== '';

        if (! $hasEndpoint && ! $hasAccountName) {
            self::markTestSkipped('AZURE_STORAGE_BLOB_ENDPOINT or AZURE_STORAGE_BLOB_ACCOUNT_NAME is required.');
        }

        if (! is_string($tenantId) || ! is_string($clientId) || ! is_string($clientSecret) || ! is_string($container)) {
            self::markTestSkipped('AZURE_STORAGE_BLOB_TENANT_ID, AZURE_STORAGE_BLOB_CLIENT_ID, AZURE_STORAGE_BLOB_CLIENT_SECRET, AZURE_STORAGE_CONTAINER are required.');
        }

        $diskConfig = [
            'driver' => 'azure-storage-blob',
            'tenant_id' => $tenantId,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'container' => $container,
        ];
        if ($hasEndpoint) {
            $diskConfig['endpoint'] = $endpoint;
        } else {
            $diskConfig['account_name'] = $accountName;
        }

        config(['filesystems.disks.azure' => $diskConfig]);

        $containerClient = self::createContainerClientFromToken();
        $containerClient->createIfNotExists();

        $driver = Storage::disk('azure');
        self::assertInstanceOf(AzureStorageBlobAdapter::class, $driver);

        $driver->deleteDirectory('');
        self::assertFalse($driver->exists('token-test.txt'));

        $driver->put('token-test.txt', 'token auth content');
        self::assertTrue($driver->exists('token-test.txt'));
        self::assertEquals('token auth content', $driver->get('token-test.txt'));

        self::assertFalse($driver->providesTemporaryUrls());

        $driver->delete('token-test.txt');
        self::assertFalse($driver->exists('token-test.txt'));
    }
}
