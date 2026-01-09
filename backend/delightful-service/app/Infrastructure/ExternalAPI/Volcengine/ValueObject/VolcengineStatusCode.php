<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\Volcengine\ValueObject;

/**
 * 火山enginevoiceidentifystatus码枚举.
 */
enum VolcengineStatusCode: string
{
    /**
     * success - responsebodycontain转录result.
     */
    case SUCCESS = '20000000';

    /**
     * justinprocessmiddle - responsebodyforempty.
     */
    case PROCESSING = '20000001';

    /**
     * taskinqueuemiddle - responsebodyforempty.
     */
    case QUEUED = '20000002';

    /**
     * muteaudio - no需重newquery,directly重newsubmit.
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
     * judgewhetherforsuccessstatus
     */
    public function isSuccess(): bool
    {
        return $this === self::SUCCESS;
    }

    /**
     * judgewhetherforprocessmiddlestatus(includeprocessmiddleandrow队middle).
     */
    public function isProcessing(): bool
    {
        return in_array($this, [self::PROCESSING, self::QUEUED]);
    }

    /**
     * judgewhetherforfailstatus
     */
    public function isFailed(): bool
    {
        return ! $this->isSuccess() && ! $this->isProcessing();
    }

    /**
     * judgewhetherforcanretryfailstatus
     */
    public function isRetryable(): bool
    {
        return in_array($this, [self::SERVER_BUSY]);
    }

    /**
     * judgewhetherneed重newsubmittask
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
            self::SUCCESS => 'identifysuccess',
            self::PROCESSING => 'justinprocessmiddle',
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
     * judgewhetherforserviceinside部error(550xxxx系column).
     */
    public static function isInternalServerError(string $statusCode): bool
    {
        return str_starts_with($statusCode, '550');
    }
}
