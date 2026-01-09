<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Speech\Enum;

/**
 * ASR taskstatus枚举（内部业务流程）.
 *
 * 【作用域】内部系统 - delightful-service 业务层
 * 【用途】table示 ASR 录音总结task的全生命周期status
 * 【use场景】
 * - taskstatus持久化（Redis/database）
 * - 业务流程控制和幂等性判断
 * - 整体taskstatus追踪（录音 → 合并 → 生成标题 → 发送message）
 *
 * 【与其他枚举的区别】
 * - AsrRecordingStatusEnum: 前端录音实时status（录音交互层）
 * - AsrTaskStatusEnum: 内部task全流程status（业务管理层）✓ 当前
 * - SandboxAsrStatusEnum: 沙箱合并taskstatus（基础设施层）
 *
 * 【status流转】created → processing → completed | failed
 */
enum AsrTaskStatusEnum: string
{
    case CREATED = 'created';              // 已create：taskinitialize完成，等待处理
    case PROCESSING = 'processing';        // 处理中：正在执行录音、合并或总结
    case COMPLETED = 'completed';          // 已完成：整个 ASR 流程全部完成（includemessage发送）
    case FAILED = 'failed';                // fail：task执行fail

    /**
     * getstatusdescription.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::CREATED => '已create',
            self::PROCESSING => '处理中',
            self::COMPLETED => '已完成',
            self::FAILED => 'fail',
        };
    }

    /**
     * check是否为successstatus
     */
    public function isSuccess(): bool
    {
        return $this === self::COMPLETED;
    }

    /**
     * 从stringcreate枚举.
     */
    public static function fromString(string $status): self
    {
        return self::tryFrom($status) ?? self::FAILED;
    }
}
