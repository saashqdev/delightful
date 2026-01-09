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
     * 正inprocessmiddle - responsebodyforempty.
     */
    case PROCESSING = '20000001';

    /**
     * taskinqueuemiddle - responsebodyforempty.
     */
    case QUEUED = '20000002';

    /**
     * muteaudio - no需重新query，直接重新submit.
     */
    case SILENT_AUDIO = '20000003';

    /**
     * requestparameterinvalid.
     */
    case INVALID_PARAMS = '45000001';

    /**
     * emptyaudio.
     */
    case EMPTY_AUDIO = '45000002';

    /**
     * audioformatnotcorrect.
     */
    case INVALID_AUDIO_FORMAT = '45000151';

    /**
     * service器繁忙.
     */
    case SERVER_BUSY = '55000031';

    /**
     * 判断whetherforsuccessstatus
     */
    public function isSuccess(): bool
    {
        return $this === self::SUCCESS;
    }

    /**
     * 判断whetherforprocessmiddlestatus（includeprocessmiddleandrow队middle）.
     */
    public function isProcessing(): bool
    {
        return in_array($this, [self::PROCESSING, self::QUEUED]);
    }

    /**
     * 判断whetherforfailstatus
     */
    public function isFailed(): bool
    {
        return ! $this->isSuccess() && ! $this->isProcessing();
    }

    /**
     * 判断whetherforcanretryfailstatus
     */
    public function isRetryable(): bool
    {
        return in_array($this, [self::SERVER_BUSY]);
    }

    /**
     * 判断whetherneed重新submittask
     */
    public function needsResubmit(): bool
    {
        return $this === self::SILENT_AUDIO;
    }

    /**
     * getstatus码descriptioninfo.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::SUCCESS => '识别success',
            self::PROCESSING => '正inprocessmiddle',
            self::QUEUED => 'taskinqueuemiddle',
            self::SILENT_AUDIO => 'muteaudio',
            self::INVALID_PARAMS => 'requestparameterinvalid',
            self::EMPTY_AUDIO => 'emptyaudio',
            self::INVALID_AUDIO_FORMAT => 'audioformatnotcorrect',
            self::SERVER_BUSY => 'service器繁忙',
        };
    }

    /**
     * according tostatus码stringcreate枚举instance.
     */
    public static function fromString(string $statusCode): ?self
    {
        return self::tryFrom($statusCode);
    }

    /**
     * 判断whetherforserviceinside部error（550xxxx系column）.
     */
    public static function isInternalServerError(string $statusCode): bool
    {
        return str_starts_with($statusCode, '550');
    }
}
