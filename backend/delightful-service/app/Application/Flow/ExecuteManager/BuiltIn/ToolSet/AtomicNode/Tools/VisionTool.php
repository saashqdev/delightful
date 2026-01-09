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
        return 'providevisualcan力.useatidentifyusertoimage意graph,andreturnidentifyresult';
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
                // notagain具have user,directly采usefromlinegroup装 messages
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
            "description": "canusemodel.nonrequired",
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
            "description": "意graph.usertoimage意graph",
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
            "description": "imageground址.remoteimageground址",
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
            "description": "imagelinklist.多imageo clockuse",
            "required": null,
            "value": null,
            "encryption": false,
            "encryption_value": null,
            "items": {
                "type": "string",
                "key": "image_urls",
                "title": "imageground址",
                "description": "imageground址.remoteimageground址",
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
            "title": "identifyresult",
            "description": "identifyresult",
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
            "title": "inference",
            "description": "inference",
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
            'structure' => Value::buildConst('youisoneprofessionalvisualcomprehendassistant,请according tobydownstepreturn应user:

1. prioritycomprehenduser意graph,alwaysuseandusersamelanguagereturn答
2. provideconcise明directlyreturn答,directlyfull足usermainissue
3. againtoimagecontentconductmulti-dimensionaldegreedetailedanalyze,includebutnot限at:
   - 主bodycontentidentify:person物,物body,scenario,textetc
   - visualfeature:color,composition,光line,qualityetc
   - semanticinfo:activity,情绪,close系,background故事etc
   - textidentify:如havetext,accurateextractandcomprehendimplication
   - technologyinfo:如havegraphtable/data,analyzeitsimplication
   
4. analyzeformatrequire:
   - toat重wantanalyzeresult,usestructure化JSONformatpresent,如:{"category别":"xx", "主body":"xx", "feature":["xx","xx"]}
   - toatnotcertaincontent,explicittable明speculatedpropertyquality,for example:"maybeis..."
   - 如imagequalitymorelow,fingeroutlimitelementanddo one's bestanalyze
   - toat多graphanalyze,minute别markimage序numberconductparse,andsummaryitsassociateproperty
   
5. notice事item:
   - avoidtosensitivecontentmake subjective judgment
   - whenno法certainsome部minutecontento clock,坦诚table达notcertainproperty
   - maintain客观professional语气'),
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
