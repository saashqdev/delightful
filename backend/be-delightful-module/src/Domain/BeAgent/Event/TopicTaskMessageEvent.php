<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Event;

use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\MessageMetadata;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\MessagePayload;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\TokenUsageDetails;

/**
 * Topic task message event.
 */
class TopicTaskMessageEvent extends AbstractEvent
{
    /**
     * Constructor.
     *
     * @param MessageMetadata $metadata Message metadata
     * @param MessagePayload $payload Message payload
     * @param null|TokenUsageDetails $tokenUsageDetails Token usage details
     */
    public function __construct(
        private MessageMetadata $metadata,
        private MessagePayload $payload,
        private ?TokenUsageDetails $tokenUsageDetails = null,
    ) {
        // Call parent constructor to generate snowflake ID
        parent::__construct();
    }

    /**
     * Create message event from array.
     *
     * @param array $data Message data array
     */
    public static function fromArray(array $data): self
    {
        $metadata = isset($data['metadata']) && is_array($data['metadata'])
            ? MessageMetadata::fromArray($data['metadata'])
            : new MessageMetadata();

        $payload = isset($data['payload']) && is_array($data['payload'])
            ? MessagePayload::fromArray($data['payload'])
            : new MessagePayload();

        $tokenUsageDetails = isset($data['token_usage_details']) && is_array($data['token_usage_details'])
            ? TokenUsageDetails::fromArray($data['token_usage_details'])
            : null;

        return new self($metadata, $payload, $tokenUsageDetails);
    }

    /**
     * Convert to array.
     *
     * @return array Message data array
     */
    public function toArray(): array
    {
        return [
            'metadata' => $this->metadata->toArray(),
            'payload' => $this->payload->toArray(),
            'token_usage_details' => $this->tokenUsageDetails?->toArray(),
        ];
    }

    /**
     * Get message metadata.
     */
    public function getMetadata(): MessageMetadata
    {
        return $this->metadata;
    }

    /**
     * Get message payload.
     */
    public function getPayload(): MessagePayload
    {
        return $this->payload;
    }

    public function getTokenUsageDetails(): ?TokenUsageDetails
    {
        return $this->tokenUsageDetails;
    }
}
