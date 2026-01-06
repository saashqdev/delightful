<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Agent\Assembler;

use App\Domain\Chat\DTO\Message\Common\MessageExtra\BeAgent\Mention\Agent\AgentMention;
use App\Domain\Chat\DTO\Message\Common\MessageExtra\BeAgent\Mention\Directory\DirectoryMention;
use App\Domain\Chat\DTO\Message\Common\MessageExtra\BeAgent\Mention\File\ProjectFileMention;
use App\Domain\Chat\DTO\Message\Common\MessageExtra\BeAgent\Mention\File\UploadFileMention;
use App\Domain\Chat\DTO\Message\Common\MessageExtra\BeAgent\Mention\Mcp\McpMention;
use App\Domain\Chat\DTO\Message\Common\MessageExtra\BeAgent\Mention\MentionInterface;
use App\Domain\Chat\DTO\Message\Common\MessageExtra\BeAgent\Mention\MentionType;
use App\Domain\Chat\DTO\Message\Common\MessageExtra\BeAgent\Mention\Tool\ToolMention;

final class MentionAssembler
{
    public static function fromArray(array $mention): ?MentionInterface
    {
        if (! isset($mention['attrs']['type'])) {
            return null;
        }

        $mentionAttrType = MentionType::tryFrom($mention['attrs']['type']);

        return match ($mentionAttrType) {
            MentionType::PROJECT_FILE => new ProjectFileMention($mention),
            MentionType::PROJECT_DIRECTORY => new DirectoryMention($mention),
            MentionType::UPLOAD_FILE => new UploadFileMention($mention),
            MentionType::AGENT => new AgentMention($mention),
            MentionType::MCP => new McpMention($mention),
            MentionType::TOOL => new ToolMention($mention),
            default => null,
        };
    }
}
