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
use App\Domain\Flow\Entity\ValueObject\NodeInput;
use App\Infrastructure\Core\Collector\BuiltInToolSet\Annotation\BuiltInToolDefine;
use Closure;
use Delightful\FlowExprEngine\ComponentFactory;
use Delightful\FlowExprEngine\Structure\StructureType;

use function di;

#[BuiltInToolDefine]
/**
 * 采用one seq push所有的search相关content，前端不再多个 seq merge成one渲染.
 */
class InternetSearchV2BuiltInTool extends AbstractBuiltInTool
{
    public function getToolSetCode(): string
    {
        return BuiltInToolSet::InternetSearch->getCode();
    }

    public function getName(): string
    {
        return 'internet_search_v2';
    }

    public function getDescription(): string
    {
        return '麦吉互联网search，批量对user的多个含义sameordifferent的问题进行互联网search。';
    }

    public function getCallback(): ?Closure
    {
        return function (ExecutionData $executionData) {
            $args = $executionData->getTriggerData()?->getParams();
            $questions = $args['questions'] ?? [];
            $useDeepSearch = $args['use_deep_search'] ?? false;
            if (empty($questions)) {
                return null;
            }
            $userQuestion = implode(' ', $questions);
            $conversationId = $executionData->getOriginConversationId();
            if ($executionData->getExecutionType()->isDebug()) {
                // debug mode
                return ['deep_internet_search : current not support debug model'];
            }
            $topicId = $executionData->getTopicId();
            $searchKeywordMessage = new TextMessage();
            $searchKeywordMessage->setContent($userQuestion);
            $delightfulChatAggregateSearchReqDTO = (new DelightfulChatAggregateSearchReqDTO())
                ->setConversationId($conversationId)
                ->setTopicId((string) $topicId)
                ->setUserMessage($searchKeywordMessage)
                ->setSearchDeepLevel($useDeepSearch ? SearchDeepLevel::DEEP : SearchDeepLevel::SIMPLE);
            di(DelightfulChatAISearchV2AppService::class)->aggregateSearch($delightfulChatAggregateSearchReqDTO);
            return null;
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
    "title": "root节点",
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
            "title": "user问题列表",
            "description": "user问题列表",
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
        },
        "use_deep_search": {
            "type": "boolean",
            "key": "use_deep_search",
            "title": "是否use深度search",
            "description": "是否use深度search",
            "required": null,
            "value": null,
            "encryption": false,
            "encryption_value": null,
            "items": null,
            "properties": null
        }
    }
}
JSON,
            true
        )));
        return $input;
    }
}
