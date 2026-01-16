<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request;

use JsonSerializable;
/** * STS Token RefreshRequest DTO. */

class RefreshStsTokenRequestDTO implements JsonSerializable 
{
 /** * Proxyuser ID. */ 
    private string $agentuser Id = ''; /** * user ID. */ 
    private string $userId = ''; /** * organization code */ 
    private string $organizationCode = ''; /** * SessionID. */ 
    private string $chatConversationId = ''; /** * topic ID. */ 
    private string $chatTopicId = ''; /** * Type. */ 
    private string $instruction = ''; /** * Sandbox ID. */ 
    private string $sandboxId = ''; /** * MagicTaskID. */ 
    private string $BeDelightfulTaskId = ''; /** * FromRequestDataCreateDTO. */ 
    public 
    static function fromRequest(array $data): self 
{
 $instance = new self(); if (isset($data['metadata'])) 
{
 $metadata = $data['metadata']; $instance->agentuser Id = $metadata['agent_user_id'] ?? ''; $instance->userId = $metadata['user_id'] ?? ''; $instance->organizationCode = $metadata['organization_code'] ?? ''; $instance->chatConversationId = $metadata['chat_conversation_id'] ?? ''; $instance->chatTopicId = $metadata['chat_topic_id'] ?? ''; $instance->instruction = $metadata['instruction'] ?? ''; $instance->sandboxId = $metadata['sandbox_id'] ?? ''; $instance->BeDelightfulTaskId = $metadata['super_magic_task_id'] ?? ''; 
}
 return $instance; 
}
 
    public function getAgentuser Id(): string 
{
 return $this->agentuser Id; 
}
 
    public function setAgentuser Id(string $agentuser Id): self 
{
 $this->agentuser Id = $agentuser Id; return $this; 
}
 
    public function getuser Id(): string 
{
 return $this->userId; 
}
 
    public function setuser Id(string $userId): self 
{
 $this->userId = $userId; return $this; 
}
 
    public function getOrganizationCode(): string 
{
 return $this->organizationCode; 
}
 
    public function setOrganizationCode(string $organizationCode): self 
{
 $this->organizationCode = $organizationCode; return $this; 
}
 
    public function getChatConversationId(): string 
{
 return $this->chatConversationId; 
}
 
    public function setChatConversationId(string $chatConversationId): self 
{
 $this->chatConversationId = $chatConversationId; return $this; 
}
 
    public function getChatTopicId(): string 
{
 return $this->chatTopicId; 
}
 
    public function setChatTopicId(string $chatTopicId): self 
{
 $this->chatTopicId = $chatTopicId; return $this; 
}
 
    public function getInstruction(): string 
{
 return $this->instruction; 
}
 
    public function setInstruction(string $instruction): self 
{
 $this->instruction = $instruction; return $this; 
}
 
    public function getSandboxId(): string 
{
 return $this->sandboxId; 
}
 
    public function setSandboxId(string $sandboxId): self 
{
 $this->sandboxId = $sandboxId; return $this; 
}
 
    public function getBeDelightfulTaskId(): string 
{
 return $this->BeDelightfulTaskId; 
}
 
    public function setBeDelightfulTaskId(string $BeDelightfulTaskId): self 
{
 $this->BeDelightfulTaskId = $BeDelightfulTaskId; return $this; 
}
 /** * ImplementationJsonSerializableInterface. */ 
    public function jsonSerialize(): array 
{
 return [ 'agent_user_id' => $this->agentuser Id, 'user_id' => $this->userId, 'organization_code' => $this->organizationCode, 'chat_conversation_id' => $this->chatConversationId, 'chat_topic_id' => $this->chatTopicId, 'instruction' => $this->instruction, 'sandbox_id' => $this->sandboxId, 'super_magic_task_id' => $this->BeDelightfulTaskId, ]; 
}
 
}
 
