<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\Sms\Volcengine;

use App\Infrastructure\ExternalAPI\Sms\AbstractTemplate;
use App\Infrastructure\ExternalAPI\Sms\Enum\LanguageEnum;
use App\Infrastructure\ExternalAPI\Sms\Enum\SignEnum;
use App\Infrastructure\ExternalAPI\Sms\Enum\SmsTypeEnum;
use App\Infrastructure\ExternalAPI\Sms\Volcengine\Base\VolcengineTemplateIdEnum;
use RuntimeException;

class Template extends AbstractTemplate
{
    /**
     * defaultmessagegroupID.
     */
    public const string DEFAULT_MESSAGE_GROUP_ID = '77a48cb1';

    /**
     * messagegroupsupportsignaturelist.
     */
    public static array $signToMessageGroup = ['lighthouseengine'];

    protected array $typeToIdMap = [
        LanguageEnum::ZH_CN->value => [
            SmsTypeEnum::VERIFICATION_WITH_EXPIRATION->value => VolcengineTemplateIdEnum::ST_79E262F3->value,
        ],
    ];

    protected array $idContents = [
        VolcengineTemplateIdEnum::ST_79E262F3->value => '您verifycodeis:${verification_code},valid期 ${timeout} minuteseconds.pleaseinpagemiddleinputverifycodecompleteverify.likenonthispersonoperationas,pleaseignore.',
    ];

    /**
     * shortmessagetemplateIdandmessagegroupmapping.
     */
    protected array $templateToGroupIdMap = [
        self::DEFAULT_MESSAGE_GROUP_ID => [
            VolcengineTemplateIdEnum::ST_79E25915->value,
        ],
    ];

    /**
     * Volcanocloudshortmessagesignature暫notsupportinternationalization.
     */
    protected array $signMap = [
        'lighthouseengine' => [
            LanguageEnum::ZH_CN->value => 'lighthouseengine',
            //            Language::EN_US => 'Light Engine',
        ],
        SignEnum::DENG_TA->value => [
            LanguageEnum::ZH_CN->value => 'lighthouseengine',
            //            Language::EN_US => 'Light Engine',
        ],
    ];

    public function getMessageGroupId(string $templateId): string
    {
        foreach ($this->templateToGroupIdMap as $groupId => $templateIds) {
            if (in_array($templateId, $templateIds, true)) {
                return $groupId;
            }
        }
        return self::DEFAULT_MESSAGE_GROUP_ID;
    }

    /**
     * according to传comeshortmessagetext,parsevariable. onlyvariablevalue,notmatchvariablekey!
     * needvariableparsereason:Volcanoshortmessageonlysupportvariableshortmessagesend,whilebusinesssidewilloutatChuanglanshortmessagereason,will传come整shortmessagetextcontent,nothavevariable.
     */
    public function smsVariableAnalyse(string $message, string $templateId, ?string $language): array
    {
        // findtofingersettemplatevariablejustthenparserule. ifnottemplateid,loopjustthenmatchwilldecreasematchspeeddegreeandaccuratedegree
        if ($templateId) {
            // judgetemplatewhetherexistsin
            if (! isset($this->idContents[$templateId])) {
                throw new RuntimeException('notmatchtotemplateid:' . $templateId);
            }
            $pregMatch = $this->variablePregAnalyse[$language][$templateId] ?? '';
            // ifaccording toshortmessagecontentmatchtotemplateid,thenchangemorepass intemplateidvalue
            $pregMatch && [$templateId, $matchedVariables] = $this->variablePregMatch([$templateId => $pregMatch], $message);
        } elseif (isset($this->variablePregAnalyse[$language])) {
            // Volcanonormalshortmessage,andnomethodaccording totype + language certaintemplateid,tryaccording toshortmessagetextcontent + language certaintemplateidandvariable
            [$templateId, $matchedVariables] = $this->variablePregMatch($this->variablePregAnalyse[$language], $message);
        }
        if (empty($templateId)) {
            throw new RuntimeException('notmatchtotemplateid');
        }
        if (empty($matchedVariables)) {
            throw new RuntimeException('shortmessagetemplatevariableparsefail');
        }
        return [$templateId, $matchedVariables];
    }

    protected function getTemplateDefaultSignType(string $sign): string
    {
        return array_key_first(self::$signToMessageGroup) ?? '';
    }

    /**
     * @param array $pregVariableAnalyse ['templateid_xxx'=>'justthentable达type']
     */
    private function variablePregMatch(array $pregVariableAnalyse, string $message): array
    {
        $matchedVariables = [];
        $matches = [];
        $templateId = null;
        foreach ($pregVariableAnalyse as $templateId => $pregTemplate) {
            if (preg_match($pregTemplate, $message, $matches)) {
                $matchedVariables = array_slice($matches, 1);
                break;
            }
        }
        return [$templateId, $matchedVariables];
    }
}
