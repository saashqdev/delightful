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
     * according to传入short信typeand语type,尝试certainmaybe存intemplateid.
     */
    public function getTemplateIdByTypeAndLanguage(string $type, ?string $language): ?string;

    /**
     * according to传入short信typeand语type,certainshort信content. maybewill动stateadjusttypeto应templatecontent.
     */
    public function getContentBySMSTypeAndLanguage(string $type, ?string $language): string;

    /**
     * according tofront传入short信templateid,certainshort信content.
     */
    public function getContentByTemplateId(string $templateId): string;

    /**
     * parsetemplatevariable,to variablekeyandvariablevalue array.
     */
    public function getTemplateVariables(string $content, array $messages): array;

    /**
     * according toshort信type,returntypesupport语typelist.
     * @return string[]
     */
    public function getTemplateLanguagesByType(string $type): array;

    /**
     * according to语typerequireandshort信supportsignaturelist,returnto应signaturetext.
     */
    public function formatSign(string $sign, ?LanguageEnum $language, ?LanguageEnum $defaultLanguage = LanguageEnum::ZH_CN): string;
}
