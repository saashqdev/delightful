<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Event;

use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\MessageMetadata;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\MessagePayload;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\TokenUsageDetails;
/** * topic TaskMessageEvent. */

class TopicTaskMessageEvent extends AbstractEvent 
{
 /** * Function. * * @param MessageMetadata $metadata MessageData * @param MessagePayload $payload Message * @param null|TokenUsageDetails $tokenUsageDetails Token UsingDetails */ 
    public function __construct( 
    private MessageMetadata $metadata, 
    private MessagePayload $payload, private ?TokenUsageDetails $tokenUsageDetails = null, ) 
{
 // Call parent constructor to generate snowflake ID parent::__construct(); 
}
 /** * FromArrayCreateMessageEvent. * * @param array $data MessageDataArray */ 
    public 
    static function fromArray(array $data): self 
{
 $metadata = isset($data['metadata']) && is_array($data['metadata']) ? MessageMetadata::fromArray($data['metadata']) : new MessageMetadata(); $payload = isset($data['payload']) && is_array($data['payload']) ? MessagePayload::fromArray($data['payload']) : new MessagePayload(); $tokenUsageDetails = isset($data['token_usage_details']) && is_array($data['token_usage_details']) ? TokenUsageDetails::fromArray($data['token_usage_details']) : null; return new self($metadata, $payload, $tokenUsageDetails); 
}
 /** * Convert toArray. * * @return array MessageDataArray */ 
    public function toArray(): array 
{
 return [ 'metadata' => $this->metadata->toArray(), 'payload' => $this->payload->toArray(), 'token_usage_details' => $this->tokenUsageDetails?->toArray(), ]; 
}
 /** * GetMessageData. */ 
    public function getMetadata(): MessageMetadata 
{
 return $this->metadata; 
}
 /** * GetMessage. */ 
    public function getPayload(): MessagePayload 
{
 return $this->payload; 
}
 
    public function getTokenUsageDetails(): ?TokenUsageDetails 
{
 return $this->tokenUsageDetails; 
}
 
}
 
