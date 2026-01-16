<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Entity;

use App\Infrastructure\Core\AbstractEntity;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use Delightful\BeDelightful\Application\SuperAgent\DTO\TaskMessageDTO;
use Hyperf\Codec\Json;

class TaskMessageEntity extends AbstractEntity 
{
 // process StatusConstant 
    public 
    const PROCESSING_STATUS_PENDING = 'pending'; 
    public 
    const PROCESSING_STATUS_PROCESSING = 'processing'; 
    public 
    const PROCESSING_STATUS_COMPLETED = 'completed'; 
    public 
    const PROCESSING_STATUS_FAILED = 'failed'; /** * @var int MessageID */ 
    protected int $id = 0; /** * @var string SendType(user/ai) */ 
    protected string $senderType = ''; /** * @var string SendID */ 
    protected string $senderUid = ''; /** * @var string ReceiveID */ 
    protected string $receiverUid = ''; /** * @var string MessageID */ 
    protected string $messageId = ''; /** * @var string MessageType */ 
    protected string $type = ''; /** * @var string TaskID */ 
    protected string $taskId = ''; /** * @var null|int|string topic ID */ protected $topicId; /** * @var null|string TaskStatus */ protected ?string $status = null; /** * @var string MessageContent */ 
    protected string $content = ''; /** * @var null|string original MessageContent */ protected ?string $rawContent = null; /** * @var null|array info */ protected ?array $steps = null; /** * @var null|array tool call info */ protected ?array $tool = null; /** * @var null|array info */ protected ?array $attachments = null; /** * @var null|array info */ protected ?array $mentions = null; /** * @var string EventType */ 
    protected string $event = ''; /** * @var int SendTimestamp */ 
    protected int $sendTimestamp = 0; 
    protected bool $showInUi = true; /** * @var null|string original delivery MessageJSONData */ protected ?string $rawData = null; /** * @var null|int ColumnIDfor MessageSort */ protected ?int $seqId = null; /** * @var string Messageprocess Status */ 
    protected string $processingStatus = self::PROCESSING_STATUS_PENDING; /** * @var null|string process ing failedError message */ protected ?string $errorMessage = null; /** * @var int Retry */ 
    protected int $retryCount = 0; /** * @var null|string process complete Time */ protected ?string $processedAt = null; /** * @var null|int IM ColumnIDfor Messageorder */ protected ?int $imSeqId = null; /** * @var null|int IM Statusmagic_chat_sequencestable statusField */ protected ?int $imStatus = null; /** * @var null|string AssociationIDfor MessageAssociation */ protected ?string $correlationId = null; /** * @var null|array Usage information (only set when task is finished) */ protected ?array $usage = null; 
    public function __construct(array $data = []) 
{
 $this->id = IdGenerator::getSnowId(); $this->messageId = isset($data['message_id']) ? (string) $data['message_id'] : (string) IdGenerator::getSnowId(); $this->sendTimestamp = time(); parent::__construct($data); 
}
 
    public function getId(): int 
{
 return $this->id; 
}
 
    public function setId(int $id): self 
{
 $this->id = $id; return $this; 
}
 
    public function getSenderType(): string 
{
 return $this->senderType; 
}
 
    public function setSenderType(?string $senderType): self 
{
 $this->senderType = $senderType ?? ''; return $this; 
}
 
    public function getSenderUid(): string 
{
 return $this->senderUid; 
}
 
    public function setSenderUid(?string $senderUid): self 
{
 $this->senderUid = $senderUid ?? ''; return $this; 
}
 
    public function getReceiverUid(): string 
{
 return $this->receiverUid; 
}
 
    public function setReceiverUid(?string $receiverUid): self 
{
 $this->receiverUid = $receiverUid ?? ''; return $this; 
}
 
    public function setMessageId(string $messageId): self 
{
 $this->messageId = $messageId; return $this; 
}
 
    public function getMessageId(): string 
{
 return $this->messageId; 
}
 
    public function getType(): string 
{
 return $this->type; 
}
 
    public function setType(?string $type): self 
{
 $this->type = $type ?? ''; return $this; 
}
 
    public function getTaskId(): string 
{
 return $this->taskId; 
}
 
    public function setTaskId(?string $taskId): self 
{
 $this->taskId = $taskId ?? ''; return $this; 
}
 
    public function getTopicId() 
{
 return $this->topicId; 
}
 
    public function setTopicId($topicId): self 
{
 $this->topicId = $topicId; return $this; 
}
 
    public function getStatus(): ?string 
{
 return $this->status; 
}
 
    public function setStatus(?string $status): self 
{
 $this->status = $status; return $this; 
}
 
    public function getContent(): string 
{
 return $this->content; 
}
 
    public function setContent(?string $content): self 
{
 $this->content = $content ?? ''; return $this; 
}
 
    public function getRawContent(): string 
{
 return $this->rawContent ?? ''; 
}
 
    public function setRawContent(?string $rawContent): self 
{
 $this->rawContent = $rawContent; return $this; 
}
 
    public function getSteps(): ?array 
{
 return $this->steps; 
}
 
    public function setSteps(null|array|string $steps): self 
{
 if (is_string($steps)) 
{
 if (! empty($steps) && json_validate($steps)) 
{
 $steps = Json::decode($steps); 
}
 else 
{
 $steps = null; 
}
 
}
 $this->steps = empty($steps) ? null : $steps; return $this; 
}
 
    public function gettool (): ?array 
{
 return $this->tool; 
}
 
    public function settool (null|array|string $tool): self 
{
 if (is_string($tool)) 
{
 if (! empty($tool) && json_validate($tool)) 
{
 $tool = Json::decode($tool); 
}
 else 
{
 $tool = null; 
}
 
}
 $this->tool = empty($tool) ? null : $tool; return $this; 
}
 
    public function getAttachments(): ?array 
{
 return $this->attachments; 
}
 
    public function setAttachments(null|array|string $attachments): self 
{
 if (is_string($attachments)) 
{
 if (! empty($attachments) && json_validate($attachments)) 
{
 $attachments = Json::decode($attachments); 
}
 else 
{
 $attachments = null; 
}
 
}
 $this->attachments = empty($attachments) ? null : $attachments; return $this; 
}
 
    public function getMentions(): ?array 
{
 return $this->mentions; 
}
 
    public function setMentions(null|array|string $mentions): self 
{
 if (is_string($mentions)) 
{
 if (! empty($mentions) && json_validate($mentions)) 
{
 $mentions = Json::decode($mentions); 
}
 else 
{
 // Invalid JSON Stringor EmptyStringSet as null $mentions = null; 
}
 
}
 $this->mentions = empty($mentions) ? null : $mentions; return $this; 
}
 
    public function getEvent(): string 
{
 return $this->event; 
}
 
    public function setEvent(?string $event): self 
{
 $this->event = $event ?? ''; return $this; 
}
 
    public function getSendTimestamp(): int 
{
 return $this->sendTimestamp; 
}
 
    public function getShowInUi(): bool 
{
 return $this->showInUi; 
}
 
    public function setShowInUi(bool|int $showInUi): self 
{
 $this->showInUi = (bool) $showInUi; return $this; 
}
 
    public function getRawData(): ?string 
{
 return $this->rawData; 
}
 
    public function setRawData(?string $rawData): self 
{
 $this->rawData = $rawData; return $this; 
}
 
    public function getSeqId(): ?int 
{
 return $this->seqId; 
}
 
    public function setSeqId(null|int|string $seqId): self 
{
 $this->seqId = $seqId !== null ? (int) $seqId : null; return $this; 
}
 
    public function getprocess ingStatus(): string 
{
 return $this->processingStatus; 
}
 
    public function setprocess ingStatus(string $processingStatus): self 
{
 $this->processingStatus = $processingStatus; return $this; 
}
 
    public function getErrorMessage(): ?string 
{
 return $this->errorMessage; 
}
 
    public function setErrorMessage(?string $errorMessage): self 
{
 $this->errorMessage = $errorMessage; return $this; 
}
 
    public function getRetryCount(): int 
{
 return $this->retryCount; 
}
 
    public function setRetryCount(int|string $retryCount): self 
{
 $this->retryCount = (int) $retryCount; return $this; 
}
 
    public function getprocess edAt(): ?string 
{
 return $this->processedAt; 
}
 
    public function setprocess edAt(?string $processedAt): self 
{
 $this->processedAt = $processedAt; return $this; 
}
 
    public function getImSeqId(): ?int 
{
 return $this->imSeqId; 
}
 
    public function setImSeqId(null|int|string $imSeqId): self 
{
 $this->imSeqId = $imSeqId !== null ? (int) $imSeqId : null; return $this; 
}
 
    public function getImStatus(): ?int 
{
 return $this->imStatus; 
}
 
    public function setImStatus(null|int|string $imStatus): self 
{
 $this->imStatus = $imStatus !== null ? (int) $imStatus : null; return $this; 
}
 
    public function getCorrelationId(): ?string 
{
 return $this->correlationId; 
}
 
    public function setCorrelationId(?string $correlationId): self 
{
 $this->correlationId = $correlationId; return $this; 
}
 
    public function getUsage(): ?array 
{
 return $this->usage; 
}
 
    public function setUsage(?array $usage): self 
{
 $this->usage = $usage; return $this; 
}
 
    public function toArray(): array 
{
 $result = [ 'id' => $this->id, 'sender_type' => $this->senderType, 'sender_uid' => $this->senderUid, 'receiver_uid' => $this->receiverUid, 'message_id' => $this->messageId, 'type' => $this->type, 'task_id' => $this->taskId, 'topic_id' => $this->topicId, 'status' => $this->status, 'content' => $this->content, 'raw_content' => $this->rawContent, 'steps' => $this->getSteps(), 'tool' => $this->gettool (), 'attachments' => $this->getAttachments(), 'mentions' => $this->getMentions(), 'event' => $this->event, 'send_timestamp' => $this->sendTimestamp, 'show_in_ui' => $this->showInUi, // NewQueueprocess Field 'raw_data' => $this->rawData, 'seq_id' => $this->seqId, 'processing_status' => $this->processingStatus, 'error_message' => $this->errorMessage, 'retry_count' => $this->retryCount, 'processed_at' => $this->processedAt, 'im_seq_id' => $this->imSeqId, 'im_status' => $this->imStatus, 'correlation_id' => $this->correlationId, 'usage' => $this->usage, ]; return array_filter($result, function ($value) 
{
 return $value !== null; 
}
); 
}
 
    public function toArrayWithoutOtherField(): array 
{
 return [ 'id' => $this->id, 'sender_type' => $this->senderType, 'sender_uid' => $this->senderUid, 'receiver_uid' => $this->receiverUid, 'message_id' => $this->messageId, 'type' => $this->type, 'task_id' => $this->taskId, 'topic_id' => $this->topicId, 'status' => $this->status, 'content' => $this->content, 'raw_content' => $this->rawContent ?? '', 'steps' => $this->getSteps() !== null ? json_encode($this->getSteps(), JSON_UNESCAPED_UNICODE) : null, 'tool' => $this->gettool () !== null ? json_encode($this->gettool (), JSON_UNESCAPED_UNICODE) : null, 'attachments' => $this->getAttachments() !== null ? json_encode($this->getAttachments(), JSON_UNESCAPED_UNICODE) : null, 'mentions' => $this->getMentions() !== null ? json_encode($this->getMentions(), JSON_UNESCAPED_UNICODE) : null, 'event' => $this->event, 'send_timestamp' => $this->sendTimestamp, 'show_in_ui' => $this->showInUi, 'raw_data' => $this->rawData ?? '', 'seq_id' => $this->seqId, 'processing_status' => $this->processingStatus, 'error_message' => $this->errorMessage, 'retry_count' => $this->retryCount, 'processed_at' => $this->processedAt, 'im_seq_id' => $this->imSeqId, 'correlation_id' => $this->correlationId, ]; 
}
 
    public 
    static function taskMessageDTOToTaskMessageEntity(TaskMessageDTO $taskMessageDTO): TaskMessageEntity 
{
 $messageData = [ 'task_id' => $taskMessageDTO->getTaskId(), 'sender_type' => $taskMessageDTO->getRole(), 'sender_uid' => $taskMessageDTO->getSenderUid(), 'receiver_uid' => $taskMessageDTO->getReceiverUid(), 'type' => $taskMessageDTO->getMessageType(), 'content' => $taskMessageDTO->getContent(), 'status' => $taskMessageDTO->getStatus(), 'steps' => $taskMessageDTO->getSteps(), 'tool' => $taskMessageDTO->gettool (), 'attachments' => $taskMessageDTO->getAttachments(), 'mentions' => $taskMessageDTO->getMentions(), 'topic_id' => $taskMessageDTO->getTopicId(), 'event' => $taskMessageDTO->getEvent(), 'show_in_ui' => $taskMessageDTO->isShowInUi(), 'raw_content' => $taskMessageDTO->getRawContent(), ]; // Add message_id if provided if ($taskMessageDTO->getMessageId() !== null) 
{
 $messageData['message_id'] = $taskMessageDTO->getMessageId(); 
}
 // Add im_seq_id if provided if ($taskMessageDTO->getImSeqId() !== null) 
{
 $messageData['im_seq_id'] = $taskMessageDTO->getImSeqId(); 
}
 // Add correlation_id if provided if ($taskMessageDTO->getCorrelationId() !== null) 
{
 $messageData['correlation_id'] = $taskMessageDTO->getCorrelationId(); 
}
 return new TaskMessageEntity($messageData); 
}
 
}
 
