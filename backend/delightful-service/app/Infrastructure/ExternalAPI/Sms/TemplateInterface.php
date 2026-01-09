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
     * according topass inshortmessagetypeandlanguagetype,trycertainmaybeexistsintemplateid.
     */
    public function getTemplateIdByTypeAndLanguage(string $type, ?string $language): ?string;

    /**
     * according topass inshortmessagetypeandlanguagetype,certainshortmessagecontent. maybewillautostateadjusttypetoshouldtemplatecontent.
     */
    public function getContentBySMSTypeAndLanguage(string $type, ?string $language): string;

    /**
     * according tofrontpass inshortmessagetemplateid,certainshortmessagecontent.
     */
    public function getContentByTemplateId(string $templateId): string;

    /**
     * parsetemplatevariable,to variablekeyandvariablevalue array.
     */
    public function getTemplateVariables(string $content, array $messages): array;

    /**
     * according toshortmessagetype,returntypesupportlanguagetypelist.
     * @return string[]
     */
    public function getTemplateLanguagesByType(string $type): array;

    /**
     * according tolanguagetyperequireandshortmessagesupportsignaturelist,returntoshouldsignaturetext.
     */
    public function formatSign(string $sign, ?LanguageEnum $language, ?LanguageEnum $defaultLanguage = LanguageEnum::ZH_CN): string;
}
