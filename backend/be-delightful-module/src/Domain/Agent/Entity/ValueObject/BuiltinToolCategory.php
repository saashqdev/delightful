<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\Agent\Entity\ValueObject;

use function Hyperf\Translation\trans;

enum BuiltinToolCategory: string
{
    case FileOperations = 'file_operations';
    case SearchExtraction = 'search_extraction';
    case ContentProcessing = 'content_processing';
    case SystemExecution = 'system_execution';
    case AIAssistance = 'ai_assistance';

    /**
     * Get category display name.
     */
    public function getName(): string
    {
        return trans("builtin_tool_categories.names.{$this->value}");
    }

    /**
     * Get category icon.
     */
    public function getIcon(): string
    {
        // Temporarily return empty string, waiting for frontend to provide icon content
        return '';
    }

    /**
     * Get category description.
     */
    public function getDescription(): string
    {
        return trans("builtin_tool_categories.descriptions.{$this->value}");
    }
}
