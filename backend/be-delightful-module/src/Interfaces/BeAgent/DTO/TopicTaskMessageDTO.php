<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\DTO;

use Delightful\BeDelightful\Domain\SuperAgent\Entity\TaskMessageEntity;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\MessageMetadata;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\MessagePayload;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\TokenUsageDetails;
/** * topic TaskMessageDTO. */

class TopicTaskMessageDTO 
{
 /** * Function. * * @param MessageMetadata $metadata MessageData * @param MessagePayload $payload Message * @param null|TokenUsageDetails $tokenUsageDetails Token UsingDetails */ 
    public function __construct( 
    private MessageMetadata $metadata, 
    private MessagePayload $payload, private ?TokenUsageDetails $tokenUsageDetails = null ) 
{
 
}
 /** * FromMessageDataCreateDTOInstance. * * @param array $data MessageData */ 
    public 
    static function fromArray(array $data): self 
{
 $metadata = isset($data['metadata']) && is_array($data['metadata']) ? MessageMetadata::fromArray($data['metadata']) : new MessageMetadata(); $payload = isset($data['payload']) && is_array($data['payload']) ? MessagePayload::fromArray($data['payload']) : new MessagePayload(); $tokenUsageDetails = isset($data['token_usage_details']) && is_array($data['token_usage_details']) ? TokenUsageDetails::fromArray($data['token_usage_details']) : null; return new self($metadata, $payload, $tokenUsageDetails); 
}
 /** * GetMessageData. */ 
    public function getMetadata(): MessageMetadata 
{
 return $this->metadata; 
}
 /** * Set MessageData. * * @param MessageMetadata $metadata MessageData */ 
    public function setMetadata(MessageMetadata $metadata): self 
{
 $this->metadata = $metadata; return $this; 
}
 /** * GetMessage. */ 
    public function getPayload(): MessagePayload 
{
 return $this->payload; 
}
 /** * Set Message. * * @param MessagePayload $payload Message */ 
    public function setPayload(MessagePayload $payload): self 
{
 $this->payload = $payload; return $this; 
}
 /** * Get Token UsingDetails. */ 
    public function getTokenUsageDetails(): ?TokenUsageDetails 
{
 return $this->tokenUsageDetails; 
}
 /** * Set Token UsingDetails. * * @param null|TokenUsageDetails $tokenUsageDetails Token UsingDetails */ 
    public function setTokenUsageDetails(?TokenUsageDetails $tokenUsageDetails): self 
{
 $this->tokenUsageDetails = $tokenUsageDetails; return $this; 
}
 /** * Convert toArray. */ 
    public function toArray(): array 
{
 return [ 'metadata' => $this->metadata->toArray(), 'payload' => $this->payload->toArray(), 'token_usage_details' => $this->tokenUsageDetails?->toArray(), ]; 
}
 /** * Convert toTaskMessageEntity. * * @param int $topicId topic ID * @return TaskMessageEntity TaskMessage */ 
    public function toTaskMessageEntity(int $topicId, string $senderUid, string $receiverUid): TaskMessageEntity 
{
 $messageData = [ 'sender_type' => 'assistant', 'sender_uid' => $senderUid, 'receiver_uid' => $receiverUid, 'message_id' => $this->payload->getMessageId() ?? '', 'type' => $this->payload->getType() ?? '', 'task_id' => $this->payload->getTaskId() ?? '', 'topic_id' => $topicId, 'status' => $this->payload->getStatus() ?? 'pending', 'content' => $this->payload->getContent() ?? '', 'raw_content' => '', 'steps' => $this->payload->getSteps() ?? null, 'tool' => $this->payload->gettool () ?? null, 'attachments' => $this->payload->getAttachments() ?? null, 'mentions' => null, 'event' => $this->payload->getEvent() ?? '', 'send_timestamp' => $this->payload->getSendTimestamp() ?? time(), 'show_in_ui' => $this->payload->getShowInUi() ?? true, 'seq_id' => $this->payload->getSeqId() ?? 0, 'correlation_id' => $this->payload->getCorrelationId(), ]; return new TaskMessageEntity($messageData); 
}
 
}
 
