<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Asr\Constants;

/**
 * ASR configurationconstant
 * 统onemanage ASR relatedclose haveconfigurationconstant,includetimeouttime,roundquerybetweenseparator,retrycountetc.
 */
class AsrConfig
{
    // ==================== timeoutconfiguration ====================

    /**
     * summarytaskminute布typelock TTL(second).
     */
    public const int SUMMARY_LOCK_TTL = 120;

    /**
     * corejumpdetecttimeout阈value(second).
     */
    public const int HEARTBEAT_TIMEOUT = 600;

    /**
     * taskstatusdefault TTL(second)- 7day.
     */
    public const int TASK_STATUS_TTL = 604800;

    /**
     * Mock roundquerystatus TTL(second)- onlytestuse.
     */
    public const int MOCK_POLLING_TTL = 600;

    /**
     * sandboxaudiomergemostlongetcpendingtime(second).
     */
    public const int SANDBOX_MERGE_TIMEOUT = 1200;

    /**
     * audiofilerecordquerytimeout(second).
     */
    public const int FILE_RECORD_QUERY_TIMEOUT = 120;

    /**
     * sandboxstarttimeout(second).
     */
    public const int SANDBOX_STARTUP_TIMEOUT = 121;

    /**
     * workregioninitializetimeout(second).
     */
    public const int WORKSPACE_INIT_TIMEOUT = 60;

    // ==================== roundquerybetweenseparatorconfiguration ====================

    /**
     * roundquerybetweenseparator(second).
     */
    public const int POLLING_INTERVAL = 2;

    // ==================== retryconfiguration ====================

    /**
     * serviceclientfromautosummarymostbigretrycount.
     */
    public const int SERVER_SUMMARY_MAX_RETRY = 10;

    /**
     * sandboxstartmostbigretrycount.
     */
    public const int SANDBOX_STARTUP_MAX_RETRY = 3;

    // ==================== logrecordconfiguration ====================

    /**
     * sandboxaudiomergelogrecordbetweenseparator(second).
     */
    public const int SANDBOX_MERGE_LOG_INTERVAL = 10;

    /**
     * sandboxaudiomergelogrecordfrequency(eachNtimetryrecordonetime).
     */
    public const int SANDBOX_MERGE_LOG_FREQUENCY = 10;

    /**
     * audiofilerecordquerylogrecordfrequency(eachNtimetryrecordonetime).
     */
    public const int FILE_RECORD_QUERY_LOG_FREQUENCY = 3;

    // ==================== Redis configuration ====================

    /**
     * Redis scanbatchtimesize.
     */
    public const int REDIS_SCAN_BATCH_SIZE = 200;

    /**
     * Redis scanmostbigquantity.
     */
    public const int REDIS_SCAN_MAX_COUNT = 2000;

    // ==================== scheduletaskconfiguration ====================

    /**
     * corejumpmonitorscheduletaskmutually exclusivelockexpiretime(second).
     */
    public const int HEARTBEAT_MONITOR_MUTEX_EXPIRES = 60;
}
