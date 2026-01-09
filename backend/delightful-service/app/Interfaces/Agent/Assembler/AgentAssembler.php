<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Agent\Assembler;

use App\Domain\Agent\Entity\DelightfulAgentEntity;
use App\Infrastructure\Core\ValueObject\Page;
use App\Interfaces\Agent\DTO\AvailableAgentDTO;
use App\Interfaces\Kernel\Assembler\FileAssembler;
use App\Interfaces\Kernel\DTO\PageDTO;
use Delightful\CloudFile\Kernel\Struct\FileLink;

class AgentAssembler
{
    /**
     * @param array<string, FileLink> $icons
     */
    public static function createAvailableAgentDTO(DelightfulAgentEntity $agentEntity, array $icons = []): AvailableAgentDTO
    {
        $dto = new AvailableAgentDTO();
        $dto->setId($agentEntity->getId());
        $dto->setName($agentEntity->getAgentName());
        $dto->setAvatar(FileAssembler::getUrl($icons[$agentEntity->getAgentAvatar()] ?? null));
        $dto->setDescription($agentEntity->getAgentDescription());
        $dto->setCreatedAt($agentEntity->getCreatedAt());
        return $dto;
    }

    public static function createAvailableList(Page $page, int $total, array $list, array $icons = []): PageDTO
    {
        $list = array_map(fn (DelightfulAgentEntity $entity) => self::createAvailableAgentDTO($entity, $icons), $list);
        return new PageDTO($page->getPage(), $total, $list);
    }

    public static function createChatModelAvailableList(Page $page, int $total, array $list, array $icons = []): PageDTO
    {
        // 处理pagination
        $offset = ($page->getPage() - 1) * $page->getPageNum();
        $pagedList = array_slice($list, $offset, $page->getPageNum());

        // 直接returnarray数据，因为已经包含conversationID等info
        return new PageDTO($page->getPage(), $total, $pagedList);
    }
}
