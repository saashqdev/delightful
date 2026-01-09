<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\DTO\Message\Common\MessageExtra\BeAgent\Mention;

use App\Domain\Chat\DTO\Message\Common\MessageExtra\BeAgent\Mention\Agent\AgentData;
use App\Domain\Chat\DTO\Message\Common\MessageExtra\BeAgent\Mention\Directory\DirectoryData;
use App\Domain\Chat\DTO\Message\Common\MessageExtra\BeAgent\Mention\File\FileData;
use App\Domain\Chat\DTO\Message\Common\MessageExtra\BeAgent\Mention\Mcp\McpData;
use App\Domain\Chat\DTO\Message\Common\MessageExtra\BeAgent\Mention\Tool\ToolData;
use App\Infrastructure\Core\AbstractDTO;

final class MentionAttrs extends AbstractDTO
{
    protected MentionType $type;

    protected MentionDataInterface $data;

    public function __construct(?array $data = null)
    {
        // need先setting type
        $this->setType($data['type'] ?? '');
        parent::__construct($data);
    }

    public function getType(): MentionType
    {
        return $this->type;
    }

    public function setType(MentionType|string $type): void
    {
        if ($type instanceof MentionType) {
            $this->type = $type;
        } else {
            $this->type = MentionType::from($type);
        }
    }

    public function getData(): MentionDataInterface
    {
        return $this->data;
    }

    public function setData(array|MentionDataInterface $data): void
    {
        if ($data instanceof MentionDataInterface) {
            $this->data = $data;
        } else {
            $this->data = match ($this->getType()) {
                MentionType::PROJECT_FILE, MentionType::UPLOAD_FILE => new FileData($data),
                MentionType::AGENT => new AgentData($data),
                MentionType::MCP => new McpData($data),
                MentionType::TOOL => new ToolData($data),
                MentionType::PROJECT_DIRECTORY => new DirectoryData($data),
            };
        }
    }
}
