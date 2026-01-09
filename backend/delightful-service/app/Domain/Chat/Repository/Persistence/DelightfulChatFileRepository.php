<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Repository\Persistence;

use App\Domain\Chat\Entity\DelightfulChatFileEntity;
use App\Domain\Chat\Repository\Facade\DelightfulChatFileRepositoryInterface;
use App\Domain\Chat\Repository\Persistence\Model\DelightfulChatFileModel;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use Carbon\Carbon;
use Hyperf\DbConnection\Db;

class DelightfulChatFileRepository implements DelightfulChatFileRepositoryInterface
{
    public function __construct(
        protected DelightfulChatFileModel $delightfulChatFileModel
    ) {
    }

    public function uploadFile(DelightfulChatFileEntity $delightfulFileDTO): DelightfulChatFileEntity
    {
        if (empty($delightfulFileDTO->getFileId())) {
            $id = (string) IdGenerator::getSnowId();
            $delightfulFileDTO->setFileId($id);
        }
        $this->delightfulChatFileModel::query()->create($delightfulFileDTO->toArray());
        return $delightfulFileDTO;
    }

    public function uploadFiles(array $delightfulFileDTOs): array
    {
        $createData = [];
        $fileEntities = [];
        foreach ($delightfulFileDTOs as $delightfulFileDTO) {
            if (empty($delightfulFileDTO->getFileId())) {
                $id = (string) IdGenerator::getSnowId();
                $delightfulFileDTO->setFileId($id);
            }
            $createData[] = $delightfulFileDTO->toArray();
            $fileEntities[] = $delightfulFileDTO;
        }
        $this->delightfulChatFileModel::query()->insert($createData);
        return $fileEntities;
    }

    /**
     * @return DelightfulChatFileEntity[]
     */
    public function getChatFileByIds(array $fileIds, ?string $order = null, ?int $limit = null): array
    {
        if (empty($fileIds)) {
            return [];
        }
        $query = $this->delightfulChatFileModel::query()->whereIn('file_id', $fileIds);
        if (! is_null($order)) {
            $query->orderBy('created_at', $order);
        }
        if (! is_null($limit)) {
            $query->limit($limit);
        }
        $files = Db::select($query->toSql(), $query->getBindings());

        // Sort by fileIds order in PHP
        $fileMap = [];
        foreach ($files as $file) {
            $fileMap[$file['file_id']] = new DelightfulChatFileEntity($file);
        }

        $fileEntities = [];
        if (is_null($order)) {
            // If no order specified, return in fileIds order
            foreach ($fileIds as $fileId) {
                if (isset($fileMap[$fileId])) {
                    $fileEntities[] = $fileMap[$fileId];
                }
            }
        } else {
            // If order specified, return database sorted results
            $fileEntities = array_values($fileMap);
        }

        return $fileEntities;
    }

    /**
     * passfile_keyfindfile.
     */
    public function getChatFileByFileKey(string $fileKey): ?DelightfulChatFileEntity
    {
        $file = $this->delightfulChatFileModel::query()
            ->where('file_key', $fileKey)
            ->first();

        if (empty($file)) {
            return null;
        }

        return new DelightfulChatFileEntity($file->toArray());
    }

    /**
     * updatefileinfo.
     */
    public function updateFile(DelightfulChatFileEntity $fileEntity): void
    {
        $this->delightfulChatFileModel->newQuery()
            ->where('file_id', $fileEntity->getFileId())
            ->update([
                'file_type' => $fileEntity->getFileType(),
                'file_size' => $fileEntity->getFileSize(),
                'file_key' => $fileEntity->getFileKey(),
                'file_name' => $fileEntity->getFileName(),
                'file_extension' => $fileEntity->getFileExtension(),
                'updated_at' => Carbon::now(),
            ]);
    }

    public function updateFileById(string $fileId, DelightfulChatFileEntity $entity)
    {
        // fileupdate需谨慎，暂o clock只allowupdatefile_name
        $model = new DelightfulChatFileModel();
        $updateData = [];
        if ($entity->getFileKey()) {
            $updateData['file_key'] = $entity->getFileKey();
        }
        $updateData['updated_at'] = date('Y-m-d H:i:s');
        $model->query()->where('file_id', $fileId)->update($updateData);
    }
}
