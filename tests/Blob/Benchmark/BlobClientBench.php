<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Blob\Benchmark;

use AzureOss\Storage\Tests\Blob\CreatesTempContainers;
use AzureOss\Storage\Tests\Blob\CreatesTempFiles;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class BlobClientBench extends TestCase
{
    use CreatesTempContainers, CreatesTempFiles;

    #[Test]
    #[DataProvider('provideFiles')]
    public function upload_uses_low_memory(int $fileSize, int $count): void
    {
        $container = $this->tempContainer();
        $blob = $container->getBlobClient('benchmark');

        $startMemory = memory_get_peak_usage(true);

        for ($i = 0; $i < $count; $i++) {
            $file = $this->tempFile($fileSize);
            $blob->upload($file);
        }

        $endMemory = memory_get_peak_usage(true);

        $memoryUsed = ($endMemory - $startMemory) / 1024 / 1024; // MB

        // Assert memory usage is reasonable (< 16MB)
        self::assertLessThan(16, $memoryUsed, 'Memory usage should be less than 16MB');
    }

    public static function provideFiles(): \Generator
    {
        yield '100x10KB' => [10_000, 100];
        yield '10x10MB' => [10_000_000, 10];
        yield '5x100MB' => [100_000_000, 5];
        yield '2x1GB' => [1_000_000_000, 2];
        yield '1x4GB' => [4_000_000_000, 1];
    }
}
