<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Asr\Constants;

/**
 * ASR Redis Key 常quantity
 * 统一管理 ASR 相关的 Redis Key format.
 */
class AsrRedisKeys
{
    /**
     * taskstatus Hash Key format
     * actualuseo clockwill MD5(user_id:task_key).
     */
    public const TASK_HASH = 'asr:task:%s';

    /**
     * core跳 Key format
     * actualuseo clockwill MD5(user_id:task_key).
     */
    public const HEARTBEAT = 'asr:heartbeat:%s';

    /**
     * 总结tasklock Key format.
     */
    public const SUMMARY_LOCK = 'asr:summary:task:%s';

    /**
     * taskstatus扫描mode.
     */
    public const TASK_SCAN_PATTERN = 'asr:task:*';

    /**
     * core跳扫描mode.
     */
    public const HEARTBEAT_SCAN_PATTERN = 'asr:heartbeat:*';

    /**
     * Mock round询计数 Key format（仅useattest）.
     */
    public const MOCK_FINISH_COUNT = 'mock:asr:task:%s:finish_count';
}
