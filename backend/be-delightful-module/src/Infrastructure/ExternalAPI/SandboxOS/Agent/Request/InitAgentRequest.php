<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Agent\Request;

use App\Infrastructure\Util\IdGenerator\IdGenerator;

/**
 * Init Agent request class
 * Follows the sandbox communication init request format.
 */
class InitAgentRequest
{
    public function __construct(
        private string $messageId = '',
        private string $userId = '',
        private string $projectId = '',
        private array $uploadConfig = [],
        private array $messageSubscriptionConfig = [],
        private array $stsTokenRefresh = [],
        private array $metadata = [],
        private string $taskMode = 'plan',
        private string $agentMode = '',
        private string $delightfulServiceHost = '',
        private string $chatHistoryDir = '',
        private string $workDir = '',
        private ?string $memory = null,
        private ?string $modelId = null,
        private bool $fetchHistory = true
    ) {
    }

    /**
     * Create an init request from array data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['message_id'] ?? '',
            $data['user_id'] ?? '',
            $data['project_id'] ?? '',
            $data['upload_config'] ?? [],
            $data['message_subscription_config'] ?? [],
            $data['sts_token_refresh'] ?? [],
            $data['metadata'] ?? [],
            $data['task_mode'] ?? 'plan',
            $data['agent_mode'] ?? '',
            $data['delightful_service_host'] ?? config('be-delightful.sandbox.callback_host', ''),
            $data['chat_history_dir'] ?? '',
            $data['work_dir'] ?? '',
            $data['memory'] ?? null,
            $data['model_id'] ?? null,
            $data['fetch_history'] ?? true
        );
    }

    /**
     * Create a default init request
     */
    public static function createDefault(): self
    {
        return new self();
    }

    /**
     * Create an init request
     */
    public static function create(
        string $messageId,
        string $userId,
        string $projectId = '',
        array $uploadConfig = [],
        array $messageSubscriptionConfig = [],
        array $stsTokenRefresh = [],
        array $metadata = [],
        string $taskMode = 'plan',
        string $agentMode = '',
        string $delightfulServiceHost = '',
        string $chatHistoryDir = '',
        string $workDir = '',
        ?string $memory = null,
        ?string $modelId = null,
        bool $fetchHistory = true
    ): self {
        return new self(
            $messageId,
            $userId,
            $projectId,
            $uploadConfig,
            $messageSubscriptionConfig,
            $stsTokenRefresh,
            $metadata,
            $taskMode,
            $agentMode,
            $delightfulServiceHost,
            $chatHistoryDir,
            $workDir,
            $memory,
            $modelId,
            $fetchHistory
        );
    }

    /**
     * Get upload config.
     */
    public function getUploadConfig(): array
    {
        return $this->uploadConfig;
    }

    /**
     * Set upload config.
     */
    public function setUploadConfig(array $uploadConfig): self
    {
        $this->uploadConfig = $uploadConfig;
        return $this;
    }

    /**
     * Get message subscription config.
     */
    public function getMessageSubscriptionConfig(): array
    {
        return $this->messageSubscriptionConfig;
    }

    /**
     * Set message subscription config.
     */
    public function setMessageSubscriptionConfig(array $messageSubscriptionConfig): self
    {
        $this->messageSubscriptionConfig = $messageSubscriptionConfig;
        return $this;
    }

    /**
     * Get STS token refresh config.
     */
    public function getStsTokenRefresh(): array
    {
        return $this->stsTokenRefresh;
    }

    /**
     * Set STS token refresh config.
     */
    public function setStsTokenRefresh(array $stsTokenRefresh): self
    {
        $this->stsTokenRefresh = $stsTokenRefresh;
        return $this;
    }

    /**
     * Get metadata.
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Set metadata.
     */
    public function setMetadata(array $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }

    /**
     * Get task mode.
     */
    public function getTaskMode(): string
    {
        return $this->taskMode;
    }

    /**
     * Set task mode.
     */
    public function setTaskMode(string $taskMode): self
    {
        $this->taskMode = $taskMode;
        return $this;
    }

    /**
     * Set user ID.
     */
    public function setUserId(string $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    /**
     * Get user ID.
     */
    public function getUserId(): string
    {
        return $this->userId;
    }

    /**
     * Set message ID.
     */
    public function setMessageId(string $messageId): self
    {
        $this->messageId = $messageId;
        return $this;
    }

    /**
     * Get message ID.
     */
    public function getMessageId(): string
    {
        return $this->messageId;
    }

    /**
     * Set project ID.
     */
    public function setProjectId(string $projectId): self
    {
        $this->projectId = $projectId;
        return $this;
    }

    /**
     * Get project ID.
     */
    public function getProjectId(): string
    {
        return $this->projectId;
    }

    public function setAgentMode(string $agentMode): self
    {
        $this->agentMode = $agentMode;
        return $this;
    }

    public function getAgentMode(): string
    {
        return $this->agentMode;
    }

    public function setDelightfulServiceHost(string $delightfulServiceHost): self
    {
        $this->delightfulServiceHost = $delightfulServiceHost;
        return $this;
    }

    public function getDelightfulServiceHost(): string
    {
        return $this->delightfulServiceHost;
    }

    /**
     * Set chat history directory.
     */
    public function setChatHistoryDir(string $chatHistoryDir): self
    {
        $this->chatHistoryDir = $chatHistoryDir;
        return $this;
    }

    /**
     * Get chat history directory.
     */
    public function getChatHistoryDir(): string
    {
        return $this->chatHistoryDir;
    }

    public function getWorkDir(): string
    {
        return $this->workDir;
    }

    public function setWorkDir(string $workDir): self
    {
        $this->workDir = $workDir;
        return $this;
    }

    /**
     * Get memory content.
     */
    public function getMemory(): ?string
    {
        return $this->memory;
    }

    /**
     * Set memory content.
     */
    public function setMemory(?string $memory): self
    {
        $this->memory = $memory;
        return $this;
    }

    public function getModelId(): ?string
    {
        return $this->modelId;
    }

    public function setModelId(?string $modelId): self
    {
        $this->modelId = $modelId;
        return $this;
    }

    /**
     * Get fetch history flag.
     */
    public function getFetchHistory(): bool
    {
        return $this->fetchHistory;
    }

    /**
     * Set fetch history flag.
     */
    public function setFetchHistory(bool $fetchHistory): self
    {
        $this->fetchHistory = $fetchHistory;
        return $this;
    }

    /**
     * Convert to API request array
     * Matches the sandbox communication init request format.
     */
    public function toArray(): array
    {
        return [
            'message_id' => ! empty($this->messageId) ? $this->messageId : (string) IdGenerator::getSnowId(),
            'user_id' => $this->userId,
            'project_id' => $this->projectId,
            'type' => 'init',
            'upload_config' => $this->uploadConfig,
            'message_subscription_config' => $this->messageSubscriptionConfig,
            'sts_token_refresh' => $this->stsTokenRefresh,
            'metadata' => $this->metadata,
            'task_mode' => $this->taskMode,
            'agent_mode' => $this->agentMode,
            'delightful_service_host' => $this->delightfulServiceHost,
            'chat_history_dir' => $this->chatHistoryDir,
            'work_dir' => $this->workDir,
            'memory' => $this->memory,
            'model_id' => $this->modelId,
            'fetch_history' => $this->fetchHistory,
        ];
    }
}
