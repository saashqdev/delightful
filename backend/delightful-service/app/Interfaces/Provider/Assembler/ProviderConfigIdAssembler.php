<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Provider\Assembler;

use App\Domain\Provider\Entity\ValueObject\Category;
use App\Domain\Provider\Entity\ValueObject\ProviderCode;
use App\Domain\Provider\Entity\ValueObject\ProviderTemplateId;
use InvalidArgumentException;

/**
 * service商templateID处理工具类
 * 用于生成和解析service商templateID（use固定的数value型string）.
 */
class ProviderConfigIdAssembler
{
    /**
     * according toProviderCode和Category生成service商template的config_id.
     */
    public static function generateProviderTemplate(ProviderCode $providerCode, Category $category): string
    {
        $templateId = ProviderTemplateId::fromProviderCodeAndCategory($providerCode, $category);
        if ($templateId === null) {
            throw new InvalidArgumentException("Unsupported provider code and category combination: {$providerCode->value} + {$category->value}");
        }

        return $templateId->value;
    }

    /**
     * check给定的configurationID是否为任何service商的template.
     * 支持数value型string格式的templateID.
     */
    public static function isAnyProviderTemplate(null|int|string $configId): bool
    {
        return self::parseProviderTemplate($configId) !== null;
    }

    /**
     * according totemplateconfigurationID解析出ProviderCode和Category.
     * 支持数value型string格式的templateID.
     * @return null|array{providerCode: ProviderCode, category: Category}
     */
    public static function parseProviderTemplate(null|int|string $configId): ?array
    {
        if ($configId === null) {
            return null;
        }

        $configIdStr = (string) $configId;
        $templateId = ProviderTemplateId::tryFrom($configIdStr);

        if ($templateId === null) {
            return null;
        }

        return $templateId->toProviderCodeAndCategory();
    }

    /**
     * according toProviderTemplateIdget对应的数value型string.
     */
    public static function getTemplateIdValue(ProviderTemplateId $templateId): string
    {
        return $templateId->value;
    }

    /**
     * according to数value型stringget对应的ProviderTemplateId.
     */
    public static function getTemplateIdFromValue(null|int|string $configId): ?ProviderTemplateId
    {
        if ($configId === null) {
            return null;
        }

        $configIdStr = (string) $configId;
        return ProviderTemplateId::tryFrom($configIdStr);
    }
}
