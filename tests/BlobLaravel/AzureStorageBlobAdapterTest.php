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

    protected function getEnvironmentSetUp($app): void
    {
        /** @phpstan-ignore-next-line */
        $app['config']->set('filesystems.disks.azure', [
            'driver' => 'azure-storage-blob',
            'connection_string' => env('AZURE_STORAGE_CONNECTION_STRING'),
            'container' => env('AZURE_STORAGE_CONTAINER'),
        ]);
    }

    private static function createContainerClient(): BlobContainerClient
    {
        $connectionString = getenv('AZURE_STORAGE_CONNECTION_STRING');
        $container = getenv('AZURE_STORAGE_CONTAINER');

        if (! is_string($connectionString)) {
            self::markTestSkipped('AZURE_STORAGE_CONNECTION_STRING is not provided.');
        }

        if (! is_string($container)) {
            self::markTestSkipped('AZURE_STORAGE_CONTAINER is not provided.');
        }

        return BlobServiceClient::fromConnectionString($connectionString)->getContainerClient(
            $container
        );
    }

    public static function setUpBeforeClass(): void
    {
        self::createContainerClient()->deleteIfExists();
        self::createContainerClient()->create();
    }

    #[Test]
    public function it_resolves_from_manager(): void
    {
        self::assertInstanceOf(AzureStorageBlobAdapter::class, Storage::disk('azure'));
    }

    #[Test]
    public function driver_works(): void
    {
        $driver = Storage::disk('azure');

        // cleanup from previous test runs
        $driver->deleteDirectory('');

        self::assertFalse($driver->exists('file.text'));

        $driver->put('file.txt', 'content');

        self::assertTrue($driver->exists('file.txt'));

        self::assertEquals(
            'content',
            $driver->get('file.txt'),
        );
        /** @phpstan-ignore-next-line */
        $temporaryUrl = $driver->temporaryUrl('file.txt', now()->addMinute());
        self::assertIsString($temporaryUrl);
        self::assertEquals(
            'content',
            Http::get($temporaryUrl)->body(),
        );

        /** @phpstan-ignore-next-line */
        $url = $driver->url('file.txt');
        self::assertIsString($url);
        self::assertEquals(
            'content',
            Http::get($url)->body(),
        );

        $driver->copy('file.txt', 'file2.txt');

        self::assertTrue($driver->exists('file2.txt'));

        $driver->move('file2.txt', 'file3.txt');

        self::assertFalse($driver->exists('file2.txt'));
        self::assertTrue($driver->exists('file3.txt'));

        // Test temporary upload URL functionality
        // Generate a temporary upload URL
        /** @phpstan-ignore-next-line */
        $uploadData = $driver->temporaryUploadUrl('temp-upload-test.txt', now()->addMinutes(5), [
            'content-type' => 'text/plain',
        ]);

        self::assertIsArray($uploadData);
        self::assertIsString($uploadData['url']);
        self::assertIsArray($uploadData['headers']);

        // Upload content directly to the URL
        $content = 'This content was uploaded directly to a temporary URL';
        $response = Http::withHeaders($uploadData['headers'])
            ->withBody($content, 'text/plain')
            ->put($uploadData['url']);

        // Verify the upload was successful
        self::assertTrue($response->successful());

        // Verify the file exists and has the correct content
        self::assertTrue($driver->exists('temp-upload-test.txt'));
        self::assertEquals($content, $driver->get('temp-upload-test.txt'));

        // Count files and clean up
        self::assertCount(3, $driver->allFiles()); // file.txt, file3.txt, and temp-upload-test.txt

        $driver->deleteDirectory('');

        self::assertCount(0, $driver->allFiles());
    }
}
