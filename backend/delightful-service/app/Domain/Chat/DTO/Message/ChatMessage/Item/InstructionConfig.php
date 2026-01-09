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
 * fingercommandconfigurationactualbodycategory,according to proto definition.
 */
class InstructionConfig extends AbstractEntity
{
    /**
     * fingercommandcontent.
     */
    protected string $content = '';

    /**
     * fingercommanddescription.
     */
    protected string $description = '';

    /**
     * fingercommandproperty,1 normalfingercommand 2 systemfingercommand.
     */
    protected int $displayType = InstructionDisplayType::Normal->value;

    /**
     * fingercommandID.
     */
    protected string $id = '';

    /**
     * fingercommandinsertposition,1 messagecontentfrontside,2 messagecontentmiddlecursorposition,3 messagecontentbackside.
     */
    protected int $insertLocation = InstructionInsertLocation::Cursor->value;

    /**
     * fingercommandtype, getvalue 1 forprocessfingercommand,getvalue 2 forconversationfingercommand,defaultfor conversationfingercommand.
     */
    protected int $instructionType = InstructionType::Conversation->value;

    /**
     * fingercommandname.
     */
    protected string $name = '';

    /**
     * directlysendfingercommand,userpointhitfingercommandbackwilldirectlysendgiveassistant.
     */
    protected bool $sendDirectly = false;

    /**
     * fingercommandgroupitemtype,1 singleoption 2 switch 3 texttype 4 statustype.
     */
    protected int $type = InstructionComponentType::Radio->value;

    /**
     * fingercommandvalue.
     *
     * @var InstructionValue[]
     */
    protected array $values = [];

    /**
     * switchopenstatustextdescription.
     */
    protected string $on = '';

    /**
     * switchclosestatustextdescription.
     */
    protected string $off = '';

    /**
     * residentfingercommand,defaultonlyread.
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
        // ensure display_type isintegertype
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
        // ensure insert_location isintegertype
        $this->insertLocation = is_numeric($insertLocation) ? (int) $insertLocation : InstructionInsertLocation::Cursor->value;
    }

    public function getInstructionType(): int
    {
        return $this->instructionType;
    }

    public function setInstructionType($instructionType): void
    {
        // ensure instruction_type isintegertype
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
        // ensure send_directly isbooleantype
        $this->sendDirectly = filter_var($sendDirectly, FILTER_VALIDATE_BOOLEAN);
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function setType($type): void
    {
        // ensure type isintegertype
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
     * @param null|array $values originalvaluearrayor InstructionValue objectarray,or null
     */
    public function setValues($values): void
    {
        // process null value
        if ($values === null) {
            $this->values = [];
            return;
        }

        // ensure $values isarray
        if (! is_array($values)) {
            $this->values = [];
            return;
        }

        // processfingercommandvaluearray
        if (empty($values)) {
            $this->values = [];
            return;
        }

        // iffirstyuanelementalreadyalreadyis InstructionValue object,thendirectlyuse
        if (isset($values[0]) && $values[0] instanceof InstructionValue) {
            $this->values = $values;
            return;
        }

        // nothen,willeachyuanelementconvertfor InstructionValue object
        $processedValues = [];
        foreach ($values as $value) {
            $processedValues[] = new InstructionValue($value);
        }
        $this->values = $processedValues;
    }

    /**
     * getswitchopenstatustextdescription.
     */
    public function getOn(): string
    {
        return $this->on;
    }

    /**
     * setswitchopenstatustextdescription.
     * @param mixed $on
     */
    public function setOn($on): void
    {
        $this->on = (string) $on;
    }

    /**
     * getswitchclosestatustextdescription.
     */
    public function getOff(): string
    {
        return $this->off;
    }

    /**
     * setswitchclosestatustextdescription.
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
     * according tofingercommandgroupitemtypegettoshouldnameandvalue.
     *
     * typeforswitcho clock,name getis open/close,value get $instruction->getOn / $instruction->getOff
     * typeforsingle-selecto clock, name getis displayname,value:$instructionValue
     * typeforstatusbuttono clock,name getisstatustext,value: $instructionValue
     * default name forempty, value = $instructionValue
     *
     * @param string $instructionValue fingercommandvalue
     * @return array returncontain name and value array
     */
    public function getNameAndValueByType(string $instructionValue): array
    {
        $name = '';
        $value = $instructionValue;

        switch ($this->type) {
            case InstructionComponentType::Switch->value:
                // switchtype
                $name = $value;
                $value = ($instructionValue === 'on') ? $this->getOn() : $this->getOff();
                break;
            case InstructionComponentType::Radio->value:
                // single-selecttype
                // findtoshould InstructionValue object
                foreach ($this->values as $instructionValueObj) {
                    if ($instructionValueObj->getId() === $instructionValue || $instructionValueObj->getValue() === $value) {
                        $name = $instructionValueObj->getName();
                        $value = $instructionValueObj->getValue();
                        break;
                    }
                }
                break;
            case InstructionComponentType::Status->value:
                // statusbuttontype
                // findtoshould InstructionValue object
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
