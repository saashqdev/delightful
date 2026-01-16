<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\Share\Repository\Facade;

use Delightful\BeDelightful\Domain\Share\Entity\ResourceShareEntity;
/** * ResourceShareRepository interface. */

interface ResourceShareRepositoryInterface 
{
 /** * ThroughIDGetShare. * * @param int $shareId ShareID * @return null|ResourceShareEntity ResourceShare */ 
    public function getShareById(int $shareId): ?ResourceShareEntity; /** * ThroughShareGetShare. * * @param string $shareCode Share * @return null|ResourceShareEntity ResourceShare */ 
    public function getShareByCode(string $shareCode): ?ResourceShareEntity; 
    public function getShareByResourceId(string $resourceId): ?ResourceShareEntity; /** * SaveShare. * * @param ResourceShareEntity $shareEntity ResourceShare * @return ResourceShareEntity SaveResourceShare */ 
    public function save(ResourceShareEntity $shareEntity): ResourceShareEntity; /** * delete Share. * * @param int $shareId ShareID * @param bool $forcedelete whether Forcedelete delete Defaultfalseas delete * @return bool whether Success */ 
    public function delete(int $shareId, bool $forcedelete = false): bool; /** * IncreaseShareView. * * @param string $shareCode Share * @return bool whether Success */ 
    public function incrementViewCount(string $shareCode): bool; /** * Pagingquery . * * @param array $conditions query Condition * @param int $page Page number * @param int $pageSize Per pageQuantity * @return array PagingResult */ 
    public function paginate(array $conditions, int $page = 1, int $pageSize = 20): array; 
    public function getShareByResource(string $userId, string $resourceId, int $resourceType, bool $withTrashed = true): ?ResourceShareEntity; /** * check Sharewhether Already exists. * * @param string $shareCode Share * @return bool whether Already exists */ 
    public function isShareCodeExists(string $shareCode): bool; 
}
 
