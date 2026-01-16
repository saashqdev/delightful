<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request;

use App\Infrastructure\Core\AbstractRequestDTO;

class InitSandboxRequestDTO extends AbstractRequestDTO 
{
 
    protected string $taskType = ''; 
    protected string $agentName = ''; 
    protected string $toolName = ''; 
    protected string $customName = ''; 
    protected string $modelId = ''; 
    protected string $workspaceId = ''; 
    protected string $projectId = ''; 
    protected string $projectMode = 'general'; 
    protected string $topicId = ''; 
    protected string $chatTopicId = ''; 
    protected string $topicMode = ''; 
    protected string $prompt = ''; protected ?string $params = null; 
    protected string $accessToken = ''; 
    protected string $conversationId = ''; /** * Validate Rule. */ 
    public 
    static function getHyperfValidate Rules(): array 
{
 return [ 'task_type' => 'required|string', // TaskTypeSupportagenttoolcustomthree types Schema // 'agent_name' => 'required_if:task_type,agent|string',//Ifyes agentSchemaneed agent_name // 'tool_name' => 'required_if:task_type,tool|string',//Ifyes toolSchemaneed tool_name // 'custom_name' => 'required_if:task_type,custom|string',//Ifyes customSchemaneed custom_name 'model_id' => 'string', // ModelID 'workspace_id' => 'string', // workspace ID 'project_id' => 'string', // Project ID 'project_mode' => 'string', // ItemSchema 'topic_id' => 'string', // topic ID 'topic_mode' => 'string', // topic Schema // 'prompt' => 'required_if:task_type,agent|string',//TaskNotice,Ifyes agentSchemaneed prompt 'params' => 'object', // params ]; 
}
 
    public 
    static function getHyperfValidate Message(): array 
{
 return [ 'task_type.required' => 'TaskTypeCannot be empty', // 'agent_name.required_if' => 'agentNameCannot be empty', // 'tool_name.required_if' => 'toolNameCannot be empty', // 'custom_name.required_if' => 'customNameCannot be empty', 'model_id.string' => 'ModelIDCannot be empty', 'workspace_id.integer' => 'workspace IDCannot be empty', 'project_id.integer' => 'Project IDCannot be empty', 'project_mode.string' => 'ItemSchemaCannot be empty', 'topic_id.integer' => 'topic IDCannot be empty', 'topic_mode.string' => 'topic SchemaCannot be empty', 'prompt.required_if' => 'NoticeCannot be empty', 'params.object' => 'ParameterCannot be empty', ]; 
}
 /** * PropertyName. */ 
    public function attributes(): array 
{
 return [ 'task_type' => 'TaskType', 'agent_name' => 'agentName', 'tool_name' => 'toolName', 'custom_name' => 'customName', 'model_id' => 'ModelID', 'workspace_id' => 'workspace ID', 'project_id' => 'Project ID', 'project_mode' => 'ItemSchema', 'topic_mode' => 'topic Schema', 'topic_id' => 'topic ID', 'chat_topic_id' => 'topic ID', 'prompt' => 'Notice', 'params' => 'Parameter', ]; 
}
 
    public function getTaskType(): string 
{
 return $this->taskType; 
}
 
    public function getAgentName(): string 
{
 return $this->agentName; 
}
 
    public function gettool Name(): string 
{
 return $this->toolName; 
}
 
    public function getCustomName(): string 
{
 return $this->customName; 
}
 
    public function getModelId(): string 
{
 return $this->modelId; 
}
 
    public function getWorkspaceId(): string 
{
 return $this->workspaceId; 
}
 
    public function setWorkspaceId(string $workspaceId): void 
{
 $this->workspaceId = $workspaceId; 
}
 
    public function getProjectId(): string 
{
 return $this->projectId; 
}
 
    public function setProjectId(string $projectId): void 
{
 $this->projectId = $projectId; 
}
 
    public function getProjectMode(): string 
{
 return $this->projectMode; 
}
 
    public function setProjectMode(string $projectMode): void 
{
 $this->projectMode = $projectMode; 
}
 
    public function getTopicId(): string 
{
 return $this->topicId; 
}
 
    public function setTopicId(string $topicId): void 
{
 $this->topicId = $topicId; 
}
 
    public function getChatTopicId(): string 
{
 return $this->chatTopicId; 
}
 
    public function setChatTopicId(string $chatTopicId): void 
{
 $this->chatTopicId = $chatTopicId; 
}
 
    public function getConversationId(): string 
{
 return $this->conversationId; 
}
 
    public function setConversationId(string $conversationId): void 
{
 $this->conversationId = $conversationId; 
}
 
    public function getTopicMode(): string 
{
 return $this->topicMode; 
}
 
    public function setTopicMode(string $topicMode): void 
{
 $this->topicMode = $topicMode; 
}
 
    public function getPrompt(): string 
{
 return $this->prompt; 
}
 
    public function getParams(): ?string 
{
 return $this->params; 
}
 
    public function setAccessToken(string $accessToken): void 
{
 $this->accessToken = $accessToken; 
}
 
    public function getAccessToken(): string 
{
 return $this->accessToken; 
}
 /* * Data. */ // 
    protected function prepareForValidate (): void // 
{
 // $this->taskType = (string) $this->input('task_type', ''); // $this->agentName = (string) $this->input('agent_name', ''); // $this->toolName = (string) $this->input('tool_name', ''); // $this->customName = (string) $this->input('custom_name', ''); // $this->modelId = (string) $this->input('model_id', ''); // $this->workspaceId = (int) $this->input('workspace_id', 0); // $this->projectId = (int) $this->input('project_id', 0); // $this->topicId = (int) $this->input('topic_id', 0); // $this->prompt = (string) $this->input('prompt', ''); // $this->params = $this->has('params') ? (string) $this->input('params') : null; // 
}
 
}
 
