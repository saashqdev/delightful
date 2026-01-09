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
     * fromtypevaluegetsystemfingercommandtypeinstance.
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
     * getsystemfingercommandtypeoption.
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
     * getsystemfingercommandtoshouldgraphmark.
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
     * getdefaultsysteminteractionfingercommandconfiguration.
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
     * get havesystemfingercommandtypevalue.
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
     * judgesystemfingercommandtypewhetherneedcontentfield.
     */
    public static function requiresContent(int $type): bool
    {
        // itemfront havesystemfingercommandallnotneedcontent
        // ifnotcomehavesystemfingercommandneedcontent,caninthiswithinaddjudge
        return match (self::fromType($type)) {
            self::EMOJI, self::FILE, self::NEW_TOPIC, self::SCHEDULE, self::RECORD => false,
        };
    }

    /**
     * ensuresysteminteractionfingertoken storagein,ifmissingthensupplement.
     * @return array returnsupplementbackfingercommandarray
     */
    public static function ensureSystemInstructs(array $instructs): array
    {
        $hasSystemGroup = false;
        $systemTypes = [];
        $toolbarGroupIndex = null;
        $toolbarGroup = null;

        // findtool栏groupandshowhavesystemfingercommand
        foreach ($instructs as $index => $group) {
            if (isset($group['position']) && $group['position'] === InstructGroupPosition::TOOLBAR->value) {
                $hasSystemGroup = true;
                $toolbarGroupIndex = $index;
                $toolbarGroup = $group;
                break;
            }
        }

        // ifnothavetool栏group,createonenew
        if (! $hasSystemGroup) {
            $toolbarGroup = [
                'id' => (string) IdGenerator::getSnowId(),
                'position' => InstructGroupPosition::TOOLBAR->value,
                'items' => [],
            ];
        }

        // minute离systemfingercommandandnonsystemfingercommand
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

        // checkmissingsystemfingercommandtypeandsupplement
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

        // 按enumdefinitionordersortsystemfingercommand
        ksort($systemInstructs);

        // 重newgroup合tool栏group items,systemfingercommandinfront
        $toolbarGroup['items'] = array_merge(array_values($systemInstructs), $normalInstructs);

        // updateoraddtool栏group
        if ($toolbarGroupIndex !== null) {
            $instructs[$toolbarGroupIndex] = $toolbarGroup;
        } else {
            $instructs[] = $toolbarGroup;
        }

        return $instructs;
    }
}
