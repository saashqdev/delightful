<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\Volcengine\ValueObject;

/**
 * Volcanoenginevoiceidentifystatuscodeenum.
 */
enum VolcengineStatusCode: string
{
    /**
     * success - responsebodycontaintranscriptionresult.
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
     * muteaudio - noneed重newquery,directly重newsubmit.
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
     * servicedevice busy.
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
     * getstatuscodedescriptioninfo.
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
            self::SERVER_BUSY => 'servicedevice busy',
        };
    }

    /**
     * according tostatuscodestringcreateenuminstance.
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
