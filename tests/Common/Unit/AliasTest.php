<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Common\Unit;

use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AliasTest extends TestCase
{
    #[Test]
    #[RunInSeparateProcess]
    public function deprecated_identity_aliases_work_immediately(): void
    {
        $oldAccessToken = 'AzureOss\\Storage\\Common\\Auth\\AccessToken';
        $newAccessToken = 'AzureOss\\Identity\\AccessToken';

        $oldTokenCredential = 'AzureOss\\Storage\\Common\\Auth\\TokenCredential';

        $oldClientSecretCredential = 'AzureOss\\Storage\\Common\\Auth\\ClientSecretCredential';
        $newClientSecretCredential = 'AzureOss\\Identity\\ClientSecretCredential';

        self::assertTrue(
            class_exists($oldAccessToken, false),
            "The deprecated alias class '$oldAccessToken' should be registered immediately by aliases.php."
        );
        self::assertTrue(
            interface_exists($oldTokenCredential, false),
            "The deprecated alias interface '$oldTokenCredential' should be registered immediately by aliases.php."
        );
        self::assertTrue(
            class_exists($oldClientSecretCredential, false),
            "The deprecated alias class '$oldClientSecretCredential' should be registered immediately by aliases.php."
        );

        $token = new $oldAccessToken('token', new \DateTimeImmutable('+1 hour'), 'Bearer');
        self::assertSame($newAccessToken, get_class($token));

        $credential = new $oldClientSecretCredential('tenant-id', 'client-id', 'client-secret');
        self::assertSame($newClientSecretCredential, get_class($credential));
    }
}
