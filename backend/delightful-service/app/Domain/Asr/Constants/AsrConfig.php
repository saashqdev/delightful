<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Asr\Constants;

/**
 * ASR configurationconstant
 * 统一管理 ASR 相关的所有configurationconstant，includetimeouttime、轮询间隔、retrycount等.
 */
class AsrConfig
{
    // ==================== timeoutconfiguration ====================

    /**
     * 总结task分布式lock TTL（秒）.
     */
    public const int SUMMARY_LOCK_TTL = 120;

    /**
     * 心跳检测timeout阈value（秒）.
     */
    public const int HEARTBEAT_TIMEOUT = 600;

    /**
     * taskstatusdefault TTL（秒）- 7天.
     */
    public const int TASK_STATUS_TTL = 604800;

    /**
     * Mock 轮询status TTL（秒）- 仅test用.
     */
    public const int MOCK_POLLING_TTL = 600;

    /**
     * 沙箱audiomerge的最长等待time（秒）.
     */
    public const int SANDBOX_MERGE_TIMEOUT = 1200;

    /**
     * audiofilerecordquerytimeout（秒）.
     */
    public const int FILE_RECORD_QUERY_TIMEOUT = 120;

    /**
     * 沙箱starttimeout（秒）.
     */
    public const int SANDBOX_STARTUP_TIMEOUT = 121;

    /**
     * 工作区initializetimeout（秒）.
     */
    public const int WORKSPACE_INIT_TIMEOUT = 60;

    // ==================== 轮询间隔configuration ====================

    /**
     * 轮询间隔（秒）.
     */
    public const int POLLING_INTERVAL = 2;

    // ==================== retryconfiguration ====================

    /**
     * service端自动总结最大retrycount.
     */
    public const int SERVER_SUMMARY_MAX_RETRY = 10;

    /**
     * 沙箱start最大retrycount.
     */
    public const int SANDBOX_STARTUP_MAX_RETRY = 3;

    // ==================== logrecordconfiguration ====================

    /**
     * 沙箱audiomergelogrecord间隔（秒）.
     */
    public const int SANDBOX_MERGE_LOG_INTERVAL = 10;

    /**
     * 沙箱audiomergelogrecordfrequency（每N次尝试record一次）.
     */
    public const int SANDBOX_MERGE_LOG_FREQUENCY = 10;

    /**
     * audiofilerecordquerylogrecordfrequency（每N次尝试record一次）.
     */
    public const int FILE_RECORD_QUERY_LOG_FREQUENCY = 3;

    // ==================== Redis configuration ====================

    /**
     * Redis 扫描批次size.
     */
    public const int REDIS_SCAN_BATCH_SIZE = 200;

    /**
     * Redis 扫描最大quantity.
     */
    public const int REDIS_SCAN_MAX_COUNT = 2000;

    // ==================== scheduletaskconfiguration ====================

    /**
     * 心跳monitorscheduletask互斥lockexpiretime（秒）.
     */
    public const int HEARTBEAT_MONITOR_MUTEX_EXPIRES = 60;
}
