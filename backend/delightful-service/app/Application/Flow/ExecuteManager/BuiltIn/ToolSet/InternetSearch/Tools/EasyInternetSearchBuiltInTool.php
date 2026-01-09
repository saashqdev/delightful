<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Flow\ExecuteManager\BuiltIn\ToolSet\InternetSearch\Tools;

use App\Application\Chat\Service\DelightfulChatAISearchV2AppService;
use App\Application\Flow\ExecuteManager\BuiltIn\BuiltInToolSet;
use App\Application\Flow\ExecuteManager\BuiltIn\ToolSet\AbstractBuiltInTool;
use App\Application\Flow\ExecuteManager\ExecutionData\ExecutionData;
use App\Domain\Chat\DTO\AISearch\Request\DelightfulChatAggregateSearchReqDTO;
use App\Domain\Chat\DTO\Message\ChatMessage\TextMessage;
use App\Domain\Chat\Entity\ValueObject\AggregateSearch\SearchDeepLevel;
use App\Domain\Contact\Entity\DelightfulUserEntity;
use App\Domain\Flow\Entity\ValueObject\NodeInput;
use App\Domain\Flow\Entity\ValueObject\NodeOutput;
use App\Infrastructure\Core\Collector\BuiltInToolSet\Annotation\BuiltInToolDefine;
use Closure;
use Delightful\FlowExprEngine\ComponentFactory;
use Delightful\FlowExprEngine\Structure\StructureType;

use function di;

#[BuiltInToolDefine]
class EasyInternetSearchBuiltInTool extends AbstractBuiltInTool
{
    public function getToolSetCode(): string
    {
        return BuiltInToolSet::InternetSearch->getCode();
    }

    public function getName(): string
    {
        return 'easy_internet_search';
    }

    public function getDescription(): string
    {
        return 'MagicInternetsearch简single版,batchquantitytouser多implicationsameordifferentissueconduct互联网search.';
    }

    public function getCallback(): ?Closure
    {
        return function (ExecutionData $executionData) {
            /** @var DelightfulUserEntity $userEntity */
            $userEntity = $executionData->getTriggerData()->getUserInfo()['user_entity'] ?? null;
            $args = $executionData->getTriggerData()->getParams();
            $questions = $args['questions'] ?? [];
            $userQuestion = implode('', $questions);
            $conversationId = $executionData->getOriginConversationId();
            $topicId = $executionData->getTopicId();
            $searchKeywordMessage = new TextMessage();
            $searchKeywordMessage->setContent($userQuestion);
            $delightfulChatAggregateSearchReqDTO = (new DelightfulChatAggregateSearchReqDTO())
                ->setConversationId($conversationId)
                ->setTopicId((string) $topicId)
                ->setUserMessage($searchKeywordMessage)
                ->setSearchDeepLevel(SearchDeepLevel::SIMPLE)
                ->setUserId($userEntity->getUserId())
                ->setOrganizationCode($userEntity->getOrganizationCode());
            return di(DelightfulChatAISearchV2AppService::class)->easyInternetSearch($delightfulChatAggregateSearchReqDTO);
        };
    }

    public function getInput(): ?NodeInput
    {
        $input = new NodeInput();
        $input->setForm(ComponentFactory::generateTemplate(StructureType::Form, json_decode(
            <<<'JSON'
{
    "type": "object",
    "key": "root",
    "sort": 0,
    "title": "rootsectionpoint",
    "description": "",
    "items": null,
    "value": null,
    "required": [
        "questions"
    ],
    "properties": {
        "questions": {
            "type": "array",
            "key": "questions",
            "title": "userissuecolumntable",
            "description": "userissuecolumntable",
            "required": null,
            "value": null,
            "encryption": false,
            "encryption_value": null,
            "items": {
                "type": "string",
                "key": "question",
                "sort": 0,
                "title": "question",
                "description": "",
                "required": null,
                "value": null,
                "encryption": false,
                "encryption_value": null,
                "items": null,
                "properties": null
            },
            "properties": null
        }
    }
}
JSON,
            true
        )));
        return $input;
    }

    public function getOutPut(): ?NodeOutput
    {
        $output = new NodeOutput();
        $output->setForm(ComponentFactory::generateTemplate(StructureType::Form, json_decode(
            <<<'JSON'
{
    "type": "object",
    "key": "root",
    "sort": 0,
    "title": "rootsectionpoint",
    "description": "",
    "items": null,
    "value": null,
    "required": [
        "url"
    ],
    "properties": {
        "search": {
            "type": "array",
            "key": "search",
            "title": "searchresult",
            "description": "searchresult",
            "required": null,
            "value": null,
            "encryption": false,
            "encryption_value": null,
            "items": {
                "type": "array",
                "key": "item",
                "sort": 0,
                "title": "item",
                "description": "",
                "required": null,
                "value": null,
                "encryption": false,
                "encryption_value": null,
                "items": null,
                "properties": null
            },
            "properties": null
        },
        "llm_response": {
            "type": "array",
            "key": "llm_response",
            "title": "bigmodelresponse",
            "description": "bigmodelresponse",
            "required": null,
            "value": null,
            "encryption": false,
            "encryption_value": null,
            "items": {
                "type": "array",
                "key": "item",
                "sort": 0,
                "title": "item",
                "description": "",
                "required": null,
                "value": null,
                "encryption": false,
                "encryption_value": null,
                "items": null,
                "properties": null
            },
            "properties": null
        },
        "related_questions": {
            "type": "array",
            "key": "related_questions",
            "title": "associateissue",
            "description": "associateissue",
            "required": null,
            "value": null,
            "encryption": false,
            "encryption_value": null,
            "items": {
                "type": "array",
                "key": "item",
                "sort": 0,
                "title": "item",
                "description": "",
                "required": null,
                "value": null,
                "encryption": false,
                "encryption_value": null,
                "items": null,
                "properties": null
            },
            "properties": null
        }
    }
}
JSON,
            true
        )));
        return $output;
    }
}
