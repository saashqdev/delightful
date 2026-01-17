<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Interfaces\BeAgent\Assembler;

use Delightful\BeDelightful\Domain\BeAgent\Event\TopicTaskMessageEvent;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\TopicTaskMessageDTO;

/**
 * Topic task message assembler.
 */
class TopicTaskMessageAssembler
{
    /**
     * Convert DTO to domain event.
     */
    public static function toEvent(TopicTaskMessageDTO $dto): TopicTaskMessageEvent
    {
        return new TopicTaskMessageEvent($dto->getMetadata(), $dto->getPayload(), $dto->getTokenUsageDetails());
    }

    /**
     * Convert domain event to DTO.
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
