<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request;

use App\Infrastructure\Core\AbstractRequestDTO;

class CreateAgentTaskRequestDTO extends AbstractRequestDTO 
{
 
    protected string $topicId = ''; 
    protected string $prompt = ''; 
    protected string $accessToken = ''; 
    protected string $conversationId = ''; /** * Validate Rule. */ 
    public 
    static function getHyperfValidate Rules(): array 
{
 return [ 'topic_id' => 'string', 'prompt' => 'required|string', ]; 
}
 
    public 
    static function getHyperfValidate Message(): array 
{
 return [ 'topic_id.string' => 'topic IDCannot be empty', 'prompt.required' => 'TaskNoticeCannot be empty', ]; 
}
 /** * PropertyName. */ 
    public function attributes(): array 
{
 return [ 'topic_id' => 'topic ID', 'prompt' => 'TaskNotice', ]; 
}
 
    public function getTopicId(): string 
{
 return $this->topicId; 
}
 
    public function getConversationId(): string 
{
 return $this->conversationId; 
}
 
    public function setConversationId(string $conversationId): void 
{
 $this->conversationId = $conversationId; 
}
 
    public function getPrompt(): string 
{
 return $this->prompt; 
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
 
