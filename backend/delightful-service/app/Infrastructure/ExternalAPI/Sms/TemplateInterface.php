<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\Sms;

use App\Infrastructure\ExternalAPI\Sms\Enum\LanguageEnum;

interface TemplateInterface
{
    /**
     * according to传入的短信type和语种,尝试确定可能存在的templateid.
     */
    public function getTemplateIdByTypeAndLanguage(string $type, ?string $language): ?string;

    /**
     * according to传入的短信type和语种,确定短信content. 可能will动态调整type对应的templatecontent.
     */
    public function getContentBySMSTypeAndLanguage(string $type, ?string $language): string;

    /**
     * according to前传入的短信templateid,确定短信content.
     */
    public function getContentByTemplateId(string $templateId): string;

    /**
     * parsetemplatevariable,得到 variablekey与variablevalue 的array.
     */
    public function getTemplateVariables(string $content, array $messages): array;

    /**
     * according to短信type,returntypesupport的语种list.
     * @return string[]
     */
    public function getTemplateLanguagesByType(string $type): array;

    /**
     * according to语种要求和短信support的signaturelist,return对应的signature文本.
     */
    public function formatSign(string $sign, ?LanguageEnum $language, ?LanguageEnum $defaultLanguage = LanguageEnum::ZH_CN): string;
}
