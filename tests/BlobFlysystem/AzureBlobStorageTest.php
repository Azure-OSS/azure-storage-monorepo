<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\BlobFlysystem;

use AzureOss\Storage\Blob\BlobContainerClient;
use AzureOss\Storage\Blob\BlobServiceClient;
use AzureOss\Storage\BlobFlysystem\AzureBlobStorageAdapter;
use League\Flysystem\AdapterTestUtilities\FilesystemAdapterTestCase;
use League\Flysystem\Config;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\Visibility;
use PHPUnit\Framework\Attributes\Test;

class AzureBlobStorageTest extends FilesystemAdapterTestCase
{
    public const CONTAINER_NAME = 'flysystem';

    protected static function createFilesystemAdapter(): FilesystemAdapter
    {
        $connectionString = getenv('AZURE_STORAGE_CONNECTION_STRING');

        if (! is_string($connectionString)) {
            self::fail('AZURE_STORAGE_CONNECTION_STRING is not provided.');
        }

        return new AzureBlobStorageAdapter(
            self::createContainerClient(),
            'flysystem',
        );
    }

    private static function createContainerClient(): BlobContainerClient
    {
        $connectionString = getenv('AZURE_STORAGE_CONNECTION_STRING');

        if (! is_string($connectionString)) {
            self::markTestSkipped('AZURE_STORAGE_CONNECTION_STRING is not provided.');
        }

        return BlobServiceClient::fromConnectionString($connectionString)->getContainerClient('flysystem');
    }

    public static function setUpBeforeClass(): void
    {
        self::createContainerClient()->deleteIfExists();
        self::createContainerClient()->create();
    }

    public function overwriting_a_file(): void
    {
        $this->runScenario(
            function () {
                $this->givenWeHaveAnExistingFile('path.txt');
                $adapter = $this->adapter();

                $adapter->write('path.txt', 'new contents', new Config);

                $contents = $adapter->read('path.txt');
                self::assertEquals('new contents', $contents);
            },
        );
    }

    public function setting_visibility(): void
    {
        self::markTestSkipped('Azure does not support visibility');
    }

    public function fetching_unknown_mime_type_of_a_file(): void
    {
        self::markTestSkipped('This adapter always returns a mime-type');
    }

    public function listing_contents_recursive(): void
    {
        self::markTestSkipped('This adapter does not support creating directories');
    }

    public function copying_a_file(): void
    {
        $this->runScenario(function () {
            $adapter = $this->adapter();
            $adapter->write(
                'source.txt',
                'contents to be copied',
                new Config([Config::OPTION_VISIBILITY => Visibility::PUBLIC]),
            );

            $adapter->copy('source.txt', 'destination.txt', new Config);

            self::assertTrue($adapter->fileExists('source.txt'));
            self::assertTrue($adapter->fileExists('destination.txt'));
            self::assertEquals('contents to be copied', $adapter->read('destination.txt'));
        });
    }

    public function moving_a_file(): void
    {
        $this->runScenario(function () {
            $adapter = $this->adapter();
            $adapter->write(
                'source.txt',
                'contents to be copied',
                new Config([Config::OPTION_VISIBILITY => Visibility::PUBLIC]),
            );
            $adapter->move('source.txt', 'destination.txt', new Config);
            self::assertFalse(
                $adapter->fileExists('source.txt'),
                'After moving a file should no longer exist in the original location.',
            );
            self::assertTrue(
                $adapter->fileExists('destination.txt'),
                'After moving, a file should be present at the new location.',
            );
            self::assertEquals('contents to be copied', $adapter->read('destination.txt'));
        });
    }

    public function copying_a_file_again(): void
    {
        $this->runScenario(function () {
            $adapter = $this->adapter();
            $adapter->write(
                'source.txt',
                'contents to be copied',
                new Config,
            );

            $adapter->copy('source.txt', 'destination.txt', new Config);

            self::assertTrue($adapter->fileExists('source.txt'));
            self::assertTrue($adapter->fileExists('destination.txt'));
            self::assertEquals('contents to be copied', $adapter->read('destination.txt'));
        });
    }

    public function checking_if_a_directory_exists_after_creating_it(): void
    {
        self::markTestSkipped('This adapter does not support creating directories');
    }

    public function setting_visibility_on_a_file_that_does_not_exist(): void
    {
        self::markTestSkipped('This adapter does not support visibility');
    }

    public function creating_a_directory(): void
    {
        self::markTestSkipped('This adapter does not support creating directories');
    }

    public function file_exists_on_directory_is_false(): void
    {
        $this->runScenario(function () {
            $adapter = $this->adapter();

            self::assertFalse($adapter->directoryExists('test'));

            $adapter->write('test/file.txt', '', new Config);

            self::assertTrue($adapter->directoryExists('test'));
            self::assertFalse($adapter->fileExists('test'));
        });
    }

    #[Test]
    public function setting_visibility_can_be_ignored_not_supported(): void
    {
        $this->givenWeHaveAnExistingFile('some-file.md');
        $this->expectNotToPerformAssertions();

        $adapter = new AzureBlobStorageAdapter(
            self::createContainerClient(),
            visibilityHandling: AzureBlobStorageAdapter::ON_VISIBILITY_IGNORE,
        );

        $adapter->setVisibility('some-file.md', 'public');
    }

    #[Test]
    public function setting_visibility_causes_errors(): void
    {
        $this->givenWeHaveAnExistingFile('some-file.md');
        $adapter = $this->adapter();

        $this->expectException(UnableToSetVisibility::class);

        $adapter->setVisibility('some-file.md', 'public');
    }

    #[Test]
    public function listing_contents_deep(): void
    {
        $this->runScenario(function () {
            $adapter = $this->adapter();

            $adapter->write('dir1/file1.txt', 'content1', new Config);
            $adapter->write('dir1/dir2/file2.txt', 'content2', new Config);
            $adapter->write('dir1/dir2/dir3/file3.txt', 'content3', new Config);
            $contents = iterator_to_array($adapter->listContents('', true));

            self::assertCount(6, $contents); // 3 files + 3 directories

            $paths = array_map(fn ($item) => $item->path(), $contents);
            self::assertContains('dir1', $paths);
            self::assertContains('dir1/file1.txt', $paths);
            self::assertContains('dir1/dir2', $paths);
            self::assertContains('dir1/dir2/file2.txt', $paths);
            self::assertContains('dir1/dir2/dir3', $paths);
            self::assertContains('dir1/dir2/dir3/file3.txt', $paths);
        });
    }

    #[Test]
    public function public_url_uses_direct_uri_when_enabled(): void
    {
        $this->givenWeHaveAnExistingFile('test-file.txt');

        $adapter = new AzureBlobStorageAdapter(
            self::createContainerClient(),
            'flysystem',
            useDirectPublicUrl: true,
        );

        $url = $adapter->publicUrl('test-file.txt', new Config);

        // Direct URL should not contain SAS token parameters
        self::assertStringNotContainsString('sig=', $url);
        self::assertStringNotContainsString('se=', $url);
        self::assertStringNotContainsString('sp=', $url);

        // But should contain the container and blob name
        self::assertStringContainsString('flysystem', $url);
        self::assertStringContainsString('test-file.txt', $url);
    }

    #[Test]
    public function public_url_uses_sas_token_by_default(): void
    {
        $this->givenWeHaveAnExistingFile('test-file.txt');

        $adapter = new AzureBlobStorageAdapter(
            self::createContainerClient(),
            'flysystem',
        );

        $url = $adapter->publicUrl('test-file.txt', new Config);

        // URL with SAS token should contain these parameters
        self::assertStringContainsString('sig=', $url);
        self::assertStringContainsString('se=', $url);
        self::assertStringContainsString('sp=', $url);
    }

    #[Test]
    public function deprecated_alias_class_works(): void
    {
        // Verify the alias class exists
        self::assertTrue(
            class_exists('AzureOss\FlysystemAzureBlobStorage\AzureBlobStorageAdapter'),
            'The deprecated alias class should exist',
        );

        // Use the old alias namespace
        $adapter = new \AzureOss\FlysystemAzureBlobStorage\AzureBlobStorageAdapter(
            self::createContainerClient(),
            'flysystem',
        );

        // Verify the classes are the same (alias points to the real class)
        self::assertEquals(
            AzureBlobStorageAdapter::class,
            $adapter::class,
        );
    }
}
