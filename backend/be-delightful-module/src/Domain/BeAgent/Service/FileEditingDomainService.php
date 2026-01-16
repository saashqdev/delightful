<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Service;

use Hyperf\Redis\Redis;
use Psr\including er\including erInterface;
/** * FileEditStatusService */

class FileEditingDomainService 
{
 
    private 
    const REDIS_KEY_PREFIX = 'file_editing_status'; 
    private 
    const TTL_SECONDS = 120; // 2 
    private Redis $redis; 
    public function __construct(including erInterface $container) 
{
 $this->redis = $container->get(Redis::class); 
}
 /** * JoinEdit. */ 
    public function joinEditing(int $fileId, string $userId, string $organizationCode): void 
{
 $key = $this->buildRedisKey($fileId, $organizationCode); // Adduser Editlist $this->redis->sadd($key, $userId); $this->redis->expire($key, self::TTL_SECONDS); 
}
 /** * Edit. */ 
    public function leaveEditing(int $fileId, string $userId, string $organizationCode): void 
{
 $key = $this->buildRedisKey($fileId, $organizationCode); // FromEditlist in Removeuser $this->redis->srem($key, $userId); // IfDon't haveuser AtEditdelete key if ($this->redis->scard($key) === 0) 
{
 $this->redis->del($key); 
}
 
}
 /** * GetEdituser Quantity. */ 
    public function getEditinguser sCount(int $fileId, string $organizationCode): int 
{
 $key = $this->buildRedisKey($fileId, $organizationCode); // Return Edituser Quantity return $this->redis->scard($key); 
}
 /** * BuildRedisKey. */ 
    public function buildRedisKey(int $fileId, string $organizationCode): string 
{
 return sprintf('%s:%s:%d', self::REDIS_KEY_PREFIX, $organizationCode, $fileId); 
}
 
}
 
