<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject;

/**
 * Initialization metadata DTO.
 * Used to encapsulate metadata configuration when initializing Agent, convenient for future expansion.
 */
class InitializationMetadataDTO
{
    /**
     * Constructor.
     *
     * @param ?bool $skipInitMessages Whether to skip initialization messages, used for ASR scenarios
     * @param ?string $authorization Authorization information
     */
    public function __construct(
        private ?bool $skipInitMessages = null,
        private ?string $authorization = null
    ) {
    }

    /**
     * Create default instance.
     */
    public static function createDefault(): self
    {
        return new self();
    }

    /**
     * Get whether to skip initialization messages.
     *
     * @return ?bool Whether to skip initialization messages
     */
    public function getSkipInitMessages(): ?bool
    {
        return $this->skipInitMessages;
    }

    /**
     * Set whether to skip initialization messages.
     *
     * @param ?bool $skipInitMessages Whether to skip initialization messages
     * @return self New instance
     */
    public function withSkipInitMessages(?bool $skipInitMessages): self
    {
        $clone = clone $this;
        $clone->skipInitMessages = $skipInitMessages;
        return $clone;
    }

    /**
     * Get authorization information.
     *
     * @return ?string Authorization information
     */
    public function getAuthorization(): ?string
    {
        return $this->authorization;
    }

    /**
     * Set authorization information.
     *
     * @param ?string $authorization Authorization information
     * @return self New instance
     */
    public function withAuthorization(?string $authorization): self
    {
        $clone = clone $this;
        $clone->authorization = $authorization;
        return $clone;
    }
}
