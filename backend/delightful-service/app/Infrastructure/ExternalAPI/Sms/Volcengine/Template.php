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
    public static array $signToMessageGroup = ['灯塔engine'];

    protected array $typeToIdMap = [
        LanguageEnum::ZH_CN->value => [
            SmsTypeEnum::VERIFICATION_WITH_EXPIRATION->value => VolcengineTemplateIdEnum::ST_79E262F3->value,
        ],
    ];

    protected array $idContents = [
        VolcengineTemplateIdEnum::ST_79E262F3->value => '您verify码is：${verification_code}，valid期 ${timeout} minute钟。请inpagemiddleinputverify码completeverify。如non本person操as，请ignore。',
    ];

    /**
     * 短messagetemplateIdandmessagegroupmapping.
     */
    protected array $templateToGroupIdMap = [
        self::DEFAULT_MESSAGE_GROUP_ID => [
            VolcengineTemplateIdEnum::ST_79E25915->value,
        ],
    ];

    /**
     * 火山云短信signature暂未support国际化.
     */
    protected array $signMap = [
        '灯塔engine' => [
            LanguageEnum::ZH_CN->value => '灯塔engine',
            //            Language::EN_US => 'Light Engine',
        ],
        SignEnum::DENG_TA->value => [
            LanguageEnum::ZH_CN->value => '灯塔engine',
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
     * according to传come短信text,parsevariable. onlyvariablevalue,未匹配variablekey!
     * needvariableparsereason:火山短信只supportvariable短信send,while业务方willoutat创蓝短信reason,will传come整短信textcontent,nothavevariable.
     */
    public function smsVariableAnalyse(string $message, string $templateId, ?string $language): array
    {
        // 找tofinger定templatevariable正thenparserule. ifnot传模版id,loop正then匹配will降低匹配speeddegreeand准确degree
        if ($templateId) {
            // 判断templatewhether存in
            if (! isset($this->idContents[$templateId])) {
                throw new RuntimeException('未匹配totemplateid:' . $templateId);
            }
            $pregMatch = $this->variablePregAnalyse[$language][$templateId] ?? '';
            // ifaccording to短信content匹配totemplateid,then变more传入templateidvalue
            $pregMatch && [$templateId, $matchedVariables] = $this->variablePregMatch([$templateId => $pregMatch], $message);
        } elseif (isset($this->variablePregAnalyse[$language])) {
            // 火山普通短信,and无法according totype + language 确定templateid,尝试according to短信textcontent + language 确定templateidandvariable
            [$templateId, $matchedVariables] = $this->variablePregMatch($this->variablePregAnalyse[$language], $message);
        }
        if (empty($templateId)) {
            throw new RuntimeException('未匹配totemplateid');
        }
        if (empty($matchedVariables)) {
            throw new RuntimeException('短信templatevariableparsefail');
        }
        return [$templateId, $matchedVariables];
    }

    protected function getTemplateDefaultSignType(string $sign): string
    {
        return array_key_first(self::$signToMessageGroup) ?? '';
    }

    /**
     * @param array $pregVariableAnalyse ['templateid_xxx'=>'正thentable达type']
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
