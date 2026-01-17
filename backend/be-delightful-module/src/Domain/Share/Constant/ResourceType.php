<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\Share\Constant;

use RuntimeException;

/**
 * Resource type enum.
 */
enum ResourceType: int
{
    // Existing types
    case BotCode = 1;           // AI Assistant
    case SubFlowCode = 2;       // Sub-flow
    case ToolSet = 3;           // Tool set
    case Knowledge = 4;         // Knowledge base

    // New business types
    case Topic = 5;             // Topic
    case Document = 6;          // Document
    case Schedule = 7;          // Schedule
    case MultiTable = 8;        // Multi-dimensional table
    case Form = 9;              // Form
    case MindMap = 10;          // Mind map
    case Website = 11;          // Website
    case Project = 12;          // Project
    case File = 13;             // File
    case ProjectInvitation = 14; // Project invitation link

    /**
     * Get the business name of the resource type.
     */
    public function getBusinessName(): string
    {
        return match ($this) {
            self::BotCode => 'bot',
            self::SubFlowCode => 'subflow',
            self::ToolSet => 'toolset',
            self::Knowledge => 'knowledge',
            self::Topic => 'topic',
            self::Document => 'document',
            self::Schedule => 'schedule',
            self::MultiTable => 'multitable',
            self::Form => 'form',
            self::MindMap => 'mindmap',
            self::Website => 'website',
            self::Project => 'project',
            self::File => 'file',
            self::ProjectInvitation => 'project_invitation',
        };
    }

    /**
     * Get resource type enum from business name.
     *
     * @param string $businessName Business name
     * @return ResourceType Resource type enum
     * @throws RuntimeException Thrown when the corresponding resource type cannot be found
     */
    public static function fromBusinessName(string $businessName): self
    {
        foreach (self::cases() as $type) {
            if ($type->getBusinessName() === $businessName) {
                return $type;
            }
        }

        throw new RuntimeException("Resource type with business name '{$businessName}' not found");
    }

    public static function isProjectInvitation(int $type): bool
    {
        return self::ProjectInvitation->value === $type;
    }
}
