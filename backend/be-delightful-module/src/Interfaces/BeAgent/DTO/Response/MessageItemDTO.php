<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response;

use JsonSerializable;

class MessageItemDTO implements JsonSerializable 
{
 /** * @var int MessageID */ 
    protected int $id; /** * @var string RoleType(user/assistant) */ 
    protected string $role; /** * @var string SendID */ 
    protected string $senderUid; /** * @var string ReceiveID */ 
    protected string $receiverUid; /** * @var string MessageID */ 
    protected string $messageId; /** * @var string MessageType */ 
    protected string $type; /** * @var string TaskID */ 
    protected string $taskId; /** * @var null|string TaskStatus */ protected ?string $status; /** * @var string MessageContent */ 
    protected string $content; /** * @var mixed original MessageContent */ protected $rawContent; /** * @var null|array info */ protected ?array $steps; /** * @var null|array tool call info */ protected ?array $tool; /** * @var int SendTimestamp */ 
    protected int $sendTimestamp; /** * @var string EventType */ 
    protected string $event; /** * @var array info */ 
    protected array $attachments; /** * @var null|string IMStatusmagic_chat_sequencestable  */ protected ?string $imStatus; /** * @var null|string AssociationIDfor MessageAssociation */ protected ?string $correlationId; /** * Function. */ 
    public function __construct(array $data = []) 
{
 $this->id = (int) ($data['id'] ?? 0); $this->role = $data['sender_type']; $this->senderUid = (string) ($data['sender_uid'] ?? ''); $this->receiverUid = (string) ($data['receiver_uid'] ?? ''); $this->messageId = (string) ($data['message_id'] ?? ''); $this->type = (string) ($data['type'] ?? ''); $this->taskId = (string) ($data['task_id'] ?? ''); $this->status = $data['status'] ?? null; $this->content = (string) ($data['content'] ?? ''); // Handle raw_content: null if empty, json_decode if not empty $rawContentValue = $data['raw_content'] ?? null; if (empty($rawContentValue)) 
{
 $this->rawContent = null; 
}
 else 
{
 $this->rawContent = json_decode($rawContentValue, true); 
}
 $this->steps = $data['steps'] ?? null; $this->tool = $data['tool'] ?? null; $this->sendTimestamp = (int) ($data['send_timestamp'] ?? 0); $this->event = (string) ($data['event'] ?? ''); $this->attachments = $data['attachments'] ?? []; $this->imStatus = isset($data['im_status']) ? $this->convertImStatusToString((int) $data['im_status']) : null; $this->correlationId = $data['correlation_id'] ?? null; 
}
 /** * Convert toArray. */ 
    public function toArray(): array 
{
 return [ 'id' => $this->id, 'role' => $this->role, 'sender_uid' => $this->senderUid, 'receiver_uid' => $this->receiverUid, 'message_id' => $this->messageId, 'type' => $this->type, 'task_id' => $this->taskId, 'status' => $this->status, 'content' => $this->content, 'raw_content' => $this->rawContent, 'steps' => $this->steps, 'tool' => $this->tool, 'send_timestamp' => $this->sendTimestamp, 'event' => $this->event, 'attachments' => $this->attachments, 'im_status' => $this->imStatus, 'correlation_id' => $this->correlationId, ]; 
}
 /** * Serializeas JSON. */ 
    public function jsonSerialize(): array 
{
 return $this->toArray(); 
}
 /** * IMStatusNumberConvert toStringMagicMessageStatusEnum. */ 
    private function convertImStatusToString(int $status): string 
{
 return match ($status) 
{
 0 => 'unread', 1 => 'seen', 2 => 'read', 3 => 'revoked', default => 'unread', 
}
; 
}
 
}
 
