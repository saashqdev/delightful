<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject;

use App\Domain\Chat\DTO\Message\Common\MessageExtra\SuperAgent\SuperAgentExtra;
use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\TaskEntity;
/** * TaskContextObjectfor Taskrelated ContextParameter. * * Classyes ValueObjectComply withDDDSchema */

class TaskContext 
{
 /** * @param TaskEntity $task Task * @param DataIsolation $dataIsolation DataObject * @param string $chatConversationId SessionID * @param string $chatTopicId ThemeID * @param string $agentuser Id Agentuser ID * @param string $sandboxId Sandbox ID * @param string $taskId TaskID * @param ChatInstruction $instruction * @param string $agentMode AgentSchema * @param array $mcpConfig MCPConfiguration * @param string $workspaceId workspace ID * @param string $messageId MessageID * @param bool $isFirstTask whether as Task * @param null|SuperAgentExtra $extra ExtensionParameter */ 
    public function __construct( 
    private readonly TaskEntity $task, 
    private readonly DataIsolation $dataIsolation, 
    private readonly string $chatConversationId, 
    private readonly string $chatTopicId, 
    private readonly string $agentuser Id, 
    private string $sandboxId = '', 
    private string $taskId = '', 
    private ChatInstruction $instruction = ChatInstruction::Normal, 
    private string $agentMode = '', 
    private array $mcpConfig = [], 
    private string $modelId = '', 
    private array $dynamicConfig = [], 
    private string $workspaceId = '', 
    private string $messageId = '', 
    private bool $isFirstTask = false, private ?SuperAgentExtra $extra = null, ) 
{
 
}
 /** * GetTask. */ 
    public function getTask(): TaskEntity 
{
 return $this->task; 
}
 /** * GetDataObject */ 
    public function getDataIsolation(): DataIsolation 
{
 return $this->dataIsolation; 
}
 /** * GetSessionID. */ 
    public function getChatConversationId(): string 
{
 return $this->chatConversationId; 
}
 /** * GetThemeID. */ 
    public function getChatTopicId(): string 
{
 return $this->chatTopicId; 
}
 /** * GetAgentuser ID. */ 
    public function getAgentuser Id(): string 
{
 return $this->agentuser Id; 
}
 /** * Get sandbox ID. */ 
    public function getSandboxId(): string 
{
 return $this->sandboxId; 
}
 /** * GetTaskID. */ 
    public function getTaskId(): string 
{
 return $this->taskId ?: $this->task->getTaskId(); 
}
 /** * Getoriginal TaskIDFromTaskin Get. */ 
    public function getOriginalTaskId(): string 
{
 return $this->task->getTaskId(); 
}
 /** * GetTaskID. */ 
    public function getTaskEntityId(): int 
{
 return $this->task->getId(); 
}
 /** * GetTaskThemeID. */ 
    public function getTopicId(): int 
{
 return $this->task->getTopicId(); 
}
 /** * GetProject ID. */ 
    public function getProjectId(): int 
{
 return $this->task->getProjectId(); 
}
 /** * Getcurrent user ID. */ 
    public function getcurrent user Id(): string 
{
 return $this->dataIsolation->getcurrent user Id(); 
}
 /** * Getcurrent OrganizationCode */ 
    public function getcurrent OrganizationCode(): string 
{
 return $this->dataIsolation->getcurrent OrganizationCode(); 
}
 /** * Get. */ 
    public function getInstruction(): ChatInstruction 
{
 return $this->instruction; 
}
 /** * GetAgentSchema. */ 
    public function getAgentMode(): string 
{
 return $this->agentMode; 
}
 
    public function getMcpConfig(): array 
{
 return $this->mcpConfig; 
}
 /** * CreateHaveNewTaskParameterContext. */ 
    public function withTask(TaskEntity $newTask): self 
{
 return new self( $newTask, $this->dataIsolation, $this->chatConversationId, $this->chatTopicId, $this->agentuser Id, $this->sandboxId, $this->taskId, $this->instruction, $this->agentMode, $this->mcpConfig, $this->modelId, $this->dynamicConfig, $this->workspaceId, $this->messageId, $this->isFirstTask, $this->extra, ); 
}
 
    public function setTaskId(string $taskId): self 
{
 $this->taskId = $taskId; return $this; 
}
 
    public function setSandboxId(string $sandboxId): self 
{
 $this->sandboxId = $sandboxId; return $this; 
}
 
    public function setInstruction(ChatInstruction $instruction): self 
{
 $this->instruction = $instruction; return $this; 
}
 
    public function setAgentMode(string $agentMode): self 
{
 $this->agentMode = $agentMode; return $this; 
}
 
    public function setMcpConfig(array $mcpConfig): self 
{
 $this->mcpConfig = $mcpConfig; return $this; 
}
 
    public function getModelId(): string 
{
 return $this->modelId; 
}
 
    public function setModelId(string $modelId): self 
{
 $this->modelId = $modelId; return $this; 
}
 
    public function getDynamicConfig(): array 
{
 if (! empty($this->modelId) && empty($this->dynamicConfig['models'][$this->getModelId()])) 
{
 // AddDefaultConfiguration $this->dynamicConfig['models'][$this->getModelId()] = [ 'api_key' => '$
{
MAGIC_API_KEY
}
', 'api_base_url' => '$
{
MAGIC_API_BASE_URL
}
', 'name' => $this->getModelId(), ]; 
}
 return $this->dynamicConfig; 
}
 
    public function setDynamicConfig(array $dynamicConfig): self 
{
 $this->dynamicConfig = $dynamicConfig; return $this; 
}
 
    public function setWorkspaceId(string $workspaceId): self 
{
 $this->workspaceId = $workspaceId; return $this; 
}
 
    public function getWorkspaceId(): string 
{
 return $this->workspaceId; 
}
 /** * GetMessageID. */ 
    public function getMessageId(): string 
{
 return $this->messageId; 
}
 /** * Set MessageID. */ 
    public function setMessageId(string $messageId): self 
{
 $this->messageId = $messageId; return $this; 
}
 /** * Getwhether as Task. */ 
    public function getIsFirstTask(): bool 
{
 return $this->isFirstTask; 
}
 /** * Set whether as Task. */ 
    public function setIsFirstTask(bool $isFirstTask): self 
{
 $this->isFirstTask = $isFirstTask; return $this; 
}
 /** * GetExtensionParameter. */ 
    public function getExtra(): ?SuperAgentExtra 
{
 return $this->extra; 
}
 /** * Set ExtensionParameter. */ 
    public function setExtra(?SuperAgentExtra $extra): self 
{
 $this->extra = $extra; return $this; 
}
 
}
 
