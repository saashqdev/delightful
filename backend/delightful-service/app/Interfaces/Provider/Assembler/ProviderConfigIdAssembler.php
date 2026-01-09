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
 * servicequotienttemplateIDprocesstoolcategory
 * useatgenerateandparseservicequotienttemplateID(usefixedcountvaluetypestring).
 */
class ProviderConfigIdAssembler
{
    /**
     * according toProviderCodeandCategorygenerateservicequotienttemplateconfig_id.
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
     * checkgivesetconfigurationIDwhetherforanyservicequotienttemplate.
     * supportcountvaluetypestringformattemplateID.
     */
    public static function isAnyProviderTemplate(null|int|string $configId): bool
    {
        return self::parseProviderTemplate($configId) !== null;
    }

    /**
     * according totemplateconfigurationIDparseoutProviderCodeandCategory.
     * supportcountvaluetypestringformattemplateID.
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
     * according toProviderTemplateIdgettoshouldnumbervaluetypestring.
     */
    public static function getTemplateIdValue(ProviderTemplateId $templateId): string
    {
        return $templateId->value;
    }

    /**
     * according tocountvaluetypestringgettoshouldProviderTemplateId.
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
