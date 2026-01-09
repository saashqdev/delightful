<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Asr\Constants;

/**
 * ASR configurationconstant
 * 统一管理 ASR 相关的所有configurationconstant，include超时time、轮询间隔、重试次数等.
 */
class AsrConfig
{
    // ==================== 超时configuration ====================

    /**
     * 总结task分布式锁 TTL（秒）.
     */
    public const int SUMMARY_LOCK_TTL = 120;

    /**
     * 心跳检测超时阈value（秒）.
     */
    public const int HEARTBEAT_TIMEOUT = 600;

    /**
     * taskstatus默认 TTL（秒）- 7天.
     */
    public const int TASK_STATUS_TTL = 604800;

    /**
     * Mock 轮询status TTL（秒）- 仅test用.
     */
    public const int MOCK_POLLING_TTL = 600;

    /**
     * 沙箱音频合并的最长等待time（秒）.
     */
    public const int SANDBOX_MERGE_TIMEOUT = 1200;

    /**
     * 音频文件recordquery超时（秒）.
     */
    public const int FILE_RECORD_QUERY_TIMEOUT = 120;

    /**
     * 沙箱启动超时（秒）.
     */
    public const int SANDBOX_STARTUP_TIMEOUT = 121;

    /**
     * 工作区initialize超时（秒）.
     */
    public const int WORKSPACE_INIT_TIMEOUT = 60;

    // ==================== 轮询间隔configuration ====================

    /**
     * 轮询间隔（秒）.
     */
    public const int POLLING_INTERVAL = 2;

    // ==================== 重试configuration ====================

    /**
     * service端自动总结最大重试次数.
     */
    public const int SERVER_SUMMARY_MAX_RETRY = 10;

    /**
     * 沙箱启动最大重试次数.
     */
    public const int SANDBOX_STARTUP_MAX_RETRY = 3;

    // ==================== logrecordconfiguration ====================

    /**
     * 沙箱音频合并logrecord间隔（秒）.
     */
    public const int SANDBOX_MERGE_LOG_INTERVAL = 10;

    /**
     * 沙箱音频合并logrecord频率（每N次尝试record一次）.
     */
    public const int SANDBOX_MERGE_LOG_FREQUENCY = 10;

    /**
     * 音频文件recordquerylogrecord频率（每N次尝试record一次）.
     */
    public const int FILE_RECORD_QUERY_LOG_FREQUENCY = 3;

    // ==================== Redis configuration ====================

    /**
     * Redis 扫描批次大小.
     */
    public const int REDIS_SCAN_BATCH_SIZE = 200;

    /**
     * Redis 扫描最大数量.
     */
    public const int REDIS_SCAN_MAX_COUNT = 2000;

    // ==================== 定时taskconfiguration ====================

    /**
     * 心跳监控定时task互斥锁过期time（秒）.
     */
    public const int HEARTBEAT_MONITOR_MUTEX_EXPIRES = 60;
}
