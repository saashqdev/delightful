<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Speech\Enum;

/**
 * ASR taskstatus枚举（inside部businessprocess）.
 *
 * 【asuse域】inside部system - delightful-service businesslayer
 * 【use途】table示 ASR 录音总结taskall生命periodstatus
 * 【use场景】
 * - taskstatus持久化（Redis/database）
 * - businessprocesscontrolandpoweretcpropertyjudge
 * - 整bodytaskstatustrace（录音 → merge → generatetitle → sendmessage）
 *
 * 【andother枚举区别】
 * - AsrRecordingStatusEnum: front端录音实o clockstatus（录音交互layer）
 * - AsrTaskStatusEnum: inside部taskallprocessstatus（businessmanagelayer）✓ current
 * - SandboxAsrStatusEnum: 沙箱mergetaskstatus（基础设施layer）
 *
 * 【statusstream转】created → processing → completed | failed
 */
enum AsrTaskStatusEnum: string
{
    case CREATED = 'created';              // alreadycreate：taskinitializecomplete，etc待process
    case PROCESSING = 'processing';        // processmiddle：justinexecute录音、mergeor总结
    case COMPLETED = 'completed';          // alreadycomplete：整 ASR processall部complete（includemessagesend）
    case FAILED = 'failed';                // fail：taskexecutefail

    /**
     * getstatusdescription.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::CREATED => 'alreadycreate',
            self::PROCESSING => 'processmiddle',
            self::COMPLETED => 'alreadycomplete',
            self::FAILED => 'fail',
        };
    }

    /**
     * checkwhetherforsuccessstatus
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
