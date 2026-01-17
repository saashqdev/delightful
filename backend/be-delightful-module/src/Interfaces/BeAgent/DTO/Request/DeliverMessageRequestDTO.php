<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Interfaces\BeAgent\DTO\Request;

use App\ErrorCode\GenericErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use Hyperf\HttpServer\Contract\RequestInterface;

class DeliverMessageRequestDTO
{
    /**
     * Constructor.
     *
     * @param array $metadata Metadata
     * @param array $payload Message payload
     */
    public function __construct(
        private array $metadata,
        private array $payload
    ) {
    }

    /**
     * Create DTO from HTTP request.
     *
     * @param RequestInterface $request HTTP request
     */
    public static function fromRequest(RequestInterface $request): self
    {
        $requestData = $request->all();
        if (empty($requestData)) {
            ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'message_required');
        }

        // Validate that request contains necessary metadata and payload fields
        if (! isset($requestData['metadata']) || ! isset($requestData['payload'])) {
            ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'metadata_and_payload_required');
        }

        return new self($requestData['metadata'], $requestData['payload']);
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
     *
     * @param array $metadata Metadata
     */
    public function setMetadata(array $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }

    /**
     * Get message payload.
     */
    public function getPayload(): array
    {
        return $this->payload;
    }

    /**
     * Set message payload.
     *
     * @param array $payload Message payload
     */
    public function setPayload(array $payload): self
    {
        $this->payload = $payload;
        return $this;
    }
}
