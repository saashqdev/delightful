<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Asr\Constants;

/**
 * ASR path常量
 * 统一管理 ASR 相关的directory和filepath.
 */
class AsrPaths
{
    /**
     * 工作区directory名.
     */
    public const WORKSPACE_DIR = '.workspace';

    /**
     * 隐藏录音directory前缀.
     */
    public const HIDDEN_DIR_PREFIX = '.asr_recordings';

    /**
     * 隐藏statusdirectoryname.
     */
    public const STATES_DIR = '.asr_states';

    /**
     * generate隐藏directory相对path.
     *
     * @param string $taskKey task键
     * @return string format：.asr_recordings/{task_key}
     */
    public static function getHiddenDirPath(string $taskKey): string
    {
        return sprintf('%s/%s', self::HIDDEN_DIR_PREFIX, $taskKey);
    }

    /**
     * getstatusdirectory相对path.
     *
     * @return string format：.asr_states
     */
    public static function getStatesDirPath(): string
    {
        return self::STATES_DIR;
    }

    /**
     * get录音directory相对path（父directory）.
     *
     * @return string format：.asr_recordings
     */
    public static function getRecordingsDirPath(): string
    {
        return self::HIDDEN_DIR_PREFIX;
    }
}
