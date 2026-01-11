# Storage Module Usage Guide

This module provides a unified file storage interface, supporting multiple storage platforms (currently supports VolcEngine TOS, with planned support for Aliyun OSS).

## Quick Start

```python
from app.infrastructure.storage import StorageFactory, PlatformType, VolcEngineCredentials

# 1. Prepare storage credentials
credentials = VolcEngineCredentials(
    policy="your_policy",
    host="your_host",
    algorithm="TOS4-HMAC-SHA256",
    date="your_date",
    credential="your_credential",
    signature="your_signature",
    server_side_encryption="AES256",
    dir="your_dir",
    content_type="",
    callback="",
    expires=1743187877
)

# 2. Get storage instance
storage = StorageFactory.get_storage(PlatformType.tos)

# 3. Upload files
# 3.1 Upload string content
content = "Hello, World!".encode('utf-8')
result = storage.upload(
    file=content,
    key="test.txt",
    credentials=credentials,
    options={
        "progress": lambda p: print(f"Upload progress: {p:.2f}%"),
        "headers": {
            "Cache-Control": "max-age=31536000"
        }
    }
)

# 3.2 Upload local file
result = storage.upload(
    file="path/to/your/file.txt",
    key="remote_file.txt",
    credentials=credentials
)
```

## Supported Storage Platforms

- `PlatformType.tos`: VolcEngine Object Storage
- `PlatformType.aliyun`: Aliyun Object Storage (planned)

## Key Features

- Unified storage interface
- Support for multiple file types (strings, byte streams, local files)
- Upload progress callback
- Custom HTTP headers
- Server-side encryption
- Singleton pattern ensures efficient resource utilization

## Important Notes

1. Upload size limit: Maximum 5GB per file
2. Credential validity: Ensure upload credentials are within their validity period
3. File path: The key during upload will be automatically combined with the dir in the credentials
4. Temporary files: Remember to clean up temporary files promptly after using local file uploads

## Error Handling

```python
try:
    result = storage.upload(file=content, key="test.txt", credentials=credentials)
except InitException as e:
    print("Initialization error:", e)
except UploadException as e:
    print("Upload error:", e)
except ValueError as e:
    print("Parameter error:", e)
```

For more examples, please refer to `examples/volcengine_upload_example.py`. 