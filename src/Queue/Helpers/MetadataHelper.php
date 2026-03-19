<?php

declare(strict_types=1);

namespace AzureOss\Storage\Queue\Helpers;

/**
 * @internal
 */
final class MetadataHelper
{
    /**
     * @param  string[][]  $headers
     * @return array<string>
     */
    public static function headersToMetadata(array $headers): array
    {
        $metadata = [];

        foreach ($headers as $key => $value) {
            if (str_starts_with($key, 'x-ms-meta-')) {
                $metadata[substr($key, strlen('x-ms-meta-'))] = implode(', ', $value);
            }
        }

        return $metadata;
    }
}
