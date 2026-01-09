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
     * according to传入短信typeand语type,尝试certainmaybe存intemplateid.
     */
    public function getTemplateIdByTypeAndLanguage(string $type, ?string $language): ?string;

    /**
     * according to传入短信typeand语type,certain短信content. maybewill动stateadjusttypeto应templatecontent.
     */
    public function getContentBySMSTypeAndLanguage(string $type, ?string $language): string;

    /**
     * according tofront传入短信templateid,certain短信content.
     */
    public function getContentByTemplateId(string $templateId): string;

    /**
     * parsetemplatevariable,to variablekeyandvariablevalue array.
     */
    public function getTemplateVariables(string $content, array $messages): array;

    /**
     * according to短信type,returntypesupport语typelist.
     * @return string[]
     */
    public function getTemplateLanguagesByType(string $type): array;

    /**
     * according to语typerequireand短信supportsignaturelist,returnto应signaturetext.
     */
    public function formatSign(string $sign, ?LanguageEnum $language, ?LanguageEnum $defaultLanguage = LanguageEnum::ZH_CN): string;
}
