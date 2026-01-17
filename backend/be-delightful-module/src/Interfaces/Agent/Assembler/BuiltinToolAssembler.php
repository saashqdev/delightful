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
     * 创建工具分类列表DTO（层级格式）.
     * @return array<BuiltinToolCategoryDTO>
     */
    public static function createToolCategoryListDTO(): array
    {
        $categoryDTOs = [];

        // 按分类分组工具并直接创建分类DTO
        foreach (BuiltinTool::cases() as $toolEnum) {
            $toolCode = $toolEnum->value;
            $category = $toolEnum->getToolCategory();
            $categoryCode = $category->value;

            // 如果分类DTO还不存在，创建它
            if (! isset($categoryDTOs[$categoryCode])) {
                $categoryDTOs[$categoryCode] = new BuiltinToolCategoryDTO([
                    'name' => $category->getName(),
                    'icon' => $category->getIcon(),
                    'description' => $category->getDescription(),
                    'tools' => [],
                ]);
            }

            // 添加工具到对应分类
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
     * 创建工具列表DTO（平铺格式）.
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
