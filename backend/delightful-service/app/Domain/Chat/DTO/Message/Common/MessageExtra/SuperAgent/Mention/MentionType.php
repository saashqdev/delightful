<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\DTO\Message\Common\MessageExtra\BeAgent\Mention;

enum MentionType: string
{
    case PROJECT_DIRECTORY = 'project_directory';
    case PROJECT_FILE = 'project_file';

    /**
     * 本timemessagemiddletemporaryuploadfile，back续will统oneto project_file middle.
     */
    case UPLOAD_FILE = 'upload_file';

    case AGENT = 'agent';
    case MCP = 'mcp';
    case TOOL = 'tool';
}
