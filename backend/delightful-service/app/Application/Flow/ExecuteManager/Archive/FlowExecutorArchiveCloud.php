<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Flow\ExecuteManager\Archive;

use App\Domain\File\Service\FileDomainService;
use App\Infrastructure\Core\ValueObject\StorageBucketType;
use Delightful\CloudFile\Kernel\Struct\UploadFile;

class FlowExecutorArchiveCloud
{
    public static function put(string $organizationCode, string $key, array $data): string
    {
        $name = "{$key}.log";

        // 直接检查序列化后的data大小
        $serializedData = serialize($data);
        $dataSize = strlen($serializedData);
        $maxSize = 100 * 1024 * 1024; // 100MB

        if ($dataSize > $maxSize) {
            // data过大，不upload，直接returnnullstring
            return '';
        }

        $tmpDir = sys_get_temp_dir();
        $tmpFile = "{$tmpDir}/{$name}." . uniqid();

        try {
            // data大小符合要求，save到临时file
            file_put_contents($tmpFile, $serializedData);

            $uploadFile = new UploadFile($tmpFile, dir: 'DelightfulFlowExecutorArchive', name: $name, rename: false);
            di(FileDomainService::class)->uploadByCredential($organizationCode, $uploadFile, storage: StorageBucketType::Private, autoDir: false);
            return $uploadFile->getKey();
        } finally {
            if (file_exists($tmpFile)) {
                @unlink($tmpFile);
            }
        }
    }

    public static function get(string $organizationCode, string $executionId): mixed
    {
        $appId = config('kk_brd_service.app_id', 'open');
        $name = "{$organizationCode}/{$appId}/DelightfulFlowExecutorArchive/{$executionId}.log";
        $file = di(FileDomainService::class)->getLink($organizationCode, $name, StorageBucketType::Private);
        return unserialize(file_get_contents($file->getUrl()));
    }
}
