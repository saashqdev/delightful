<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Asr\Constants;

/**
 * ASR pathconstantquantity
 * systemonemanage ASR relatedclosedirectoryandfilepath.
 */
class AsrPaths
{
    /**
     * workregiondirectoryname.
     */
    public const WORKSPACE_DIR = '.workspace';

    /**
     * hiddenrecordingdirectoryfrontsuffix.
     */
    public const HIDDEN_DIR_PREFIX = '.asr_recordings';

    /**
     * hiddenstatusdirectoryname.
     */
    public const STATES_DIR = '.asr_states';

    /**
     * generatehiddendirectoryrelatedtopath.
     *
     * @param string $taskKey taskkey
     * @return string format:.asr_recordings/{task_key}
     */
    public static function getHiddenDirPath(string $taskKey): string
    {
        return sprintf('%s/%s', self::HIDDEN_DIR_PREFIX, $taskKey);
    }

    /**
     * getstatusdirectoryrelatedtopath.
     *
     * @return string format:.asr_states
     */
    public static function getStatesDirPath(): string
    {
        return self::STATES_DIR;
    }

    /**
     * getrecordingdirectoryrelatedtopath(parentdirectory).
     *
     * @return string format:.asr_recordings
     */
    public static function getRecordingsDirPath(): string
    {
        return self::HIDDEN_DIR_PREFIX;
    }
}
