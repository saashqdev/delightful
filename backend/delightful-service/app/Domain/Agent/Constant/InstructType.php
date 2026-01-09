<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Agent\Constant;

use App\ErrorCode\AgentErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use Exception;

use function Hyperf\Translation\__;

enum InstructType: int
{
    case SINGLE_CHOICE = 1;  // singleoption
    case SWITCH = 2;         // switch
    case TEXT = 3;          // texttype
    case STATUS = 4;        // statustype

    /**
     * gettypeinstance.
     */
    public static function fromType(int $type): self
    {
        return match ($type) {
            self::SINGLE_CHOICE->value => self::SINGLE_CHOICE,
            self::SWITCH->value => self::SWITCH,
            self::TEXT->value => self::TEXT,
            self::STATUS->value => self::STATUS,
            default => ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, 'agent.interaction_command_type_invalid'),
        };
    }

    /**
     * get所havefinger令typeandits国际化tag.
     * @return array<string, int> returntypenameandto应value
     */
    public static function getTypeOptions(): array
    {
        return [
            __('agent.instruct_type_single_choice') => self::SINGLE_CHOICE->value,
            __('agent.instruct_type_switch') => self::SWITCH->value,
            __('agent.instruct_type_text') => self::TEXT->value,
            __('agent.instruct_type_status_button') => self::STATUS->value,
        ];
    }

    /**
     * verifyfinger令value
     */
    public function validate(array &$items): void
    {
        // othertypeverify
        match ($this) {
            self::SINGLE_CHOICE => $this->validateSingleChoice($items),
            self::SWITCH => $this->validateSwitch($items),
            self::TEXT => $this->validateText($items),
            self::STATUS => $this->validateStatusGroup($items),
        };
    }

    /**
     * judgefinger令typewhetherneedcontentfield.
     */
    public static function requiresContent(int $type, ?int $displayType = null, ?int $instructionType = null): bool
    {
        // ifisprocessfinger令,thennotcanconfigurationfinger令content
        if ($instructionType == InstructCategory::FLOW) {
            return false;
        }

        // ifissystemfinger令,useSystemInstructTypejudge
        if ($displayType === InstructDisplayType::SYSTEM) {
            return SystemInstructType::requiresContent($type);
        }

        // 普通finger令judge
        return match (self::fromType($type)) {
            self::STATUS => false,  // statustypenotneedcontent
            self::SINGLE_CHOICE, self::SWITCH, self::TEXT => true,  // othertypeneedcontent
        };
    }

    /**
     * verify普通交互finger令item.
     */
    public static function validateInstructItem(array &$item, array &$seenOuterNames): void
    {
        if (isset($item['display_type'])) {
            if ($item['display_type'] !== InstructDisplayType::SYSTEM) {
                ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, 'agent.instruct_display_type_invalid');
            }
            SystemInstructType::fromType((int) $item['type']);
        } else {
            if (! isset($item['name']) || trim($item['name']) === '') {
                ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, 'agent.interaction_command_name_cannot_be_empty');
            }

            if (in_array($item['name'], $seenOuterNames, true)) {
                ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, 'agent.interaction_command_name_cannot_be_duplicated');
            }
            $seenOuterNames[] = $item['name'];
            if (! isset($item['type'])) {
                ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, 'agent.interaction_command_type_cannot_be_empty');
            }

            if (! is_numeric($item['type'])) {
                ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, 'agent.interaction_command_type_must_be_numeric');
            }
        }

        // verifycontentfield
        $instructionType = isset($item['instruction_type']) ? (int) $item['instruction_type'] : 0;
        if (self::requiresContent((int) $item['type'], $item['display_type'] ?? null, $instructionType)) {
            if (! isset($item['content']) || preg_match('/^\s*$/', $item['content'])) {
                ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, 'agent.interaction_command_content_cannot_be_empty');
            }
        }

        // processfinger令notcanconfiguration sendfinger令detect
        if ($instructionType == InstructCategory::FLOW && isset($item['send_directly']) && $item['send_directly']) {
            ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, 'agent.send_directly_only_allow_flow_instruction');
        }

        if (! isset($item['id']) || $item['id'] === '') {
            $item['id'] = (string) IdGenerator::getSnowId();
        }

        // ifis普通finger令,verifytype
        if (! isset($item['display_type'])) {
            self::fromType((int) $item['type'])->validate($item);
        }
    }

    /**
     * verify普通交互finger令group.
     */
    public static function validateInstructs(array &$instructs): void
    {
        foreach ($instructs as &$group) {
            // verifygrouptype
            if (! isset($group['position']) || ! isset($group['items'])) {
                ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, 'agent.instruct_group_format_invalid');
            }

            // verifygrouptypewhethervalid
            if (! is_numeric($group['position'])) {
                ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, 'agent.instruct_group_type_must_be_numeric');
            }
            InstructGroupPosition::fromPosition((int) $group['position']);

            // verifyfinger令quantity
            if (! is_array($group['items']) || (count($group['items']) - count(SystemInstructType::getTypeOptions())) > InstructGroupPosition::MAX_INSTRUCTS) {
                ExceptionBuilder::throw(
                    AgentErrorCode::VALIDATE_FAILED,
                    'agent.instruct_group_exceeds_max_limit',
                    ['max' => InstructGroupPosition::MAX_INSTRUCTS]
                );
            }
            $seenOuterNames = [];
            foreach ($group['items'] as &$item) {
                self::validateInstructItem($item, $seenOuterNames);
            }
            if (! isset($group['id']) || $group['id'] === '') {
                $group['id'] = (string) IdGenerator::getSnowId();
            }
        }
    }

    /**
     * securityverifyfinger令,catchexceptionandreturnresult.
     * @return array{success: bool, message: null|string}
     */
    public static function safeValidateInstructs(array &$instructs): array
    {
        try {
            self::validateInstructs($instructs);
            return ['success' => true, 'message' => null];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * verifystatustypegroup.
     */
    private function validateStatusGroup(array &$items): void
    {
        if (! isset($items['values']) || ! is_array($items['values'])) {
            ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, 'agent.interaction_command_status_items_invalid');
        }

        $totalItems = count($items['values']);
        // verifystatusitemmostsmallquantity
        if ($totalItems < 2) {
            ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, 'agent.interaction_command_status_items_min_count');
        }

        // verifydefaultvalue
        if (! isset($items['default_value'])) {
            ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, 'agent.interaction_command_status_default_value_required');
        }

        if (! is_int($items['default_value']) || $items['default_value'] < 1 || $items['default_value'] > $totalItems) {
            ExceptionBuilder::throw(
                AgentErrorCode::VALIDATE_FAILED,
                'agent.interaction_command_status_default_value_invalid',
                ['min' => 1, 'max' => $totalItems]
            );
        }

        // verifystatusitem
        foreach ($items['values'] as &$item) {
            // ensureeachstatusitemallhaveID
            if (! isset($item['id'])) {
                $item['id'] = (string) IdGenerator::getSnowId();
            }

            // verifyeachstatusitem
            $this->validateStatus($item);
        }
    }

    /**
     * verifysingleoptiontype.
     */
    private function validateSingleChoice(array &$item): void
    {
        if (! array_key_exists('values', $item) || ! is_array($item['values']) || count($item['values']) === 0) {
            ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, 'agent.interaction_command_select_cannot_be_empty');
        }

        // verifywhether直接sendfinger令
        if (! isset($item['send_directly'])) {
            $item['send_directly'] = false;  // defaultnot直接send
        }

        if (! is_bool($item['send_directly'])) {
            ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, 'agent.interaction_command_send_directly_must_be_boolean');
        }

        $seenValues = [];
        foreach ($item['values'] as &$value) {
            if (! is_array($value) || ! isset($value['name'], $value['value'])) {
                ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, 'agent.interaction_command_value_invalid_format');
            }

            if (preg_match('/^\s*$/', $value['name']) || preg_match('/^\s*$/', $value['value'])) {
                ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, 'agent.interaction_command_value_cannot_be_empty');
            }

            if (in_array($value['name'], $seenValues, true)) {
                ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, 'agent.interaction_command_value_cannot_be_duplicated_within_group');
            }
            $seenValues[] = $value['name'];
            if (! isset($value['id']) || $value['id'] === '') {
                $value['id'] = (string) IdGenerator::getSnowId();
            }
        }
    }

    /**
     * verifyswitchtype.
     */
    private function validateSwitch(array &$item): void
    {
        // verifymust存in on and off field
        if (! array_key_exists('on', $item) || ! array_key_exists('off', $item)) {
            ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, 'agent.interaction_command_switch_fields_missing');
        }

        // verifydefaultvaluemust存inandmust 'on' or 'off'
        if (! isset($item['default_value']) || ! in_array($item['default_value'], ['on', 'off'], true)) {
            ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, 'agent.interaction_command_switch_default_value_invalid');
        }
    }

    /**
     * verifytexttype.
     */
    private function validateText(array &$item): void
    {
        // verifywhether直接sendfinger令
        if (! isset($item['send_directly'])) {
            $item['send_directly'] = false;  // defaultnot直接send
        }

        if (! is_bool($item['send_directly'])) {
            ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, 'agent.interaction_command_send_directly_must_be_boolean');
        }
    }

    /**
     * verifystatustype.
     */
    private function validateStatus(array &$item): void
    {
        // verifygraph标
        if (! isset($item['icon'])) {
            ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, 'agent.interaction_command_status_icon_required');
        }

        // use StatusIcon 枚举verifygraph标value
        if (! StatusIcon::isValid($item['icon'])) {
            ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, 'agent.interaction_command_status_icon_invalid');
        }

        // verifystatustext
        if (! isset($item['status_text']) || empty($item['status_text'])) {
            ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, 'agent.interaction_command_status_text_required');
        }

        // verifytextcolor
        if (! isset($item['text_color'])) {
            ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, 'agent.interaction_command_status_color_required');
        }

        // use TextColor 枚举verifycolorvalue
        if (! TextColor::isValid($item['text_color'])) {
            ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, 'agent.interaction_command_status_color_invalid');
        }

        // verifyfinger令value
        if (! isset($item['value']) || empty($item['value'])) {
            ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, 'agent.interaction_command_status_value_required');
        }
    }
}
