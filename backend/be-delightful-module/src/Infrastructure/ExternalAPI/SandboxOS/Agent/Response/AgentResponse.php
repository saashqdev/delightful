<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Agent\Response;

use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Gateway\Result\GatewayResult;

/**
 * Agent response class
 * Wraps Agent API response data.
 */
class AgentResponse extends GatewayResult
{
    private ?string $agentId = null;

    private ?string $sessionId = null;

    private ?string $messageId = null;

    private ?string $responseMessage = null;

    private ?string $responseType = null;

    private array $responseMetadata = [];

    /**
     * Create an Agent response from a Gateway result.
     */
    public static function fromGatewayResult(GatewayResult $gatewayResult): self
    {
        $response = new self(
            $gatewayResult->getCode(),
            $gatewayResult->getMessage(),
            $gatewayResult->getData()
        );

        // Parse Agent-specific response data
        $data = $gatewayResult->getData();
        $response->parseAgentData($data);

        return $response;
    }

    /**
     * Create an Agent response from an API response array.
     */
    public static function fromApiResponse(array $response): self
    {
        $agentResponse = new self(
            $response['code'] ?? 2000,
            $response['message'] ?? 'Unknown error',
            $response['data'] ?? []
        );

        $agentResponse->parseAgentData($response['data'] ?? []);

        return $agentResponse;
    }

    /**
     * Get Agent ID.
     */
    public function getAgentId(): ?string
    {
        return $this->agentId ?? $this->getDataValue('agent_id');
    }

    /**
     * Get session ID.
     */
    public function getSessionId(): ?string
    {
        return $this->sessionId ?? $this->getDataValue('session_id');
    }

    /**
     * Get message ID.
     */
    public function getMessageId(): ?string
    {
        return $this->messageId ?? $this->getDataValue('message_id');
    }

    /**
     * Get response message.
     */
    public function getResponseMessage(): ?string
    {
        return $this->responseMessage ?? $this->getDataValue('response');
    }

    /**
     * Get response type.
     */
    public function getResponseType(): ?string
    {
        return $this->responseType ?? $this->getDataValue('response_type');
    }

    /**
     * Get response metadata.
     */
    public function getResponseMetadata(): array
    {
        return $this->responseMetadata ?: ($this->getDataValue('metadata', []));
    }

    /**
     * Get a specific metadata value.
     */
    public function getMetadataValue(string $key, mixed $default = null): mixed
    {
        $metadata = $this->getResponseMetadata();
        return $metadata[$key] ?? $default;
    }

    /**
     * Check if a response message exists.
     */
    public function hasResponseMessage(): bool
    {
        return ! empty($this->getResponseMessage());
    }

    /**
     * Check if the response is text.
     */
    public function isTextResponse(): bool
    {
        return $this->getResponseType() === 'text';
    }

    /**
     * Check if the response is an error.
     */
    public function isErrorResponse(): bool
    {
        return $this->getResponseType() === 'error';
    }

    /**
     * Convert to array (including Agent-specific fields).
     */
    public function toArray(): array
    {
        $baseArray = parent::toArray();

        return array_merge($baseArray, [
            'agent_id' => $this->getAgentId(),
            'session_id' => $this->getSessionId(),
            'message_id' => $this->getMessageId(),
            'response_message' => $this->getResponseMessage(),
            'response_type' => $this->getResponseType(),
            'response_metadata' => $this->getResponseMetadata(),
        ]);
    }

    /**
     * Parse Agent-specific data.
     */
    private function parseAgentData(array $data): void
    {
        if (isset($data['agent_id'])) {
            $this->agentId = $data['agent_id'];
        }
        if (isset($data['session_id'])) {
            $this->sessionId = $data['session_id'];
        }
        if (isset($data['message_id'])) {
            $this->messageId = $data['message_id'];
        }
        if (isset($data['response'])) {
            $this->responseMessage = $data['response'];
        }
        if (isset($data['response_type'])) {
            $this->responseType = $data['response_type'];
        }
        if (isset($data['metadata']) && is_array($data['metadata'])) {
            $this->responseMetadata = $data['metadata'];
        }
    }
}
