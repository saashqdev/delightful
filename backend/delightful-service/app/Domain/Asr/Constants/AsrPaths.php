<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Asr\Constants;

/**
 * ASR 路径常量
 * 统一管理 ASR 相关的目录和file路径.
 */
class AsrPaths
{
    /**
     * 工作区目录名.
     */
    public const WORKSPACE_DIR = '.workspace';

    /**
     * 隐藏录音目录前缀.
     */
    public const HIDDEN_DIR_PREFIX = '.asr_recordings';

    /**
     * 隐藏status目录名称.
     */
    public const STATES_DIR = '.asr_states';

    /**
     * generate隐藏目录相对路径.
     *
     * @param string $taskKey task键
     * @return string format：.asr_recordings/{task_key}
     */
    public static function getHiddenDirPath(string $taskKey): string
    {
        return sprintf('%s/%s', self::HIDDEN_DIR_PREFIX, $taskKey);
    }

    /**
     * getstatus目录相对路径.
     *
     * @return string format：.asr_states
     */
    public static function getStatesDirPath(): string
    {
        return self::STATES_DIR;
    }

    /**
     * get录音目录相对路径（父目录）.
     *
     * @return string format：.asr_recordings
     */
    public static function getRecordingsDirPath(): string
    {
        return self::HIDDEN_DIR_PREFIX;
    }
}
