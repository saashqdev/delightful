<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Asr\Constants;

/**
 * ASR Redis Key 常quantity
 * 统onemanage ASR 相close Redis Key format.
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
     * summarytasklock Key format.
     */
    public const SUMMARY_LOCK = 'asr:summary:task:%s';

    /**
     * taskstatusscanmode.
     */
    public const TASK_SCAN_PATTERN = 'asr:task:*';

    /**
     * core跳scanmode.
     */
    public const HEARTBEAT_SCAN_PATTERN = 'asr:heartbeat:*';

    /**
     * Mock round询count Key format(onlyuseattest).
     */
    public const MOCK_FINISH_COUNT = 'mock:asr:task:%s:finish_count';
}
