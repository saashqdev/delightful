<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Speech\Enum;

/**
 * 沙箱 ASR taskstatus枚举.
 *
 * 【作用域】外部系统 - 沙箱音频合并service
 * 【用途】table示沙箱中音频合并task的执行status
 * 【use场景】
 * - call沙箱 finishTask 接口的轮询status判断
 * - 判断音频分片合并是否完成
 *
 * 【与其他枚举的区别】
 * - AsrRecordingStatusEnum: 前端录音实时status（录音交互层）
 * - AsrTaskStatusEnum: 内部task全流程status（业务管理层）
 * - SandboxAsrStatusEnum: 沙箱合并taskstatus（基础设施层）✓ 当前
 *
 * 【status流转】waiting → running → finalizing → completed/finished | error
 */
enum SandboxAsrStatusEnum: string
{
    case WAITING = 'waiting';           // 等待中：task已提交，等待沙箱处理
    case RUNNING = 'running';           // 运行中：沙箱正在处理音频分片
    case FINALIZING = 'finalizing';     // 正在执行最终合并：沙箱正在合并音频并处理笔记文件
    case COMPLETED = 'completed';       // task完成（V2 新格式）：音频合并和文件处理全部完成
    case FINISHED = 'finished';         // task完成（向后兼容旧格式）：保留用于兼容旧版本沙箱
    case ERROR = 'error';               // error：沙箱处理fail

    /**
     * getstatusdescription.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::WAITING => '等待中',
            self::RUNNING => '运行中',
            self::FINALIZING => '正在合并',
            self::COMPLETED => '已完成',
            self::FINISHED => '已完成（旧）',
            self::ERROR => 'error',
        };
    }

    /**
     * 是否为完成status（包含新旧两种格式）.
     */
    public function isCompleted(): bool
    {
        return $this === self::COMPLETED || $this === self::FINISHED;
    }

    /**
     * 是否为errorstatus.
     */
    public function isError(): bool
    {
        return $this === self::ERROR;
    }

    /**
     * 是否为中间status（需要继续轮询）.
     */
    public function isInProgress(): bool
    {
        return match ($this) {
            self::WAITING, self::RUNNING, self::FINALIZING => true,
            default => false,
        };
    }

    /**
     * 从string安全create枚举.
     */
    public static function fromString(string $status): ?self
    {
        return self::tryFrom($status);
    }
}
