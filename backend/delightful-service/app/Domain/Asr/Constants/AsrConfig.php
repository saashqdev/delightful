<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Asr\Constants;

/**
 * ASR configurationconstant
 * 统onemanage ASR 相close所haveconfigurationconstant，includetimeouttime、round询between隔、retrycountetc.
 */
class AsrConfig
{
    // ==================== timeoutconfiguration ====================

    /**
     * 总结taskminute布typelock TTL（second）.
     */
    public const int SUMMARY_LOCK_TTL = 120;

    /**
     * core跳detecttimeout阈value（second）.
     */
    public const int HEARTBEAT_TIMEOUT = 600;

    /**
     * taskstatusdefault TTL（second）- 7day.
     */
    public const int TASK_STATUS_TTL = 604800;

    /**
     * Mock round询status TTL（second）- 仅testuse.
     */
    public const int MOCK_POLLING_TTL = 600;

    /**
     * 沙箱audiomergemost长etc待time（second）.
     */
    public const int SANDBOX_MERGE_TIMEOUT = 1200;

    /**
     * audiofilerecordquerytimeout（second）.
     */
    public const int FILE_RECORD_QUERY_TIMEOUT = 120;

    /**
     * 沙箱starttimeout（second）.
     */
    public const int SANDBOX_STARTUP_TIMEOUT = 121;

    /**
     * work区initializetimeout（second）.
     */
    public const int WORKSPACE_INIT_TIMEOUT = 60;

    // ==================== round询between隔configuration ====================

    /**
     * round询between隔（second）.
     */
    public const int POLLING_INTERVAL = 2;

    // ==================== retryconfiguration ====================

    /**
     * service端from动总结most大retrycount.
     */
    public const int SERVER_SUMMARY_MAX_RETRY = 10;

    /**
     * 沙箱startmost大retrycount.
     */
    public const int SANDBOX_STARTUP_MAX_RETRY = 3;

    // ==================== logrecordconfiguration ====================

    /**
     * 沙箱audiomergelogrecordbetween隔（second）.
     */
    public const int SANDBOX_MERGE_LOG_INTERVAL = 10;

    /**
     * 沙箱audiomergelogrecordfrequency（eachNtime尝试recordonetime）.
     */
    public const int SANDBOX_MERGE_LOG_FREQUENCY = 10;

    /**
     * audiofilerecordquerylogrecordfrequency（eachNtime尝试recordonetime）.
     */
    public const int FILE_RECORD_QUERY_LOG_FREQUENCY = 3;

    // ==================== Redis configuration ====================

    /**
     * Redis 扫描batchtimesize.
     */
    public const int REDIS_SCAN_BATCH_SIZE = 200;

    /**
     * Redis 扫描most大quantity.
     */
    public const int REDIS_SCAN_MAX_COUNT = 2000;

    // ==================== scheduletaskconfiguration ====================

    /**
     * core跳monitorscheduletask互斥lockexpiretime（second）.
     */
    public const int HEARTBEAT_MONITOR_MUTEX_EXPIRES = 60;
}
