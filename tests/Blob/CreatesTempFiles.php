<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Blob;

use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\Attributes\After;
use Psr\Http\Message\StreamInterface;

trait CreatesTempFiles
{
    /** @var list<string> */
    private array $tempFiles = [];

    /**
     * Creates a temporary file and returns a readable stream.
     *
     * @return resource
     */
    protected function tempFile(int $sizeInBytes): StreamInterface
    {
        $path = tempnam(sys_get_temp_dir(), 'azure-test-');

        if ($path === false) {
            throw new \RuntimeException('Failed to create temporary file');
        }

        $this->tempFiles[] = $path;

        $handle = fopen($path, 'wb');
        $chunkSize = 8192;
        $written = 0;

        while ($written < $sizeInBytes) {
            $remaining = $sizeInBytes - $written;
            $write = min($chunkSize, $remaining);
            fwrite($handle, str_repeat('a', $write));
            $written += $write;
        }

        fclose($handle);

        $stream = fopen($path, 'rb');

        if ($stream === false) {
            throw new \RuntimeException("Failed to open temporary file: {$path}");
        }

        return Utils::streamFor($stream);
    }

    #[After]
    protected function cleanupTempFiles(): void
    {
        foreach ($this->tempFiles as $path) {
            try {
                if (file_exists($path)) {
                    unlink($path);
                }
            } catch (\Throwable) {
            }
        }

        $this->tempFiles = [];
    }
}
