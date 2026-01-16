<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Constant;

/** * Topic duplication process constants. */

class TopicDuplicateConstant 
{
 // ====== Cache Prefixes ====== 
    public 
    const string CACHE_PREFIX = 'topic_duplicate:'; 
    public 
    const string USER_KEY_PREFIX = 'topic_duplicate_user:'; // ====== Cache TTL (seconds) ====== 
    public 
    const int TTL_TASK_STATUS = 300; // 5 minutes - enough for 30s task + queries 
    public 
    const int TTL_USER_PERMISSION = 300; // 5 minutes - same as task status // ====== Status Messages ====== 
    public 
    const string MSG_TASK_INITIALIZING = 'Initializing topic duplication'; 
    public 
    const string MSG_TASK_PROCESSING = 'Topic duplication in progress'; 
    public 
    const string MSG_TASK_COMPLETED = 'Topic duplication completed successfully'; 
    public 
    const string MSG_TASK_FAILED = 'Topic duplication failed'; // ====== Helper Methods ====== /** * Get task cache key. */ 
    public 
    static function getTaskKey(string $taskKey): string 
{
 return self::CACHE_PREFIX . 'task:' . $taskKey; 
}
 /** * Get user permission cache key. */ 
    public 
    static function getuser Key(string $taskKey): string 
{
 return self::USER_KEY_PREFIX . $taskKey; 
}
 /** * Generate task key. * Format: duplicate_topic_
{
short_topic_id
}
_
{
user_hash
}
_
{
time_random
}
 * Example: duplicate_topic_834909592_a1b2c3_67310x9k (about 42 characters). */ 
    public 
    static function generateTaskKey(string $sourceTopicId, string $userId): string 
{
 // 1. Keep easy-to-identify prefix $prefix = 'duplicate_topic'; // 2. topic_id (9) $shortTopicId = substr($sourceTopicId, -9); // 3. user ID (6MD5) $userHash = substr(md5($userId), 0, 6); // 4. Timestamp5 + 3 = 8Identifier $timeSuffix = substr((string) time(), -5); $random = substr(uniqid(), -3); $timeRandom = $timeSuffix . $random; return sprintf('%s_%s_%s_%s', $prefix, $shortTopicId, $userHash, $timeRandom); 
}
 
}
 
