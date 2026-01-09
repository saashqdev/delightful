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
 * finger令configuration实bodycategory，according to proto definition.
 */
class InstructionConfig extends AbstractEntity
{
    /**
     * finger令content.
     */
    protected string $content = '';

    /**
     * finger令description.
     */
    protected string $description = '';

    /**
     * finger令property，1 普通finger令 2 系统finger令.
     */
    protected int $displayType = InstructionDisplayType::Normal->value;

    /**
     * finger令ID.
     */
    protected string $id = '';

    /**
     * finger令插入position，1 messagecontentfront方，2 messagecontentmiddle光标position，3 messagecontentback方.
     */
    protected int $insertLocation = InstructionInsertLocation::Cursor->value;

    /**
     * finger令type, 取value 1 为processfinger令，取value 2 为conversationfinger令，default为 conversationfinger令。
     */
    protected int $instructionType = InstructionType::Conversation->value;

    /**
     * finger令name.
     */
    protected string $name = '';

    /**
     * 直接sendfinger令，userpoint击finger令back将直接send给助理.
     */
    protected bool $sendDirectly = false;

    /**
     * finger令groupitemtype，1 单option 2 开关 3 文本type 4 statustype.
     */
    protected int $type = InstructionComponentType::Radio->value;

    /**
     * finger令value.
     *
     * @var InstructionValue[]
     */
    protected array $values = [];

    /**
     * 开关openstatus的文本description.
     */
    protected string $on = '';

    /**
     * 开关closestatus的文本description.
     */
    protected string $off = '';

    /**
     * 常驻finger令，default只读.
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
        // ensure display_type 是整数type
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
        // ensure insert_location 是整数type
        $this->insertLocation = is_numeric($insertLocation) ? (int) $insertLocation : InstructionInsertLocation::Cursor->value;
    }

    public function getInstructionType(): int
    {
        return $this->instructionType;
    }

    public function setInstructionType($instructionType): void
    {
        // ensure instruction_type 是整数type
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
        // ensure send_directly 是布尔type
        $this->sendDirectly = filter_var($sendDirectly, FILTER_VALIDATE_BOOLEAN);
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function setType($type): void
    {
        // ensure type 是整数type
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
     * @param null|array $values originalvaluearrayor InstructionValue objectarray，or null
     */
    public function setValues($values): void
    {
        // process null value
        if ($values === null) {
            $this->values = [];
            return;
        }

        // ensure $values 是array
        if (! is_array($values)) {
            $this->values = [];
            return;
        }

        // processfinger令valuearray
        if (empty($values)) {
            $this->values = [];
            return;
        }

        // iffirstyuan素已经是 InstructionValue object，then直接use
        if (isset($values[0]) && $values[0] instanceof InstructionValue) {
            $this->values = $values;
            return;
        }

        // 否then，将eachyuan素convert为 InstructionValue object
        $processedValues = [];
        foreach ($values as $value) {
            $processedValues[] = new InstructionValue($value);
        }
        $this->values = $processedValues;
    }

    /**
     * get开关openstatus的文本description.
     */
    public function getOn(): string
    {
        return $this->on;
    }

    /**
     * set开关openstatus的文本description.
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
     * according tofinger令groupitemtypeget对应的name和value.
     *
     * type为开关o clock，name 取is 开/关，value 取 $instruction->getOn / $instruction->getOff
     * type为单选o clock, name 取is 显示name，value：$instructionValue
     * type为status按钮o clock，name 取isstatus文本，value: $instructionValue
     * default name 为空， value = $instructionValue
     *
     * @param string $instructionValue finger令value
     * @return array returncontain name 和 value 的array
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
