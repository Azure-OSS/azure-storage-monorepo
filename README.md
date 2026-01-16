# Azure Storage PHP Monorepo

This monorepo contains the source code for the Azure Storage PHP SDK and related packages.

## Packages

This monorepo contains the following packages:

### [azure-oss/storage](src/Blob/)
The core Azure Blob Storage PHP SDK. This is the main package that provides the core functionality for interacting with Azure Blob Storage.

### [azure-oss/storage-blob-flysystem](src/BlobFlysystem/)
Flysystem adapter for Azure Storage PHP. Provides integration with the [Flysystem](https://flysystem.thephpleague.com/) filesystem abstraction library.

### [azure-oss/storage-blob-laravel](src/BlobLaravel/)
Laravel filesystem driver for Azure Storage Blob. Provides seamless integration with Laravel's filesystem abstraction.

## Development

This is a monorepo managed using git subtrees. Each package is maintained in this repository and can be published independently.

### Requirements

* PHP 8.1 or above
* Composer

### Installation

To work on this monorepo locally:

```shell
composer install
```

### Testing

Run the test suite:

```shell
composer test
```

### Code Quality

This project uses:
* [PHPUnit](https://phpunit.de/) for testing
* [PHPStan](https://phpstan.org/) for static analysis
* [Laravel Pint](https://laravel.com/docs/pint) for code formatting

## Contributing

Please read our [Contributing Guide](.github/CONTRIBUTING.md) before submitting issues or pull requests.

## Issues & Support

**All issues should be reported in the [monorepo issue tracker](https://github.com/Azure-OSS/azure-storage-monorepo/issues).**

Do not create issues in individual package repositories. All issues, bug reports, and feature requests should be submitted to the monorepo.

For support and discussions:
* [Github Discussions](https://github.com/Azure-OSS/azure-storage-monorepo/discussions)
* [Slack](https://join.slack.com/t/azure-oss/shared_invite/zt-2lw5knpon-mqPM_LIuRZUoH02AY8uiYw)

## License

This project is released under the MIT License. See [LICENSE](./LICENSE) for details.
