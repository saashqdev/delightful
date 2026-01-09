<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Flow\ExecuteManager\BuiltIn\ToolSet\AIImage\Tools;

use App\Application\Flow\ExecuteManager\BuiltIn\BuiltInToolSet;
use App\Application\Flow\ExecuteManager\BuiltIn\ToolSet\AbstractBuiltInTool;
use App\Application\Flow\ExecuteManager\Compressible\CompressibleContent;
use App\Application\Flow\ExecuteManager\ExecutionData\ExecutionData;
use App\Application\KnowledgeBase\VectorDatabase\Similarity\KnowledgeSimilarityFilter;
use App\Application\KnowledgeBase\VectorDatabase\Similarity\KnowledgeSimilarityManager;
use App\Domain\Flow\Entity\ValueObject\NodeInput;
use App\Domain\Flow\Entity\ValueObject\NodeOutput;
use App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\Knowledge\Structure\Knowledge;
use App\Domain\KnowledgeBase\Entity\ValueObject\KnowledgeBaseDataIsolation;
use App\Infrastructure\Core\Collector\BuiltInToolSet\Annotation\BuiltInToolDefine;
use Closure;
use Delightful\FlowExprEngine\ComponentFactory;
use Delightful\FlowExprEngine\Structure\StructureType;

#[BuiltInToolDefine]
class KnowledgeSimilarityBuiltInTool extends AbstractBuiltInTool
{
    private ?NodeInput $input = null;

    private ?NodeInput $customSystemInput = null;

    public function getToolSetCode(): string
    {
        return BuiltInToolSet::AtomicNode->getCode();
    }

    public function getName(): string
    {
        return 'knowledge_similarity';
    }

    public function getDescription(): string
    {
        return 'useuser问题和关键词，去检索knowledge base中的content，return与user问题相似度最高的content。';
    }

    public function getAppendSystemPrompt(array $customParams = []): string
    {
        $knowledgeList = $customParams['knowledge_list'] ?? [];
        if (empty($knowledgeList)) {
            return '';
        }
        $knowledgePrompt = '';
        /** @var Knowledge $knowledge */
        foreach ($knowledgeList as $knowledge) {
            $knowledgePrompt .= "- {$knowledge->getName()}：{$knowledge->getDescription()}\n";
        }
        return <<<MARKDOWN
# allow被use的能力: knowledge base检索
## knowledge base列表
> knowledge basename：knowledge basedescription
{$knowledgePrompt}
## process
1. 结合上下文提炼user的问题，generate多个关键词，at most不超过 5 个，多个关键词用英文逗号"," 隔开，用于usedifferent关键词从knowledge base中检索最相关的info；
2. 结合上下文，分析user的问题，generate `names` parameter，用于指定与user问题可能有关的多个knowledge basename，按照相关性sort，相关性需结合上下文、knowledge basename和knowledge basedescription进行判断；
3. use关键词和user问题，call `{$this->getName()}` 工具检索knowledge base中的content，关键词的parameter是 `keyword`，user问题的parameter是 `question`, 请ensureparameter都被correct填入，工具将return与user问题相似度最高的content片段；
4. knowledge base检索出来的content里willcontain一些customize的 Delightful tag，你要善于use它们，有以下几种tag：
    - <DelightfulImage></DelightfulImage> 表示一个image，如 <DelightfulImage>cp_xxxxxxx</DelightfulImage>，每个tag都will在前端messagecard渲染出一张image；
    - <DelightfulVideo></DelightfulVideo> 表示一个video，如 <DelightfulVideo>cp_xxxxxxx</DelightfulVideo>，每个tag都will在前端messagecard渲染出一个video；
    - <DelightfulMention></DelightfulMention> 表示一个人员info，如 <DelightfulMention>cp_xxxxxxx</DelightfulMention>，每个tag都will在前端messagecard形成一个 @某某人 的effect；
5. 优先usecontain <DelightfulImage></DelightfulImage>、<DelightfulVideo></DelightfulVideo>、<DelightfulMention></DelightfulMention> 等有 Delightful tag的片段；
6. 结合knowledge basereturn的content整理后尽可能丰富地回答user的问题。
## 工具中关键的returnvalueinstruction
- fragments: 本次检索到的所有knowledge base片段
- fragments.*.content: 片段content
- fragments.*.metadata.url: current片段的原文link
- graph.*.content: 来自知识图谱的data，能增强info，让你更好回答问题
## 限制
- 回答的content中不allow出现不是Delightfultag的link。
MARKDOWN;
    }

    public function getCallback(): ?Closure
    {
        return function (ExecutionData $executionData) {
            $params = $executionData->getTriggerData()->getParams();
            $customParams = $executionData->getTriggerData()->getSystemParams();

            $knowledgeList = $customParams['knowledge_list'] ?? [];
            $query = $params['keyword'] ?? '';
            $question = $params['question'] ?? '';

            if (empty($knowledgeList) || empty($query)) {
                return [];
            }
            $limit = $customParams['limit'] ?? 5;
            $score = $customParams['score'] ?? 0.4;

            $similarityCodes = [];
            $names = $params['names'] ?? [];
            foreach ($names as $name) {
                foreach ($knowledgeList as $item) {
                    if (empty($item['name']) || empty($item['knowledge_code'])) {
                        continue;
                    }
                    if ($name === $item['name']) {
                        $similarityCodes[] = $item['knowledge_code'];
                    }
                }
            }
            if (empty($similarityCodes)) {
                $similarityCodes = $customParams['knowledge_codes'] ?? [];
            }

            $knowledgeSimilarity = new KnowledgeSimilarityFilter();
            $knowledgeSimilarity->setKnowledgeCodes($similarityCodes);
            $knowledgeSimilarity->setQuery($query);
            $knowledgeSimilarity->setQuestion($question);
            $knowledgeSimilarity->setLimit($limit);
            $knowledgeSimilarity->setScore($score);

            $dataIsolation = $executionData->getDataIsolation();
            $knowledgeBaseDataIsolation = KnowledgeBaseDataIsolation::createByBaseDataIsolation($dataIsolation);
            $fragments = di(KnowledgeSimilarityManager::class)->similarity($knowledgeBaseDataIsolation, $knowledgeSimilarity);

            $similarityContents = [];
            $fragmentList = [];
            foreach ($fragments as $fragment) {
                $similarityContents[] = $fragment->getContent();
                $fragmentList[] = [
                    'business_id' => $fragment->getBusinessId(),
                    'content' => CompressibleContent::compress($fragment->getContent()),
                    'metadata' => $fragment->getMetadata(),
                ];
            }

            return [
                'similarities' => $similarityContents,
                'fragments' => $fragmentList,
            ];
        };
    }

    public function isShow(): bool
    {
        return false;
    }

    public function getInput(): ?NodeInput
    {
        if ($this->input) {
            return $this->input;
        }
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
        "keyword",
        "question"
    ],
    "properties": {
         "keyword": {
            "type": "string",
            "key": "keyword",
            "sort": 0,
            "title": "search关键字",
            "description": "search关键字",
            "required": null,
            "value": null,
            "encryption": false,
            "encryption_value": null,
            "items": null,
            "properties": null
        },
        "question": {
            "type": "string",
            "key": "question",
            "sort": 0,
            "title": "user的original问题",
            "description": "user的original问题",
            "required": null,
            "value": null,
            "encryption": false,
            "encryption_value": null,
            "items": null,
            "properties": null
        },
        "names": {
            "type": "array",
            "key": "names",
            "sort": 1,
            "title": "knowledge base names",
            "description": "need被检索的knowledge basename",
            "required": null,
            "value": null,
            "encryption": false,
            "encryption_value": null,
            "items": {
                "type": "string",
                "key": "names",
                "sort": 0,
                "title": "names",
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
        $this->input = $input;
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
    "title": "root节点",
    "description": "",
    "items": null,
    "value": null,
    "required": [
        "content"
    ],
    "properties": {
         "content": {
            "type": "string",
            "key": "content",
            "sort": 0,
            "title": "检索到的所有content",
            "description": "",
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
        return $output;
    }

    public function getCustomSystemInput(): ?NodeInput
    {
        if ($this->customSystemInput) {
            return $this->customSystemInput;
        }
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
        "knowledge_codes",
        "limit",
        "score"
    ],
    "properties": {
        "knowledge_codes": {
            "type": "array",
            "key": "knowledge_codes",
            "title": "knowledge base codes",
            "description": "",
            "required": null,
            "value": null,
            "encryption": false,
            "encryption_value": null,
            "items": {
                "type": "string",
                "key": "knowledge_codes",
                "sort": 0,
                "title": "knowledge_codes",
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
        "knowledge_list": {
            "type": "array",
            "key": "knowledge_list",
            "title": "knowledge base列表",
            "description": "",
            "required": null,
            "value": null,
            "encryption": false,
            "encryption_value": null,
            "items": {
                "type": "object",
                "key": "knowledge_list",
                "title": "knowledge_list",
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
         "limit": {
            "type": "number",
            "key": "limit",
            "title": "最大召回quantity",
            "description": "",
            "required": null,
            "value": null,
            "encryption": false,
            "encryption_value": null,
            "items": null,
            "properties": null
        },
        "score": {
            "type": "number",
            "key": "score",
            "title": "相似度",
            "description": "",
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
        $this->customSystemInput = $input;
        return $input;
    }
}
