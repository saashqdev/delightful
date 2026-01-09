<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\Volcengine\ValueObject;

/**
 * 火山引擎语音识别status码枚举.
 */
enum VolcengineStatusCode: string
{
    /**
     * success - 响应body包含转录result.
     */
    case SUCCESS = '20000000';

    /**
     * 正在处理中 - 响应body为空.
     */
    case PROCESSING = '20000001';

    /**
     * task在队列中 - 响应body为空.
     */
    case QUEUED = '20000002';

    /**
     * 静音音频 - 无需重新query，直接重新submit.
     */
    case SILENT_AUDIO = '20000003';

    /**
     * 请求parameter无效.
     */
    case INVALID_PARAMS = '45000001';

    /**
     * 空音频.
     */
    case EMPTY_AUDIO = '45000002';

    /**
     * 音频格式不正确.
     */
    case INVALID_AUDIO_FORMAT = '45000151';

    /**
     * service器繁忙.
     */
    case SERVER_BUSY = '55000031';

    /**
     * 判断是否为successstatus
     */
    public function isSuccess(): bool
    {
        return $this === self::SUCCESS;
    }

    /**
     * 判断是否为处理中status（包括处理中和排队中）.
     */
    public function isProcessing(): bool
    {
        return in_array($this, [self::PROCESSING, self::QUEUED]);
    }

    /**
     * 判断是否为failstatus
     */
    public function isFailed(): bool
    {
        return ! $this->isSuccess() && ! $this->isProcessing();
    }

    /**
     * 判断是否为可重试的failstatus
     */
    public function isRetryable(): bool
    {
        return in_array($this, [self::SERVER_BUSY]);
    }

    /**
     * 判断是否需要重新提交task
     */
    public function needsResubmit(): bool
    {
        return $this === self::SILENT_AUDIO;
    }

    /**
     * getstatus码的descriptioninfo.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::SUCCESS => '识别success',
            self::PROCESSING => '正在处理中',
            self::QUEUED => 'task在队列中',
            self::SILENT_AUDIO => '静音音频',
            self::INVALID_PARAMS => '请求parameter无效',
            self::EMPTY_AUDIO => '空音频',
            self::INVALID_AUDIO_FORMAT => '音频格式不正确',
            self::SERVER_BUSY => 'service器繁忙',
        };
    }

    /**
     * 根据status码stringcreate枚举实例.
     */
    public static function fromString(string $statusCode): ?self
    {
        return self::tryFrom($statusCode);
    }

    /**
     * 判断是否为service内部error（550xxxx系列）.
     */
    public static function isInternalServerError(string $statusCode): bool
    {
        return str_starts_with($statusCode, '550');
    }
}
