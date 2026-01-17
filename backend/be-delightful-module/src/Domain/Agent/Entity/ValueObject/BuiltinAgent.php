<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\Agent\Entity\ValueObject;

enum BuiltinAgent: string
{
    /** General mode */
    case General = 'general';

    /** Chat mode */
    case Chat = 'chat';

    /** Data analysis */
    case DataAnalysis = 'data_analysis';

    /** PPT */
    case PPT = 'ppt';

    /** Research report mode */
    case Report = 'report';

    /** Recording summary */
    case Summary = 'summary';

    /**
     * Get built-in agent name.
     */
    public function getName(): string
    {
        return match ($this) {
            self::General => 'General mode',
            self::Chat => 'Chat mode',
            self::DataAnalysis => 'Data analysis',
            self::PPT => 'PPT',
            self::Report => 'Research report mode',
            self::Summary => 'Recording summary',
        };
    }

    /**
     * Get built-in agent description.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::General => 'A smart assistant suitable for various general scenarios',
            self::Chat => 'A smart assistant focused on conversation',
            self::DataAnalysis => 'A professional data analysis and processing assistant',
            self::PPT => 'A professional PPT creation and presentation assistant',
            self::Report => 'A professional research report writing assistant',
            self::Summary => 'A professional recording content summary assistant',
        };
    }

    /**
     * Get built-in agent icon.
     */
    public function getIcon(): array
    {
        return match ($this) {
            self::General => ['type' => 'general', 'color' => ''],
            self::Chat => ['type' => 'IconMessages', 'color' => ''],
            self::DataAnalysis => ['type' => 'IconChartBarPopular', 'color' => ''],
            self::PPT => ['type' => 'IconPresentation', 'color' => ''],
            self::Report => ['type' => 'report', 'color' => ''],
            self::Summary => ['type' => 'IconFileDescription', 'color' => ''],
        };
    }

    /**
     * Get built-in agent prompt.
     */
    public function getPrompt(): array
    {
        return [];
    }

    /**
     * Get all built-in agents.
     * @return array<BuiltinAgent>
     */
    public static function getAllBuiltinAgents(): array
    {
        return [
            self::General,
            self::Chat,
            self::DataAnalysis,
            self::PPT,
            self::Summary,
        ];
    }
}
