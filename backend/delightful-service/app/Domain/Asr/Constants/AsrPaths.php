<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Asr\Constants;

/**
 * ASR path常quantity
 * 统onemanage ASR 相closedirectoryandfilepath.
 */
class AsrPaths
{
    /**
     * work区directory名.
     */
    public const WORKSPACE_DIR = '.workspace';

    /**
     * hidden录音directoryfront缀.
     */
    public const HIDDEN_DIR_PREFIX = '.asr_recordings';

    /**
     * hiddenstatusdirectoryname.
     */
    public const STATES_DIR = '.asr_states';

    /**
     * generatehiddendirectory相topath.
     *
     * @param string $taskKey taskkey
     * @return string format:.asr_recordings/{task_key}
     */
    public static function getHiddenDirPath(string $taskKey): string
    {
        return sprintf('%s/%s', self::HIDDEN_DIR_PREFIX, $taskKey);
    }

    /**
     * getstatusdirectory相topath.
     *
     * @return string format:.asr_states
     */
    public static function getStatesDirPath(): string
    {
        return self::STATES_DIR;
    }

    /**
     * get录音directory相topath(父directory).
     *
     * @return string format:.asr_recordings
     */
    public static function getRecordingsDirPath(): string
    {
        return self::HIDDEN_DIR_PREFIX;
    }
}
