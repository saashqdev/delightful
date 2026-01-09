<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\DTO\Message\ChatMessage\Item;

use App\Domain\Chat\Entity\AbstractEntity;
use App\Domain\Chat\Entity\ValueObject\InstructionComponentType;
use App\Domain\Chat\Entity\ValueObject\InstructionDisplayType;
use App\Domain\Chat\Entity\ValueObject\InstructionInsertLocation;
use App\Domain\Chat\Entity\ValueObject\InstructionType;

/**
 * 指令configuration实体类，according to proto 定义.
 */
class InstructionConfig extends AbstractEntity
{
    /**
     * 指令content.
     */
    protected string $content = '';

    /**
     * 指令description.
     */
    protected string $description = '';

    /**
     * 指令property，1 普通指令 2 系统指令.
     */
    protected int $displayType = InstructionDisplayType::Normal->value;

    /**
     * 指令ID.
     */
    protected string $id = '';

    /**
     * 指令插入位置，1 messagecontent前方，2 messagecontent中光标位置，3 messagecontent后方.
     */
    protected int $insertLocation = InstructionInsertLocation::Cursor->value;

    /**
     * 指令type, 取value 1 为process指令，取value 2 为conversation指令，默认为 conversation指令。
     */
    protected int $instructionType = InstructionType::Conversation->value;

    /**
     * 指令name.
     */
    protected string $name = '';

    /**
     * 直接发送指令，user点击指令后将直接发送给助理.
     */
    protected bool $sendDirectly = false;

    /**
     * 指令组件type，1 单选项 2 开关 3 文本type 4 statustype.
     */
    protected int $type = InstructionComponentType::Radio->value;

    /**
     * 指令value.
     *
     * @var InstructionValue[]
     */
    protected array $values = [];

    /**
     * 开关打开status的文本description.
     */
    protected string $on = '';

    /**
     * 开关closestatus的文本description.
     */
    protected string $off = '';

    /**
     * 常驻指令，默认只读.
     */
    protected bool $residency = true;

    protected bool $switch_off = false;

    protected bool $switch_on = false;

    protected string $defaultValue = '';

    public function __construct(array $instruction)
    {
        parent::__construct($instruction);
    }

    public function isFlowInstructionType(): bool
    {
        return $this->instructionType === InstructionType::Flow->value;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getDisplayType(): int
    {
        return $this->displayType;
    }

    public function setDisplayType($displayType): void
    {
        // 确保 display_type 是整数type
        $this->displayType = is_numeric($displayType) ? (int) $displayType : InstructionDisplayType::Normal->value;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getInsertLocation(): int
    {
        return $this->insertLocation;
    }

    public function setInsertLocation($insertLocation): void
    {
        // 确保 insert_location 是整数type
        $this->insertLocation = is_numeric($insertLocation) ? (int) $insertLocation : InstructionInsertLocation::Cursor->value;
    }

    public function getInstructionType(): int
    {
        return $this->instructionType;
    }

    public function setInstructionType($instructionType): void
    {
        // 确保 instruction_type 是整数type
        $this->instructionType = is_numeric($instructionType) ? (int) $instructionType : InstructionType::Conversation->value;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function isSendDirectly(): bool
    {
        return $this->sendDirectly;
    }

    public function setSendDirectly($sendDirectly): void
    {
        // 确保 send_directly 是布尔type
        $this->sendDirectly = filter_var($sendDirectly, FILTER_VALIDATE_BOOLEAN);
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function setType($type): void
    {
        // 确保 type 是整数type
        $this->type = is_numeric($type) ? (int) $type : InstructionComponentType::Radio->value;
    }

    /**
     * @return InstructionValue[]
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * @param null|array $values 原始valuearray或 InstructionValue objectarray，或 null
     */
    public function setValues($values): void
    {
        // 处理 null value
        if ($values === null) {
            $this->values = [];
            return;
        }

        // 确保 $values 是array
        if (! is_array($values)) {
            $this->values = [];
            return;
        }

        // 处理指令valuearray
        if (empty($values)) {
            $this->values = [];
            return;
        }

        // 如果第一个元素已经是 InstructionValue object，则直接use
        if (isset($values[0]) && $values[0] instanceof InstructionValue) {
            $this->values = $values;
            return;
        }

        // 否则，将每个元素转换为 InstructionValue object
        $processedValues = [];
        foreach ($values as $value) {
            $processedValues[] = new InstructionValue($value);
        }
        $this->values = $processedValues;
    }

    /**
     * get开关打开status的文本description.
     */
    public function getOn(): string
    {
        return $this->on;
    }

    /**
     * set开关打开status的文本description.
     * @param mixed $on
     */
    public function setOn($on): void
    {
        $this->on = (string) $on;
    }

    /**
     * get开关closestatus的文本description.
     */
    public function getOff(): string
    {
        return $this->off;
    }

    /**
     * set开关closestatus的文本description.
     * @param mixed $off
     */
    public function setOff($off): void
    {
        $this->off = (string) $off;
    }

    public function setResidency(bool $residency): void
    {
        $this->residency = $residency;
    }

    public function getResidency(): bool
    {
        return $this->residency;
    }

    public function getSwitchOff(): bool
    {
        return $this->switch_off;
    }

    public function getSwitchOn(): bool
    {
        return $this->switch_on;
    }

    public function setSwitchOff(bool $switch_off): void
    {
        $this->switch_off = $switch_off;
    }

    public function setSwitchOn(bool $switch_on): void
    {
        $this->switch_on = $switch_on;
    }

    public function getDefaultValue(): string
    {
        return $this->defaultValue;
    }

    public function setDefaultValue(mixed $defaultValue): void
    {
        $this->defaultValue = is_string($defaultValue) ? $defaultValue : '';
    }

    /**
     * according to指令组件typeget对应的name和value.
     *
     * type为开关时，name 取的是 开/关，value 取 $instruction->getOn / $instruction->getOff
     * type为单选时, name 取的是 显示name，value：$instructionValue
     * type为status按钮时，name 取的是status文本，value: $instructionValue
     * 默认 name 为空， value = $instructionValue
     *
     * @param string $instructionValue 指令value
     * @return array return包含 name 和 value 的array
     */
    public function getNameAndValueByType(string $instructionValue): array
    {
        $name = '';
        $value = $instructionValue;

        switch ($this->type) {
            case InstructionComponentType::Switch->value:
                // 开关type
                $name = $value;
                $value = ($instructionValue === 'on') ? $this->getOn() : $this->getOff();
                break;
            case InstructionComponentType::Radio->value:
                // 单选type
                // 查找对应的 InstructionValue object
                foreach ($this->values as $instructionValueObj) {
                    if ($instructionValueObj->getId() === $instructionValue || $instructionValueObj->getValue() === $value) {
                        $name = $instructionValueObj->getName();
                        $value = $instructionValueObj->getValue();
                        break;
                    }
                }
                break;
            case InstructionComponentType::Status->value:
                // status按钮type
                // 查找对应的 InstructionValue object
                foreach ($this->values as $instructionValueObj) {
                    if ($instructionValueObj->getValue() === $instructionValue) {
                        $name = $instructionValueObj->getName();
                        break;
                    }
                }
                break;
        }

        return [
            'name' => $name,
            'value' => $value,
        ];
    }
}
