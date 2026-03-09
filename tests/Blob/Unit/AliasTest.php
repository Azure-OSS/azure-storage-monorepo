<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Blob\Unit;

use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AliasTest extends TestCase
{
    #[Test]
    #[RunInSeparateProcess]
    public function deprecated_alias_classes_work_immediately(): void
    {
        $aliases = [
            'AzureOss\\Storage\\Blob\\Options\\BlobClientOptions' => 'AzureOss\\Storage\\Blob\\Models\\BlobClientOptions',
            'AzureOss\\Storage\\Blob\\Options\\BlobContainerClientOptions' => 'AzureOss\\Storage\\Blob\\Models\\BlobContainerClientOptions',
            'AzureOss\\Storage\\Blob\\Options\\BlobServiceClientOptions' => 'AzureOss\\Storage\\Blob\\Models\\BlobServiceClientOptions',
            'AzureOss\\Storage\\Blob\\Options\\BlockBlobClientOptions' => 'AzureOss\\Storage\\Blob\\Models\\BlockBlobClientOptions',
        ];

        foreach ($aliases as $oldClass => $newClass) {
            // Verify the alias class exists BEFORE we explicitly reference the new class name in a way that would trigger autoloading.
            // PHPUnit's separate process includes vendor/autoload.php, so our aliases.php should have run.
            self::assertTrue(
                class_exists($oldClass, false),
                "The deprecated alias class '$oldClass' should be registered immediately by aliases.php (without triggering autoloading of the new class)."
            );

            $instance = new $oldClass;

            self::assertSame($newClass, get_class($instance));
        }
    }
}
