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
        return 'useuserissue和keyword，去检索knowledge basemiddle的content，return与userissuesimilardegreemost高的content。';
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
# allowbeuse的能力: knowledge base检索
## knowledge basecolumn表
> knowledge basename：knowledge basedescription
{$knowledgePrompt}
## process
1. 结合updown文提炼user的issue，generate多keyword，at mostnot超过 5 ，多keyworduse英文逗number"," 隔开，useatusedifferentkeywordfromknowledge basemiddle检索most相关的info；
2. 结合updown文，analyzeuser的issue，generate `names` parameter，useatfinger定与userissue可能have关的多knowledge basename，按照相关propertysort，相关property需结合updown文、knowledge basename和knowledge basedescriptionconduct判断；
3. usekeyword和userissue，call `{$this->getName()}` tool检索knowledge basemiddle的content，keyword的parameter是 `keyword`，userissue的parameter是 `question`, 请ensureparameterallbecorrect填入，tool将return与userissuesimilardegreemost高的contentslicesegment；
4. knowledge base检索出来的contentwithinwillcontain一些customize的 Delightful tag，你要善atuse它们，havebydown几typetag：
    - <DelightfulImage></DelightfulImage> 表示一image，如 <DelightfulImage>cp_xxxxxxx</DelightfulImage>，eachtagallwillinfront端messagecard渲染出一张image；
    - <DelightfulVideo></DelightfulVideo> 表示一video，如 <DelightfulVideo>cp_xxxxxxx</DelightfulVideo>，eachtagallwillinfront端messagecard渲染出一video；
    - <DelightfulMention></DelightfulMention> 表示一人员info，如 <DelightfulMention>cp_xxxxxxx</DelightfulMention>，eachtagallwillinfront端messagecardshapebecome一 @somesome人 的effect；
5. 优先usecontain <DelightfulImage></DelightfulImage>、<DelightfulVideo></DelightfulVideo>、<DelightfulMention></DelightfulMention> etchave Delightful tag的slicesegment；
6. 结合knowledge basereturn的content整理back尽可能丰富ground回答user的issue。
## toolmiddle关键的returnvalueinstruction
- fragments: 本time检索to的所haveknowledge baseslicesegment
- fragments.*.content: slicesegmentcontent
- fragments.*.metadata.url: currentslicesegment的原文link
- graph.*.content: 来自知识图谱的data，能enhanceinfo，让你more好回答issue
## 限制
- 回答的contentmiddlenotallow出现not是Delightfultag的link。
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
    "title": "rootsectionpoint",
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
            "title": "user的originalissue",
            "description": "user的originalissue",
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
            "description": "needbe检索的knowledge basename",
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
    "title": "rootsectionpoint",
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
            "title": "检索to的所havecontent",
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
    "title": "rootsectionpoint",
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
            "title": "knowledge basecolumn表",
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
            "title": "most大召回quantity",
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
            "title": "similardegree",
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
