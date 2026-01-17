<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Infrastructure\ExternalAPI\Sandbox;

class SandboxResult
{
    public const NotFound = 4004;

    public const Normal = 1000;

    public const SandboxRunnig = 'running';

    public const SandboxExited = 'exited';

    public function __construct(
        private bool $success = false,
        private ?string $message = null,
        private ?int $code = 0,
        private ?SandboxData $data = null,
    ) {
        // Initialize empty SandboxData object
        if ($this->data === null) {
            $this->data = new SandboxData();
        }
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function setSuccess(bool $success): self
    {
        $this->success = $success;
        return $this;
    }

    /**
     * Get sandbox ID.
     *
     * @return null|string Sandbox ID
     */
    public function getSandboxId(): ?string
    {
        $sandboxId = $this->data->getSandboxId();
        return empty($sandboxId) ? null : $sandboxId;
    }

    /**
     * Set sandbox ID.
     *
     * @param null|string $sandboxId Sandbox ID
     */
    public function setSandboxId(?string $sandboxId): self
    {
        if ($sandboxId !== null) {
            $this->data->setSandboxId($sandboxId);
        }
        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): self
    {
        $this->message = $message;
        return $this;
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function setCode(int $code): self
    {
        $this->code = $code;
        return $this;
    }

    /**
     * Get sandbox data object
     *
     * @return SandboxData Sandbox data object
     */
    public function getSandboxData(): SandboxData
    {
        return $this->data;
    }

    /**
     * Set sandbox data object
     *
     * @param SandboxData $data Sandbox data object
     */
    public function setSandboxData(SandboxData $data): self
    {
        $this->data = $data;
        return $this;
    }
}
