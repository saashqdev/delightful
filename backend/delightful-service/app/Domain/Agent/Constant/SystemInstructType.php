<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Agent\Constant;

use App\ErrorCode\AgentErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\IdGenerator\IdGenerator;

use function Hyperf\Translation\__;

enum SystemInstructType: int
{
    case EMOJI = 1;
    case FILE = 2;
    case NEW_TOPIC = 3;
    case SCHEDULE = 4;
    case RECORD = 5;

    /**
     * 从typevalueget系统指令type实例.
     */
    public static function fromType(int $type): self
    {
        return match ($type) {
            self::EMOJI->value => self::EMOJI,
            self::FILE->value => self::FILE,
            self::NEW_TOPIC->value => self::NEW_TOPIC,
            self::SCHEDULE->value => self::SCHEDULE,
            self::RECORD->value => self::RECORD,
            default => ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, __('agent.system_instruct_type_invalid')),
        };
    }

    /**
     * get系统指令type选项.
     * @return array<int, mixed>
     */
    public static function getTypeOptions(): array
    {
        return [
            self::EMOJI->value => __('agent.system_instruct_type_emoji'),
            self::FILE->value => __('agent.system_instruct_type_file'),
            self::NEW_TOPIC->value => __('agent.system_instruct_type_new_topic'),
            self::SCHEDULE->value => __('agent.system_instruct_type_schedule'),
            self::RECORD->value => __('agent.system_instruct_type_record'),
        ];
    }

    /**
     * get系统指令对应的图标.
     */
    public function getIcon(): string
    {
        return match ($this) {
            self::EMOJI => 'IconMoodHappy',
            self::FILE => 'IconFileUpload',
            self::NEW_TOPIC => 'IconMessage2Plus',
            self::SCHEDULE => 'IconClockPlay',
            self::RECORD => 'IconMicrophone',
        };
    }

    /**
     * getdefault的系统交互指令configuration.
     */
    public static function getDefaultInstructs(): array
    {
        return [
            [
                'id' => (string) IdGenerator::getSnowId(),
                'position' => InstructGroupPosition::TOOLBAR->value,
                'items' => [
                    [
                        'id' => (string) IdGenerator::getSnowId(),
                        'type' => self::EMOJI->value,
                        'display_type' => InstructDisplayType::SYSTEM,
                        'hidden' => false,
                        'icon' => self::EMOJI->getIcon(),
                    ],
                    [
                        'id' => (string) IdGenerator::getSnowId(),
                        'type' => self::FILE->value,
                        'display_type' => InstructDisplayType::SYSTEM,
                        'hidden' => false,
                        'icon' => self::FILE->getIcon(),
                    ],
                    [
                        'id' => (string) IdGenerator::getSnowId(),
                        'type' => self::NEW_TOPIC->value,
                        'display_type' => InstructDisplayType::SYSTEM,
                        'hidden' => false,
                        'icon' => self::NEW_TOPIC->getIcon(),
                    ],
                    [
                        'id' => (string) IdGenerator::getSnowId(),
                        'type' => self::SCHEDULE->value,
                        'display_type' => InstructDisplayType::SYSTEM,
                        'hidden' => true,
                        'icon' => self::SCHEDULE->getIcon(),
                    ],
                    [
                        'id' => (string) IdGenerator::getSnowId(),
                        'type' => self::RECORD->value,
                        'display_type' => InstructDisplayType::SYSTEM,
                        'hidden' => false,
                        'icon' => self::RECORD->getIcon(),
                    ],
                ],
            ],
        ];
    }

    /**
     * get所有系统指令typevalue.
     * @return array<int>
     */
    public static function getAllTypes(): array
    {
        return [
            self::EMOJI->value,
            self::FILE->value,
            self::NEW_TOPIC->value,
            self::SCHEDULE->value,
            self::RECORD->value,
        ];
    }

    /**
     * 判断系统指令type是否needcontentfield.
     */
    public static function requiresContent(int $type): bool
    {
        // 目前所有系统指令都不needcontent
        // 如果未来有系统指令needcontent，can在这里添加判断
        return match (self::fromType($type)) {
            self::EMOJI, self::FILE, self::NEW_TOPIC, self::SCHEDULE, self::RECORD => false,
        };
    }

    /**
     * ensure系统交互指令存在，如果缺少则补充.
     * @return array return补充后的指令array
     */
    public static function ensureSystemInstructs(array $instructs): array
    {
        $hasSystemGroup = false;
        $systemTypes = [];
        $toolbarGroupIndex = null;
        $toolbarGroup = null;

        // 查找工具栏组和现有的系统指令
        foreach ($instructs as $index => $group) {
            if (isset($group['position']) && $group['position'] === InstructGroupPosition::TOOLBAR->value) {
                $hasSystemGroup = true;
                $toolbarGroupIndex = $index;
                $toolbarGroup = $group;
                break;
            }
        }

        // 如果没有工具栏组，create一个new
        if (! $hasSystemGroup) {
            $toolbarGroup = [
                'id' => (string) IdGenerator::getSnowId(),
                'position' => InstructGroupPosition::TOOLBAR->value,
                'items' => [],
            ];
        }

        // 分离系统指令和非系统指令
        $systemInstructs = [];
        $normalInstructs = [];
        foreach ($toolbarGroup['items'] as $item) {
            if (isset($item['display_type']) && $item['display_type'] === InstructDisplayType::SYSTEM) {
                $systemInstructs[$item['type']] = $item;
                $systemTypes[] = $item['type'];
            } else {
                $normalInstructs[] = $item;
            }
        }

        // check缺失的系统指令type并补充
        foreach (self::cases() as $case) {
            if (! in_array($case->value, $systemTypes)) {
                $systemInstructs[$case->value] = [
                    'id' => (string) IdGenerator::getSnowId(),
                    'type' => $case->value,
                    'display_type' => InstructDisplayType::SYSTEM,
                    'hidden' => false,
                    'icon' => $case->getIcon(),
                ];
            }
        }

        // 按枚举定义顺序sort系统指令
        ksort($systemInstructs);

        // 重新组合工具栏组的 items，系统指令在前
        $toolbarGroup['items'] = array_merge(array_values($systemInstructs), $normalInstructs);

        // update或添加工具栏组
        if ($toolbarGroupIndex !== null) {
            $instructs[$toolbarGroupIndex] = $toolbarGroup;
        } else {
            $instructs[] = $toolbarGroup;
        }

        return $instructs;
    }
}
