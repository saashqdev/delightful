<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\DTO\Message\ChatMessage\Item;

use App\Domain\Chat\Entity\AbstractEntity;

/**
 * 聊天instruction实体类，according to proto 定义.
 */
class ChatInstruction extends AbstractEntity
{
    /**
     * instruction值.
     */
    protected string $value = '';

    /**
     * instructiontype.
     */
    protected ?InstructionConfig $instruction = null;

    public function __construct(array $instruction)
    {
        parent::__construct($instruction);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    public function getInstruction(): ?InstructionConfig
    {
        return $this->instruction;
    }

    public function setInstruction(null|array|InstructionConfig $instruction): void
    {
        if (isset($instruction)) {
            if (is_array($instruction)) {
                $this->instruction = new InstructionConfig($instruction);
            /* @phpstan-ignore-next-line */
            } elseif (! $instruction instanceof InstructionConfig) {
                // if不是array也不是 InstructionConfig object，则createonenull的 InstructionConfig object
                $this->instruction = new InstructionConfig([]);
            }
        } else {
            $this->instruction = null;
        }
    }
}
