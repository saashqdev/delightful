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
        return '提供视觉能力。用于识别user对image的意图，并return识别result';
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
                // 不再具有 user，直接采用自行group装的 messages
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
    "title": "root节点",
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
            "description": "可用model。非必填",
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
            "title": "意图",
            "description": "意图。user对image的意图",
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
            "title": "image地址",
            "description": "image地址。远程image地址",
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
            "description": "imagelinklist。多个image时use",
            "required": null,
            "value": null,
            "encryption": false,
            "encryption_value": null,
            "items": {
                "type": "string",
                "key": "image_urls",
                "title": "image地址",
                "description": "image地址。远程image地址",
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
    "title": "root节点",
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
            'structure' => Value::buildConst('你是一个专业的视觉理解助理，请按照以下步骤回应user：

1. 优先理解user的意图，始终use与usersame的语言回答
2. 提供简洁明了的直接回答，直接满足user的主要issue
3. 再对imagecontent进行多维度详细analyze，include但不限于：
   - 主体content识别：人物、物体、场景、文字等
   - 视觉特征：color、构图、光线、quality等
   - 语义info：活动、情绪、关系、背景故事等
   - 文字识别：如有文字，准确提取并理解含义
   - 技术info：如有图table/data，analyze其含义
   
4. analyzeformat要求：
   - 对于重要analyzeresult，use结构化JSONformat呈现，如：{"类别":"xx", "主体":"xx", "特征":["xx","xx"]}
   - 对于不确定content，明确table明推测性质，for example："可能是..."
   - 如imagequality较低，指出限制因素并尽力analyze
   - 对于多图analyze，分别markimage序号进行parse，并总结其associate性
   
5. 注意事项：
   - 避免对敏感content做主观评判
   - 当无法确定某部分content时，坦诚table达不确定性
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
