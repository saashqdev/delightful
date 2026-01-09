<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\Volcengine\ValueObject;

/**
 * 火山enginevoice识别status码枚举.
 */
enum VolcengineStatusCode: string
{
    /**
     * success - responsebodycontain转录result.
     */
    case SUCCESS = '20000000';

    /**
     * 正在process中 - responsebody为空.
     */
    case PROCESSING = '20000001';

    /**
     * task在queue中 - responsebody为空.
     */
    case QUEUED = '20000002';

    /**
     * 静音audio - 无需重新query，直接重新submit.
     */
    case SILENT_AUDIO = '20000003';

    /**
     * requestparameterinvalid.
     */
    case INVALID_PARAMS = '45000001';

    /**
     * 空audio.
     */
    case EMPTY_AUDIO = '45000002';

    /**
     * audioformat不correct.
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
     * 判断是否为process中status（includeprocess中和排队中）.
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
     * 判断是否为可retry的failstatus
     */
    public function isRetryable(): bool
    {
        return in_array($this, [self::SERVER_BUSY]);
    }

    /**
     * 判断是否need重新submittask
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
            self::PROCESSING => '正在process中',
            self::QUEUED => 'task在queue中',
            self::SILENT_AUDIO => '静音audio',
            self::INVALID_PARAMS => 'requestparameterinvalid',
            self::EMPTY_AUDIO => '空audio',
            self::INVALID_AUDIO_FORMAT => 'audioformat不correct',
            self::SERVER_BUSY => 'service器繁忙',
        };
    }

    /**
     * according tostatus码stringcreate枚举实例.
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
