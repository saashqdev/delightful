<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\ErrorCode;

use App\Infrastructure\Core\Exception\Annotation\ErrorMessage;

/**
 * range:8000, 8999.
 */
enum UserTaskErrorCode: int
{
    // parameternotlegal notlegal
    #[ErrorMessage('task.invalid')]
    case PARAMETER_INVALID = 8001;

    // tasknotexistsin
    #[ErrorMessage('task.not_found')]
    case TASK_NOT_FOUND = 8002;

    // taskalreadyexistsin
    #[ErrorMessage('task.already_exists')]
    case TASK_ALREADY_EXISTS = 8003;

    // taskcreatefailed
    #[ErrorMessage('task.create_failed')]
    case TASK_CREATE_FAILED = 8004;

    // taskupdatefailed
    #[ErrorMessage('task.update_failed')]
    case TASK_UPDATE_FAILED = 8005;

    // taskdeletefailed
    #[ErrorMessage('task.delete_failed')]
    case TASK_DELETE_FAILED = 8006;

    // taskcolumntablegetfailed
    #[ErrorMessage('task.list_failed')]
    case TASK_LIST_FAILED = 8007;

    // taskgetfailed
    #[ErrorMessage('task.get_failed')]
    case TASK_GET_FAILED = 8008;

    // agentId cannotfornull
    #[ErrorMessage('task.agent_id_required')]
    case AGENT_ID_REQUIRED = 8009;

    // topicId cannotfornull
    #[ErrorMessage('task.topic_id_required')]
    case TOPIC_ID_REQUIRED = 8010;
}
