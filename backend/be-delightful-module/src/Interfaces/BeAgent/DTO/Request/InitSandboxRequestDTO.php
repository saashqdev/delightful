<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Interfaces\BeAgent\DTO\Request;

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

    protected string $prompt = '';

    protected ?string $params = null;

    protected string $accessToken = '';

    protected string $conversationId = '';

    /**
     * Validation rules.
     */
    public static function getHyperfValidationRules(): array
    {
        return [
            'task_type' => 'required|string', // Task type, supports three modes: agent, tool, custom
            // 'agent_name' => 'required_if:task_type,agent|string',//If agent mode, agent_name is required
            // 'tool_name' => 'required_if:task_type,tool|string',//If tool mode, tool_name is required
            // 'custom_name' => 'required_if:task_type,custom|string',//If custom mode, custom_name is required
            'model_id' => 'string', // Model ID
            'workspace_id' => 'string', // Workspace ID
            'project_id' => 'string', // Project ID
            'project_mode' => 'string', // Project mode
            'topic_id' => 'string', // Topic ID
            'topic_mode' => 'string', // Topic mode
            // 'prompt' => 'required_if:task_type,agent|string',//Task prompt, if agent mode, prompt is required
            'params' => 'object', // Custom params
        ];
    }

    public static function getHyperfValidationMessage(): array
    {
        return [
            'task_type.required' => 'Task type cannot be empty',
            // 'agent_name.required_if' => 'Agent name cannot be empty',
            // 'tool_name.required_if' => 'Tool name cannot be empty',
            // 'custom_name.required_if' => 'Custom name cannot be empty',
            'model_id.string' => 'Model ID cannot be empty',
            'workspace_id.integer' => 'Workspace ID cannot be empty',
            'project_id.integer' => 'Project ID cannot be empty',
            'project_mode.string' => 'Project mode cannot be empty',
            'topic_id.integer' => 'Topic ID cannot be empty',
            'topic_mode.string' => 'Topic mode cannot be empty',
            'prompt.required_if' => 'Prompt cannot be empty',
            'params.object' => 'Params cannot be empty',
        ];
    }

    /**
     * Attribute names.
     */
    public function attributes(): array
    {
        return [
            'task_type' => 'Task type',
            'agent_name' => 'Agent name',
            'tool_name' => 'Tool name',
            'custom_name' => 'Custom name',
            'model_id' => 'Model ID',
            'workspace_id' => 'Workspace ID',
            'project_id' => 'Project ID',
            'project_mode' => 'Project mode',
            'topic_mode' => 'Topic mode',
            'topic_id' => 'Topic ID',
            'chat_topic_id' => 'Chat topic ID',
            'prompt' => 'Prompt',
            'params' => 'Params',
        ];
    }

    public function getTaskType(): string
    {
        return $this->taskType;
    }

    public function getAgentName(): string
    {
        return $this->agentName;
    }

    public function getToolName(): string
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

    /*
     * Prepare data.
     */
    // protected function prepareForValidation(): void
    // {
    //     $this->taskType = (string) $this->input('task_type', '');
    //     $this->agentName = (string) $this->input('agent_name', '');
    //     $this->toolName = (string) $this->input('tool_name', '');
    //     $this->customName = (string) $this->input('custom_name', '');
    //     $this->modelId = (string) $this->input('model_id', '');
    //     $this->workspaceId = (int) $this->input('workspace_id', 0);
    //     $this->projectId = (int) $this->input('project_id', 0);
    //     $this->topicId = (int) $this->input('topic_id', 0);
    //     $this->prompt = (string) $this->input('prompt', '');
    //     $this->params = $this->has('params') ? (string) $this->input('params') : null;
    // }
}
