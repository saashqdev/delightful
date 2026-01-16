<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\Assembler;

use Delightful\BeDelightful\Domain\SuperAgent\Event\TopicTaskMessageEvent;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\TopicTaskMessageDTO;
/** * topic TaskMessage. */

class TopicTaskMessageAssembler 
{
 /** * DTOConvert toEvent. */ 
    public 
    static function toEvent(TopicTaskMessageDTO $dto): TopicTaskMessageEvent 
{
 return new TopicTaskMessageEvent($dto->getMetadata(), $dto->getPayload(), $dto->getTokenUsageDetails()); 
}
 /** * EventConvert toDTO. */ 
    public 
    static function toDTO(TopicTaskMessageEvent $event): TopicTaskMessageDTO 
{
 return new TopicTaskMessageDTO( $event->getMetadata(), $event->getPayload(), $event->getTokenUsageDetails(), ); 
}
 
}
 
