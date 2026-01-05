<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Speech\Enum;

/**
 * ASR directory type enum.
 */
enum AsrDirectoryTypeEnum: string
{
    case ASR_HIDDEN_DIR = 'asr_hidden_dir';       // Hidden directory (stores shards; actual path under ASR_RECORDINGS_DIR/task_key)
    case ASR_DISPLAY_DIR = 'asr_display_dir';     // Display directory (stores streaming text and notes)
    case ASR_STATES_DIR = 'asr_states_dir';       // State directory (stores frontend recording states)
    case ASR_RECORDINGS_DIR = 'asr_recordings_dir'; // Recording directory (.asr_recordings)
}
