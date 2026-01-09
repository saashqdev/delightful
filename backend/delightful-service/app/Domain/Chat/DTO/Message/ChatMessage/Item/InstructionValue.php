<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\DTO\Message\ChatMessage\Item;

use App\Domain\Chat\Entity\AbstractEntity;

/**
 * instructionoptionvalue实bodycategory,according to proto definition.
 */
class InstructionValue extends AbstractEntity
{
    /**
     * optionID.
     */
    protected string $id = '';

    /**
     * optiondisplayname.
     */
    protected string $name = '';

    /**
     * optionvalue.
     */
    protected string $value = '';

    public function __construct(array $value)
    {
        parent::__construct($value);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }
}
