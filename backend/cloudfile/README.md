<h1 align="center">  cloud-file </h1>

<p align="center"> .</p>

## Introduction

This SDK is an enhanced version of the file service SDK, providing more features and being more user-friendly.

Built-in simple calls for Alibaba Cloud, Volcano Engine, and file services. File upload, download, and deletion operations can be completed with just a few lines of code.

Supports backend direct upload mode. After obtaining temporary credentials, the backend uploads files directly to cloud storage, reducing server pressure.

Replaceable `FilesystemAdapter` configuration with stronger customization.

Extracts common file service features into a package, allowing use without depending on the file service.

## Supported Cloud Services

 - Alibaba Cloud and Volcano Engine proxied by file service
 - Alibaba Cloud
 - Volcano Engine
 - MinIO / S3 (AWS S3 compatible)

## Key Features
- [x] Get temporary credentials
- [x] Upload files - direct upload via temporary credentials
- [x] Copy files
- [x] Delete files
- [x] Batch get accessible links
- [x] Get file metadata

## Notes
If you want to use direct Alibaba Cloud or Volcano Engine connection, you need to install the corresponding FilesystemAdapter first, such as

```composer
"suggest": {
    "hyperf/logger": "Required to use the Hyperf.",
    "hyperf/di": "Required to use the Hyperf.",
    "hyperf/config": "Required to use the Hyperf.",
    "hyperf/cache": "Required to use the Hyperf.",
    "alibabacloud/sts": "^1.8",
    "aliyuncs/oss-sdk-php": "^2.7",
    "league/flysystem": "^2.0",
    "xxtime/flysystem-aliyun-oss": "^1.6",
    "volcengine/ve-tos-php-sdk": "^2.1",
    "volcengine/volc-sdk-php": "^1.0"
},
```

Or in the config configuration, add the driver parameter, which is the FilesystemAdapter. Due to compatibility issues between packages, there may be bugs, but currently the file service is used more frequently, so let's ignore this for now and fix it if there are issues.

## Installation

```shell
$ composer require bedelightful/cloudfile -vvv
```

## Configuration

```php
$configs = [
    'storages' => [
        // File service configuration example
        'file_service_test' => [
            'adapter' => 'file_service',
            'config' => [
                // File service address
                'host' => 'xxx',
                // File service platform
                'platform' => 'xxx',
                // File service key
                'key' => 'xxx',
            ],
        ],
        // Alibaba Cloud configuration example
        'aliyun_test' => [
            'adapter' => 'aliyun',
            'config' => [
                'accessId' => 'xxx',
                'accessSecret' => 'xxx',
                'bucket' => 'xxx',
                'endpoint' => 'xxx',
                'role_arn' => 'xxx',
            ],
        ],
        // Volcano Engine configuration example
        'tos_test' => [
            'adapter' => 'tos',
            'config' => [
                'region' => 'xxx',
                'endpoint' => 'xxx',
                'ak' => 'xxx',
                'sk' => 'xxx',
                'bucket' => 'xxx',
                'trn' => 'xxx',
            ],
        ],
        // MinIO/S3 configuration example
        's3_test' => [
            'adapter' => 's3', // or 'minio'
            'config' => [
                'region' => 'us-east-1',
                'endpoint' => 'http://localhost:9000', // MinIO service address
                'accessKey' => 'xxx',
                'secretKey' => 'xxx',
                'bucket' => 'xxx',
                'use_path_style_endpoint' => true, // Must be true for MinIO
                'version' => 'latest',
                'role_arn' => 'xxx', // Optional, for STS temporary credentials
            ],
        ],
    ],
];

$container = new SdkContainer([
    // SDK basic configuration
    'sdk_name' => 'easy_file_sdk',
    'exception_class' => CloudFileException::class,·
    // cloudfile configuration
    'cloudfile' => $configs,
]);

$cloudFile = new CloudFile($container);
```

## File Service Specifics
Because requesting the file service requires dynamic token and organization-code, these need to be placed in the options parameter. **All** file service requests need to include them, as follows:

```php
$filesystem = $cloudFile->get('file_service_test');

$options = [
    'token' => 'xxx',
    'organization-code' => 'xxx',
    'cache' => false, // Set as needed, recommend false for easier debugging
];

```

## Usage

### Get Temporary Credentials

```php
$filesystem = $cloudFile->get('file_service_test');

$credentialPolicy = new CredentialPolicy([
    'sts' => false,
    'roleSessionName' => 'test',
]);
$options = [
    'token' => 'xxx',
    'organization-code' => 'xxx',
];
$data = $filesystem->getUploadTemporaryCredential($credentialPolicy, $options);
```

### Upload File - Direct Upload via Temporary Credential
After uploading, remember to check `$uploadFile->getKey()` to get the actual file path after upload (because the file service will prepend organization/application prefix)

```php
$filesystem = $cloudFile->get('file_service_test');

$credentialPolicy = new CredentialPolicy([
    'sts' => false,
]);

$realPath = __DIR__ . '/../test.txt';

$uploadFile = new UploadFile($realPath, 'easy-file');
$options = [
    'token' => 'xxx',
    'organization-code' => 'xxx',
];
$filesystem->uploadByCredential($uploadFile, $credentialPolicy, $options);
```

### Copy File

```php
$filesystem = $cloudFile->get('file_service_test');

$options = [
    'token' => 'xxx',
    'organization-code' => 'xxx',
];
// After successfully copying the file, you need to get this path result as the real address, because the file service will handle permissions
$path = $filesystem->duplicate('easy-file/test.txt', 'easy-file/test-copy.txt', $options);
```

### Delete File

```php
$filesystem = $cloudFile->get('file_service_test');

$options = [
    'token' => 'xxx',
    'organization-code' => 'xxx',
];
$filesystem->destroy('easy-file/test.txt', $options);
```

### Batch Get Accessible Links
> When requesting file service, links are returned directly without checking existence
```php
$filesystem = $cloudFile->get('file_service_test');

$options = [
    'token' => 'xxx',
    'organization-code' => 'xxx',
];
$list = $filesystem->getLinks([
    'easy-file/file-service.txt',
    'easy-file/test.txt',
], [], 7200, $options);
```

### Get File Metadata

```php
$filesystem = $cloudFile->get('file_service_test');

$options = [
    'token' => 'xxx',
    'organization-code' => 'xxx',
];
$list = $filesystem->getMetas([
    'easy-file/file-service.txt',
    'easy-file/test.txt'], $options);
```
## Hyperf Quick Usage

### Publish Configuration File
```shell
$ php bin/hyperf.php vendor:publish delightful/cloudfile
```

### Usage
```php
// You can inject CloudFileFactory in the constructor here
$cloudFile = \Hyperf\Support\make(CloudFileFactory::class)->create();

$filesystem = $cloudFile->get('file_service');

$options = [
    // Dynamic token needs to be passed in by yourself
    'token' => 'xxx',
    'organization-code' => 'xxx',
];
$list = $filesystem->getLinks([
    'easy-file/file-service.txt',
    'easy-file/test.txt',
], [], 7200, $options);

$link = $list[0]->getUrl();
```

### Using MinIO in Hyperf
```php
// Configure in .env
MINIO_ENDPOINT=http://localhost:9000
MINIO_REGION=us-east-1
MINIO_ACCESS_KEY=your-access-key
MINIO_SECRET_KEY=your-secret-key
MINIO_BUCKET=your-bucket

// Use in code
$cloudFile = \Hyperf\Support\make(CloudFileFactory::class)->create();
$filesystem = $cloudFile->get('minio');

// Upload file
$uploadFile = new UploadFile('/path/to/file.txt', 'my-folder');
$credentialPolicy = new CredentialPolicy(['sts' => false]);
$filesystem->uploadByCredential($uploadFile, $credentialPolicy);

// Get file links
$links = $filesystem->getLinks(['my-folder/file.txt'], [], 3600);
```

## MinIO / S3 Usage Instructions

### Configuration Key Points
MinIO is AWS S3 compatible object storage, note when using:
- `use_path_style_endpoint` must be set to `true`
- `endpoint` set to MinIO service address (e.g., `http://localhost:9000`)
- Supports STS temporary credential functionality (requires `role_arn` configuration)

### Basic Usage Example

```php
$filesystem = $cloudFile->get('s3_test');

// Get temporary credentials
$credentialPolicy = new CredentialPolicy([
    'sts' => false, // Simple signature mode
    'roleSessionName' => 'test',
]);
$credential = $filesystem->getUploadCredential($credentialPolicy);

// Upload file
$realPath = __DIR__ . '/test.txt';
$uploadFile = new UploadFile($realPath, 'my-folder');
$filesystem->uploadByCredential($uploadFile, $credentialPolicy);

// Get file links
$links = $filesystem->getLinks(['my-folder/test.txt'], [], 3600);
$downloadUrl = $links[0]->getUrl();

// Copy file
$newPath = $filesystem->duplicate('my-folder/test.txt', 'my-folder/test-copy.txt');

// Delete file
$filesystem->destroy('my-folder/test.txt');
```

### MinIO vs AWS S3 Differences
- MinIO uses path-style access by default (`http://endpoint/bucket/key`)
- AWS S3 uses virtual-hosted-style access by default (`http://bucket.endpoint/key`)
- By setting `use_path_style_endpoint => true`, path-style can be used uniformly
```
