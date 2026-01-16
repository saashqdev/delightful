<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Agent\Response;

use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Gateway\Result\GatewayResult;
/** * AgentResponseClass * Agent APIResponseData. */

class AgentResponse extends GatewayResult 
{
 private ?string $agentId = null; private ?string $sessionId = null; private ?string $messageId = null; private ?string $responseMessage = null; private ?string $responseType = null; 
    private array $responseMetadata = []; /** * FromGatewayResultCreateAgentResponse. */ 
    public 
    static function fromGatewayResult(GatewayResult $gatewayResult): self 
{
 $response = new self( $gatewayResult->getCode(), $gatewayResult->getMessage(), $gatewayResult->getData() ); // Parse AgentResponseData $data = $gatewayResult->getData(); $response->parseAgentData($data); return $response; 
}
 /** * FromAPIResponseCreateAgentResponse. */ 
    public 
    static function fromApiResponse(array $response): self 
{
 $agentResponse = new self( $response['code'] ?? 2000, $response['message'] ?? 'Unknown error', $response['data'] ?? [] ); $agentResponse->parseAgentData($response['data'] ?? []); return $agentResponse; 
}
 /** * GetAgent ID. */ 
    public function getAgentId(): ?string 
{
 return $this->agentId ?? $this->getDataValue('agent_id'); 
}
 /** * GetSessionID. */ 
    public function getSessionId(): ?string 
{
 return $this->sessionId ?? $this->getDataValue('session_id'); 
}
 /** * GetMessageID. */ 
    public function getMessageId(): ?string 
{
 return $this->messageId ?? $this->getDataValue('message_id'); 
}
 /** * GetResponseMessage. */ 
    public function getResponseMessage(): ?string 
{
 return $this->responseMessage ?? $this->getDataValue('response'); 
}
 /** * GetResponseType. */ 
    public function getResponseType(): ?string 
{
 return $this->responseType ?? $this->getDataValue('response_type'); 
}
 /** * GetResponseData. */ 
    public function getResponseMetadata(): array 
{
 return $this->responseMetadata ?: ($this->getDataValue('metadata', [])); 
}
 /** * Getspecified DataValue */ 
    public function getMetadataValue(string $key, mixed $default = null): mixed 
{
 $metadata = $this->getResponseMetadata(); return $metadata[$key] ?? $default; 
}
 /** * check whether HaveResponseMessage. */ 
    public function hasResponseMessage(): bool 
{
 return ! empty($this->getResponseMessage()); 
}
 /** * check whether yes TextResponse. */ 
    public function isTextResponse(): bool 
{
 return $this->getResponseType() === 'text'; 
}
 /** * check whether yes ErrorResponse. */ 
    public function isErrorResponse(): bool 
{
 return $this->getResponseType() === 'error'; 
}
 /** * Convert toArrayincluding AgentField. */ 
    public function toArray(): array 
{
 $baseArray = parent::toArray(); return array_merge($baseArray, [ 'agent_id' => $this->getAgentId(), 'session_id' => $this->getSessionId(), 'message_id' => $this->getMessageId(), 'response_message' => $this->getResponseMessage(), 'response_type' => $this->getResponseType(), 'response_metadata' => $this->getResponseMetadata(), ]); 
}
 /** * Parse AgentData. */ 
    private function parseAgentData(array $data): void 
{
 if (isset($data['agent_id'])) 
{
 $this->agentId = $data['agent_id']; 
}
 if (isset($data['session_id'])) 
{
 $this->sessionId = $data['session_id']; 
}
 if (isset($data['message_id'])) 
{
 $this->messageId = $data['message_id']; 
}
 if (isset($data['response'])) 
{
 $this->responseMessage = $data['response']; 
}
 if (isset($data['response_type'])) 
{
 $this->responseType = $data['response_type']; 
}
 if (isset($data['metadata']) && is_array($data['metadata'])) 
{
 $this->responseMetadata = $data['metadata']; 
}
 
}
 
}
 
