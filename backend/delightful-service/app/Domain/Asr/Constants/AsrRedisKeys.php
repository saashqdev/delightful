<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Asr\Constants;

/**
 * ASR Redis Key 常量
 * 统一管理 ASR 相关的 Redis Key format.
 */
class AsrRedisKeys
{
    /**
     * taskstatus Hash Key format
     * actualuse时will MD5(user_id:task_key).
     */
    public const TASK_HASH = 'asr:task:%s';

    /**
     * 心跳 Key format
     * actualuse时will MD5(user_id:task_key).
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
     * 心跳扫描mode.
     */
    public const HEARTBEAT_SCAN_PATTERN = 'asr:heartbeat:*';

    /**
     * Mock 轮询计数 Key format（仅用于test）.
     */
    public const MOCK_FINISH_COUNT = 'mock:asr:task:%s:finish_count';
}
