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
 * service商templateIDprocesstoolcategory
 * useatgenerateandparseservice商templateID（usefixed数value型string）.
 */
class ProviderConfigIdAssembler
{
    /**
     * according toProviderCodeandCategorygenerateservice商templateconfig_id.
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
     * checkgive定configurationIDwhetherfor任何service商template.
     * support数value型stringformattemplateID.
     */
    public static function isAnyProviderTemplate(null|int|string $configId): bool
    {
        return self::parseProviderTemplate($configId) !== null;
    }

    /**
     * according totemplateconfigurationIDparseoutProviderCodeandCategory.
     * support数value型stringformattemplateID.
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
     * according toProviderTemplateIdgetto应数value型string.
     */
    public static function getTemplateIdValue(ProviderTemplateId $templateId): string
    {
        return $templateId->value;
    }

    /**
     * according to数value型stringgetto应ProviderTemplateId.
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
