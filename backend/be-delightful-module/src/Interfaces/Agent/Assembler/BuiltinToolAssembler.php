<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Interfaces\Agent\Assembler;

use Delightful\BeDelightful\Domain\Agent\Entity\ValueObject\BuiltinTool;
use Delightful\BeDelightful\Interfaces\Agent\DTO\BuiltinToolCategoryDTO;
use Delightful\BeDelightful\Interfaces\Agent\DTO\BuiltinToolDTO;

class BuiltinToolAssembler
{
    /**
     * Create tool category list DTO (hierarchical format).
     * @return array<BuiltinToolCategoryDTO>
     */
    public static function createToolCategoryListDTO(): array
    {
        $categoryDTOs = [];

        // Group tools by category and create category DTOs directly
        foreach (BuiltinTool::cases() as $toolEnum) {
            $toolCode = $toolEnum->value;
            $category = $toolEnum->getToolCategory();
            $categoryCode = $category->value;

            // If category DTO does not exist yet, create it
            if (! isset($categoryDTOs[$categoryCode])) {
                $categoryDTOs[$categoryCode] = new BuiltinToolCategoryDTO([
                    'name' => $category->getName(),
                    'icon' => $category->getIcon(),
                    'description' => $category->getDescription(),
                    'tools' => [],
                ]);
            }

            // Add tool to corresponding category
            $categoryDTOs[$categoryCode]->addTool(new BuiltinToolDTO([
                'code' => $toolCode,
                'name' => $toolEnum->getToolName(),
                'description' => $toolEnum->getToolDescription(),
                'icon' => $toolEnum->getToolIcon(),
                'required' => $toolEnum->isRequired(),
            ]));
        }

        return array_values($categoryDTOs);
    }

    /**
     * Create tool list DTO (flat format).
     * @return array<BuiltinToolDTO>
     */
    public static function createToolListDTO(): array
    {
        $tools = [];
        foreach (BuiltinTool::cases() as $toolEnum) {
            $tools[] = new BuiltinToolDTO([
                'code' => $toolEnum->value,
                'name' => $toolEnum->getToolName(),
                'description' => $toolEnum->getToolDescription(),
                'icon' => $toolEnum->getToolIcon(),
                'required' => $toolEnum->isRequired(),
            ]);
        }
        return $tools;
    }
}
