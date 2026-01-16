<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Dtyq\BeDelightful\Interfaces\SuperAgent\Assembler;

use Delightful\BeDelightful\Domain\SuperAgent\Event\TopicTaskMessageEvent;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\TopicTaskMessageDTO;

/**
 * 话题任务消息装配器.
 */
class TopicTaskMessageAssembler
{
    /**
     * 将DTO转换为领域事件.
     */
    public static function toEvent(TopicTaskMessageDTO $dto): TopicTaskMessageEvent
    {
        return new TopicTaskMessageEvent($dto->getMetadata(), $dto->getPayload(), $dto->getTokenUsageDetails());
    }

    /**
     * 将领域事件转换为DTO.
     */
    public static function toDTO(TopicTaskMessageEvent $event): TopicTaskMessageDTO
    {
        return new TopicTaskMessageDTO(
            $event->getMetadata(),
            $event->getPayload(),
            $event->getTokenUsageDetails(),
        );
    }
}
