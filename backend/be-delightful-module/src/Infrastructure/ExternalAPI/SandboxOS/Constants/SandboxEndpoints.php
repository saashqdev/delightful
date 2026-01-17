<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Constants;

/**
 * Sandbox API endpoint constants
 * Centrally manage sandbox service API paths.
 */
class SandboxEndpoints
{
    /**
     * ASR task start endpoint.
     */
    public const ASR_TASK_START = 'api/asr/task/start';

    /**
     * ASR task finish endpoint.
     */
    public const ASR_TASK_FINISH = 'api/asr/task/finish';

    /**
     * ASR task cancel endpoint.
     */
    public const ASR_TASK_CANCEL = 'api/asr/task/cancel';

    /**
     * Agent message chat endpoint.
     */
    public const AGENT_MESSAGES_CHAT = 'api/v1/messages/chat';
}
