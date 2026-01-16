<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Repository\Facade;

use Delightful\BeDelightful\Domain\SuperAgent\Entity\TaskFileVersionEntity;

interface TaskFileVersionRepositoryInterface 
{
 /** * According toIDGetFileVersion. */ 
    public function getById(int $id): ?TaskFileVersionEntity; /** * According toFileIDGetAllVersionlist VersionReverse. * * @return TaskFileVersionEntity[] */ 
    public function getByFileId(int $fileId): array; /** * Getspecified FileVersionQuantity. */ 
    public function countByFileId(int $fileId): int; /** * Getspecified FileLatestVersion. */ 
    public function getLatestVersionNumber(int $fileId): int; /** * InsertFileVersion. */ 
    public function insert(TaskFileVersionEntity $entity): TaskFileVersionEntity; /** * According toFileIDdelete QuantityLimitOldVersion. */ 
    public function deleteOldVersionsByFileId(int $fileId, int $keepCount): int; /** * According toFileIDBatchdelete AllVersion. */ 
    public function deleteAllVersionsByFileId(int $fileId): int; /** * Getneed Cleanversion entities list . * * @return TaskFileVersionEntity[] */ 
    public function getVersionsToCleanup(int $fileId, int $keepCount): array; /** * PagingGetspecified FileVersionlist VersionReverse. * * @param int $fileId FileID * @param int $page Page numberFrom1Start * @param int $pageSize Per pageQuantity * @return array including list total Array */ 
    public function getByFileIdWithPage(int $fileId, int $page, int $pageSize): array; /** * According toFileIDVersionGetVersion. */ 
    public function getByFileIdAndVersion(int $fileId, int $version): ?TaskFileVersionEntity; 
}
 
