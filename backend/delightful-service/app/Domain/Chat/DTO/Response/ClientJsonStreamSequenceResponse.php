<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\DTO\Response;

use App\Domain\Chat\DTO\Message\StreamMessage\StreamMessageStatus;
use App\Domain\Chat\DTO\Message\StreamMessage\StreamOptions;
use App\Domain\Chat\Entity\AbstractEntity;
use Hyperf\Codec\Json;

/**
 * todo forcompatibleoldversionstreammessage,needwill content/reasoning_content/status fieldputtomostoutsidelayer.
 */
class ClientJsonStreamSequenceResponse extends AbstractEntity
{
    // wantupdategoal seqId content
    protected string $targetSeqId;

    // forimplement丢packageretransmit,needrecordcurrent $streamId.onesetsingleincrement.
    protected ?int $streamId;

    /**
     * big json streampush
     */
    protected array $streams;

    protected ?StreamOptions $streamOptions;

    /**
     * @deprecated forcompatibleoldversionstreammessage,needwill content/reasoning_content/status/llm_response fieldputtomostoutsidelayer
     */
    protected ?string $content;

    /**
     * @deprecated forcompatibleoldversionstreammessage,needwill content/reasoning_content/status/llm_response fieldputtomostoutsidelayer
     */
    protected ?string $reasoningContent;

    /**
     * @deprecated forcompatibleoldversionstreammessage,needwill content/reasoning_content/status/llm_response fieldputtomostoutsidelayer
     */
    protected ?int $status;

    /**
     * @deprecated forcompatibleoldversionstreammessage,needwill content/reasoning_content/status/llm_response fieldputtomostoutsidelayer
     */
    protected ?string $llmResponse;

    public function getContent(): ?string
    {
        return $this->content ?? null;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getReasoningContent(): ?string
    {
        return $this->reasoningContent ?? null;
    }

    public function setReasoningContent(?string $reasoningContent): self
    {
        $this->reasoningContent = $reasoningContent;
        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status ?? null;
    }

    public function setStatus(null|int|StreamMessageStatus $status): self
    {
        if ($status instanceof StreamMessageStatus) {
            $status = $status->value;
        }
        $this->status = $status;
        return $this;
    }

    public function getTargetSeqId(): string
    {
        return $this->targetSeqId;
    }

    public function setTargetSeqId(string $targetSeqId): self
    {
        $this->targetSeqId = $targetSeqId;
        return $this;
    }

    public function getStreamId(): ?int
    {
        return $this->streamId;
    }

    public function setStreamId(?int $streamId): self
    {
        $this->streamId = $streamId;
        return $this;
    }

    public function getStreams(): array
    {
        return $this->streams;
    }

    public function setStreams(array $streams): self
    {
        $this->streams = $streams;
        return $this;
    }

    public function getStreamOptions(): ?StreamOptions
    {
        return $this->streamOptions ?? null;
    }

    public function setStreamOptions(?StreamOptions $streamOptions): self
    {
        $this->streamOptions = $streamOptions;
        return $this;
    }

    public function getLlmResponse(): ?string
    {
        return $this->llmResponse;
    }

    public function setLlmResponse(?string $llmResponse): self
    {
        $this->llmResponse = $llmResponse;
        return $this;
    }

    public function toArray(bool $filterNull = false): array
    {
        $data = Json::decode($this->toJsonString());
        if ($filterNull) {
            $data = array_filter($data, static fn ($value) => $value !== null && $value !== '');
        }
        return $data;
    }
}
