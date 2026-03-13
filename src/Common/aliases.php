<?php

declare(strict_types=1);

use AzureOss\Identity\AccessToken;
use AzureOss\Identity\ClientSecretCredential;
use AzureOss\Identity\TokenCredential;

// Backwards compatibility aliases (moved to azure-oss/identity)

if (! class_exists('AzureOss\\Storage\\Common\\Auth\\AccessToken', false) && class_exists(AccessToken::class)) {
    class_alias(AccessToken::class, 'AzureOss\\Storage\\Common\\Auth\\AccessToken');
}

if (! interface_exists('AzureOss\\Storage\\Common\\Auth\\TokenCredential', false) && interface_exists(TokenCredential::class)) {
    class_alias(TokenCredential::class, 'AzureOss\\Storage\\Common\\Auth\\TokenCredential');
}

if (! class_exists('AzureOss\\Storage\\Common\\Auth\\ClientSecretCredential', false) && class_exists(ClientSecretCredential::class)) {
    class_alias(ClientSecretCredential::class, 'AzureOss\\Storage\\Common\\Auth\\ClientSecretCredential');
}
