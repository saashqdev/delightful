<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Flow\ExecuteManager\BuiltIn\ToolSet\AtomicNode\Tools;

use App\Application\Flow\ExecuteManager\BuiltIn\BuiltInToolSet;
use App\Application\Flow\ExecuteManager\BuiltIn\ToolSet\AbstractBuiltInTool;
use App\Application\Flow\ExecuteManager\ExecutionData\ExecutionData;
use App\Application\Flow\ExecuteManager\NodeRunner\NodeRunnerFactory;
use App\Domain\Flow\Entity\ValueObject\Node;
use App\Domain\Flow\Entity\ValueObject\NodeInput;
use App\Domain\Flow\Entity\ValueObject\NodeOutput;
use App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\LLM\V1\LLMChatNodeParamsConfig;
use App\Domain\Flow\Entity\ValueObject\NodeType;
use App\Infrastructure\Core\Collector\BuiltInToolSet\Annotation\BuiltInToolDefine;
use App\Infrastructure\Core\Dag\VertexResult;
use Closure;
use Delightful\FlowExprEngine\Component;
use Delightful\FlowExprEngine\ComponentFactory;
use Delightful\FlowExprEngine\Structure\Expression\Value;
use Delightful\FlowExprEngine\Structure\StructureType;
use Hyperf\Odin\Message\UserMessage;
use Hyperf\Odin\Message\UserMessageContent;

#[BuiltInToolDefine]
class VisionTool extends AbstractBuiltInTool
{
    public function getToolSetCode(): string
    {
        return BuiltInToolSet::AtomicNode->getCode();
    }

    public function getName(): string
    {
        return 'vision';
    }

    public function getDescription(): string
    {
        return '提供视觉能力。useat识别user对image的意graph，并return识别result';
    }

    /**
     * @return array{response: string, reasoning: string, model: string}
     */
    public static function execute(ExecutionData $executionData): array
    {
        $visor = new VisionTool();
        $callback = $visor->getCallback();
        return $callback($executionData);
    }

    public function getCallback(): ?Closure
    {
        return function (ExecutionData $executionData) {
            $executionData = clone $executionData;

            $params = $executionData->getTriggerData()->getParams();

            $node = Node::generateTemplate(NodeType::LLM, [], 'latest');
            /** @var LLMChatNodeParamsConfig $nodeParamsConfig */
            $nodeParamsConfig = $node->getNodeParamsConfig();

            $model = empty($params['model']) ? $nodeParamsConfig->getDefaultVisionModelString() : $params['model'];

            $node->setParams([
                'model' => $model,
                'system_prompt' => $this->createSystemPrompt(),
                // notagain具have user，直接采use自linegroup装的 messages
                'messages' => $this->createMessages($executionData, $params),
                'user_prompt' => ComponentFactory::generateTemplate(StructureType::Value),
                'model_config' => [
                    'auto_memory' => false,
                    'vision' => true,
                    'vision_model' => $model,
                ],
            ]);

            $runner = NodeRunnerFactory::make($node);
            $vertexResult = new VertexResult();
            $runner->execute($vertexResult, $executionData, [
                'check_user_content' => false,
            ]);
            $result = $vertexResult->getResult();
            return [
                'response' => $result['response'] ?? '',
                'reasoning' => $result['reasoning'] ?? '',
                'model' => $model,
            ];
        };
    }

    public function getInput(): ?NodeInput
    {
        $input = new NodeInput();
        $input->setForm(ComponentFactory::generateTemplate(StructureType::Form, json_decode(<<<'JSON'
{
    "type": "object",
    "key": "root",
    "sort": 0,
    "title": "rootsectionpoint",
    "description": "",
    "required": [
        "intent"
    ],
    "value": null,
    "encryption": false,
    "encryption_value": null,
    "items": null,
    "properties": {
        "model": {
            "type": "string",
            "key": "model",
            "title": "model",
            "description": "可usemodel。non必填",
            "required": null,
            "value": null,
            "encryption": false,
            "encryption_value": null,
            "items": null,
            "properties": null
        },
        "intent": {
            "type": "string",
            "key": "intent",
            "title": "意graph",
            "description": "意graph。user对image的意graph",
            "required": null,
            "value": null,
            "encryption": false,
            "encryption_value": null,
            "items": null,
            "properties": null
        },
        "image_url": {
            "type": "string",
            "key": "image_url",
            "title": "imageground址",
            "description": "imageground址。远程imageground址",
            "required": null,
            "value": null,
            "encryption": false,
            "encryption_value": null,
            "items": null,
            "properties": null
        },
        "image_urls": {
            "type": "array",
            "key": "image_urls",
            "title": "file",
            "description": "imagelinklist。多imageo clockuse",
            "required": null,
            "value": null,
            "encryption": false,
            "encryption_value": null,
            "items": {
                "type": "string",
                "key": "image_urls",
                "title": "imageground址",
                "description": "imageground址。远程imageground址",
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
JSON, true)));
        return $input;
    }

    public function getOutPut(): ?NodeOutput
    {
        $output = new NodeOutput();
        $output->setForm(ComponentFactory::generateTemplate(StructureType::Form, json_decode(<<<'JSON'
{
    "type": "object",
    "key": "root",
    "sort": 0,
    "title": "rootsectionpoint",
    "description": "",
    "required": [
        "response",
        "reasoning",
        "model"
    ],
    "value": null,
    "encryption": false,
    "encryption_value": null,
    "items": null,
    "properties": {
        "response": {
            "type": "string",
            "key": "response",
            "title": "识别result",
            "description": "识别result",
            "required": null,
            "value": null,
            "encryption": false,
            "encryption_value": null,
            "items": null,
            "properties": null
        },
        "reasoning": {
            "type": "string",
            "key": "reasoning",
            "title": "推理",
            "description": "推理",
            "required": null,
            "value": null,
            "encryption": false,
            "encryption_value": null,
            "items": null,
            "properties": null
        }
    }
}
JSON, true)));
        return $output;
    }

    private function createSystemPrompt(): Component
    {
        return ComponentFactory::fastCreate([
            'type' => StructureType::Value,
            'structure' => Value::buildConst('你是一专业的视觉理解助理，请按照bydownstep回应user：

1. 优先理解user的意graph，始终use与usersame的语言回答
2. 提供简洁明了的直接回答，直接满足user的mainissue
3. again对imagecontentconduct多维degree详细analyze，includebutnot限at：
   - 主bodycontent识别：person物、物body、场景、文字etc
   - 视觉特征：color、composition、光line、qualityetc
   - 语义info：activity、情绪、关系、background故事etc
   - 文字识别：如have文字，准确extract并理解含义
   - 技术info：如havegraphtable/data，analyze其含义
   
4. analyzeformat要求：
   - 对at重要analyzeresult，use结构化JSONformat呈现，如：{"category别":"xx", "主body":"xx", "特征":["xx","xx"]}
   - 对atnot确定content，明确table明推测propertyquality，for example："可能是..."
   - 如imagequalitymore低，finger出限制因素并尽力analyze
   - 对at多graphanalyze，minute别markimage序numberconductparse，并总结其associateproperty
   
5. 注意事item：
   - 避免对敏感content做主观评判
   - when无法确定some部minutecontento clock，坦诚table达not确定property
   - 保持客观专业的语气'),
        ]);
    }

    private function createMessages(ExecutionData $executionData, array $params): Component
    {
        $userMessage = new UserMessage();
        $userMessage->addContent(UserMessageContent::text($params['intent'] ?? ''));
        if (! empty($params['image_url'])) {
            $userMessage->addContent(UserMessageContent::imageUrl($params['image_url']));
        }
        foreach ($params['image_urls'] ?? [] as $url) {
            $userMessage->addContent(UserMessageContent::imageUrl($url));
        }
        $messages = [$userMessage->toArray()];
        $executionData->saveNodeContext('vision_tool', [
            'messages' => $messages,
        ]);

        return ComponentFactory::generateTemplate(StructureType::Form, json_decode(
            <<<'JSON'
{
    "type": "array",
    "key": "root",
    "sort": 0,
    "title": "historymessage",
    "description": "",
    "required": null,
    "value": {
        "type": "expression",
        "const_value": null,
        "expression_value": [
            {
                "type": "fields",
                "value": "vision_tool.messages",
                "name": "",
                "args": null
            }
        ]
    },
    "encryption": false,
    "encryption_value": null,
    "items": {
        "type": "object",
        "key": "messages",
        "sort": 0,
        "title": "historymessage",
        "description": "",
        "required": [
            "role",
            "content"
        ],
        "value": null,
        "encryption": false,
        "encryption_value": null,
        "items": null,
        "properties": {
            "role": {
                "type": "string",
                "key": "role",
                "sort": 0,
                "title": "role",
                "description": "",
                "required": null,
                "value": null,
                "encryption": false,
                "encryption_value": null,
                "items": null,
                "properties": null
            },
            "content": {
                "type": "string",
                "key": "content",
                "sort": 1,
                "title": "content",
                "description": "",
                "required": null,
                "value": null,
                "encryption": false,
                "encryption_value": null,
                "items": null,
                "properties": null
            }
        }
    },
    "properties": null
}
JSON,
            true
        ));
    }
}
