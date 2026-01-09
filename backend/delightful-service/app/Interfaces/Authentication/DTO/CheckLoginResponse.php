<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Authentication\DTO;

use App\Infrastructure\Core\AbstractDTO;

/**
 * Login response DTO.
 */
class CheckLoginResponse extends AbstractDTO
{
    /**
     * Status code
     */
    protected int $code = 1000;

    /**
     * Message.
     */
    protected string $message = 'Request successful';

    /**
     * Return data.
     */
    protected array $data;

    /**
     * Set return data.
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * Get return data.
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Set status code
     */
    public function setCode(int $code): void
    {
        $this->code = $code;
    }

    /**
     * Get status code
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * Set message.
     */
    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    /**
     * Get message.
     */
    public function getMessage(): string
    {
        return $this->message;
    }
}
