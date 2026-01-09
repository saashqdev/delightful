<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Speech\Enum;

/**
 * ASR taskstatus枚举（inside部业务process）.
 *
 * 【作use域】inside部system - delightful-service 业务layer
 * 【use途】table示 ASR 录音总结task的all生命periodstatus
 * 【use场景】
 * - taskstatus持久化（Redis/database）
 * - 业务process控制和幂etcproperty判断
 * - 整bodytaskstatustrace（录音 → merge → generatetitle → sendmessage）
 *
 * 【与其他枚举的区别】
 * - AsrRecordingStatusEnum: front端录音实o clockstatus（录音交互layer）
 * - AsrTaskStatusEnum: inside部taskallprocessstatus（业务管理layer）✓ current
 * - SandboxAsrStatusEnum: 沙箱mergetaskstatus（基础设施layer）
 *
 * 【statusstream转】created → processing → completed | failed
 */
enum AsrTaskStatusEnum: string
{
    case CREATED = 'created';              // 已create：taskinitializecomplete，etc待process
    case PROCESSING = 'processing';        // processmiddle：正inexecute录音、mergeor总结
    case COMPLETED = 'completed';          // 已complete：整 ASR processall部complete（includemessagesend）
    case FAILED = 'failed';                // fail：taskexecutefail

    /**
     * getstatusdescription.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::CREATED => '已create',
            self::PROCESSING => 'processmiddle',
            self::COMPLETED => '已complete',
            self::FAILED => 'fail',
        };
    }

    /**
     * checkwhether为successstatus
     */
    public function isSuccess(): bool
    {
        return $this === self::COMPLETED;
    }

    /**
     * fromstringcreate枚举.
     */
    public static function fromString(string $status): self
    {
        return self::tryFrom($status) ?? self::FAILED;
    }
}
