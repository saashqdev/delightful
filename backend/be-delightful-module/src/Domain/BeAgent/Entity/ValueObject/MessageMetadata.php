<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject;

/** * MessageDataValueObject. */

class MessageMetadata 
{
 private ?user info ValueObject $userinfo = null; /** * Function. * * @param string $agentuser Id user ID * @param string $userId user ID * @param string $organizationCode OrganizationCode * @param string $chatConversationId SessionID * @param string $chatTopicId topic ID * @param string $topicId topic ID * @param string $instruction * @param string $sandboxId Sandbox ID * @param string $BeDelightfulTaskId TaskID * @param string $workspaceId workspace ID * @param string $projectId Project ID * @param string $language user * @param null|user info ValueObject $userinfo user info Object * @param bool $skipInitMessages whether SkipInitializeMessage */ 
    public function __construct( 
    private string $agentuser Id = '', 
    private string $userId = '', 
    private string $organizationCode = '', 
    private string $chatConversationId = '', 
    private string $chatTopicId = '', 
    private string $topicId = '', 
    private string $instruction = '', 
    private string $sandboxId = '', 
    private string $BeDelightfulTaskId = '', 
    private string $workspaceId = '', 
    private string $projectId = '', 
    private string $language = '', ?user info ValueObject $userinfo = null, 
    private bool $skipInitMessages = false ) 
{
 $this->userinfo = $userinfo ; 
}
 /** * FromArrayCreateDataObject. * * @param array $data DataArray */ 
    public 
    static function fromArray(array $data): self 
{
 $userinfo = null; if (isset($data['user']) && is_array($data['user'])) 
{
 $userinfo = user info ValueObject::fromArray($data['user']); 
}
 return new self( $data['agent_user_id'] ?? '', $data['user_id'] ?? '', $data['organization_code'] ?? '', $data['chat_conversation_id'] ?? '', $data['chat_topic_id'] ?? '', $data['topic_id'] ?? '', $data['instruction'] ?? '', $data['sandbox_id'] ?? '', $data['super_magic_task_id'] ?? '', $data['workspace_id'] ?? '', $data['project_id'] ?? '', $data['language'] ?? '', $userinfo , $data['skip_init_messages'] ?? false ); 
}
 /** * Convert toArray. * * @return array DataArray */ 
    public function toArray(): array 
{
 $result = [ 'agent_user_id' => $this->agentuser Id, 'user_id' => $this->userId, 'organization_code' => $this->organizationCode, 'chat_conversation_id' => $this->chatConversationId, 'chat_topic_id' => $this->chatTopicId, 'topic_id' => $this->topicId, 'instruction' => $this->instruction, 'sandbox_id' => $this->sandboxId, 'super_magic_task_id' => $this->BeDelightfulTaskId, 'workspace_id' => $this->workspaceId, 'project_id' => $this->projectId, 'language' => $this->language, 'skip_init_messages' => $this->skipInitMessages, ]; // Adduser info IfExist if ($this->userinfo !== null) 
{
 $result['user'] = $this->userinfo ->toArray(); 
}
 return $result; 
}
 // Getters 
    public function getAgentuser Id(): string 
{
 return $this->agentuser Id; 
}
 
    public function getuser Id(): string 
{
 return $this->userId; 
}
 
    public function getOrganizationCode(): string 
{
 return $this->organizationCode; 
}
 
    public function getChatConversationId(): string 
{
 return $this->chatConversationId; 
}
 
    public function getChatTopicId(): string 
{
 return $this->chatTopicId; 
}
 
    public function getTopicId(): string 
{
 return $this->topicId; 
}
 
    public function getInstruction(): string 
{
 return $this->instruction; 
}
 
    public function getSandboxId(): string 
{
 return $this->sandboxId; 
}
 
    public function getBeDelightfulTaskId(): string 
{
 return $this->BeDelightfulTaskId; 
}
 
    public function getWorkspaceId(): string 
{
 return $this->workspaceId; 
}
 
    public function getProjectId(): string 
{
 return $this->projectId; 
}
 
    public function getLanguage(): string 
{
 return $this->language; 
}
 
    public function setLanguage(string $language): self 
{
 $this->language = $language; return $this; 
}
 /** * Getuser info . * * @return null|user info ValueObject user info Object */ 
    public function getuser info (): ?user info ValueObject 
{
 return $this->userinfo ; 
}
 // Withers for immutability 
    public function withAgentuser Id(string $agentuser Id): self 
{
 $clone = clone $this; $clone->agentuser Id = $agentuser Id; return $clone; 
}
 
    public function withuser Id(string $userId): self 
{
 $clone = clone $this; $clone->userId = $userId; return $clone; 
}
 
    public function withOrganizationCode(string $organizationCode): self 
{
 $clone = clone $this; $clone->organizationCode = $organizationCode; return $clone; 
}
 
    public function withChatConversationId(string $chatConversationId): self 
{
 $clone = clone $this; $clone->chatConversationId = $chatConversationId; return $clone; 
}
 
    public function withChatTopicId(string $chatTopicId): self 
{
 $clone = clone $this; $clone->chatTopicId = $chatTopicId; return $clone; 
}
 
    public function withTopicId(string $topicId): self 
{
 $clone = clone $this; $clone->topicId = $topicId; return $clone; 
}
 
    public function withInstruction(string $instruction): self 
{
 $clone = clone $this; $clone->instruction = $instruction; return $clone; 
}
 
    public function withSandboxId(string $sandboxId): self 
{
 $clone = clone $this; $clone->sandboxId = $sandboxId; return $clone; 
}
 
    public function withBeDelightfulTaskId(string $BeDelightfulTaskId): self 
{
 $clone = clone $this; $clone->BeDelightfulTaskId = $BeDelightfulTaskId; return $clone; 
}
 
    public function withWorkspaceId(string $workspaceId): self 
{
 $clone = clone $this; $clone->workspaceId = $workspaceId; return $clone; 
}
 
    public function withProjectId(string $projectId): self 
{
 $clone = clone $this; $clone->projectId = $projectId; return $clone; 
}
 
    public function withLanguage(string $language): self 
{
 $clone = clone $this; $clone->language = $language; return $clone; 
}
 /** * Set user info . * * @param null|user info ValueObject $userinfo user info Object * @return self NewInstance */ 
    public function withuser info (?user info ValueObject $userinfo ): self 
{
 $clone = clone $this; $clone->userinfo = $userinfo ; return $clone; 
}
 /** * check whether Haveuser info . * * @return bool whether Haveuser info */ 
    public function hasuser info (): bool 
{
 return $this->userinfo !== null; 
}
 /** * Getwhether SkipInitializeMessage. * * @return bool whether SkipInitializeMessage */ 
    public function getSkipInitMessages(): bool 
{
 return $this->skipInitMessages; 
}
 /** * Set whether SkipInitializeMessage. * * @param bool $skipInitMessages whether SkipInitializeMessage * @return self NewInstance */ 
    public function withSkipInitMessages(bool $skipInitMessages): self 
{
 $clone = clone $this; $clone->skipInitMessages = $skipInitMessages; return $clone; 
}
 
}
 
