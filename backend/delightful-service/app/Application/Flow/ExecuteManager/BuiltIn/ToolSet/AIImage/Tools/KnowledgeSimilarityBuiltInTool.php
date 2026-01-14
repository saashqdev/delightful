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
use BeDelightful\FlowExprEngine\ComponentFactory;
use BeDelightful\FlowExprEngine\Structure\StructureType;

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
        return 'useuserissueandkeyword,goretrieveknowledge basemiddlecontent,returnanduserissuesimilardegreemosthighcontent.';
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
            $knowledgePrompt .= "- {$knowledge->getName()}:{$knowledge->getDescription()}\n";
        }
        return <<<MARKDOWN
# allowbeusecancapability: knowledge baseretrieve
## knowledge basecolumntable
> knowledge basename:knowledge basedescription
{$knowledgePrompt}
## process
1. combineupdowntext extractionuserissue,generatemultiplekeyword,at mostnotexceedspass 5 ,multiplekeyworduseEnglishteasenumber"," separatoropen,useatusedifferentkeywordfromknowledge basemiddleretrievemostrelatedcloseinfo;
2. combineupdowntext,analyzeuserissue,generate `names` parameter,useatfingersetanduserissuemaybehaveclosemultipleknowledge basename,according torelatedclosepropertysort,relatedclosepropertyneedcombineupdowntext,knowledge basenameandknowledge basedescriptionconductjudge;
3. usekeywordanduserissue,call `{$this->getName()}` toolretrieveknowledge basemiddlecontent,keywordparameteris `keyword`,userissueparameteris `question`, pleaseensureparameterallbecorrectfill in,toolwillreturnanduserissuesimilardegreemosthighcontentslicesegment;
4. knowledge baseretrieveoutcomecontentwithinwillcontainonethesecustomize Delightful tag,youwantgoodatuseitus,havebydownseveraltypetag:
    - <DelightfulImage></DelightfulImage> indicateoneimage,like <DelightfulImage>cp_xxxxxxx</DelightfulImage>,eachtagallwillinfrontclientmessagecardrenderoutonesheetimage;
    - <DelightfulVideo></DelightfulVideo> indicateonevideo,like <DelightfulVideo>cp_xxxxxxx</DelightfulVideo>,eachtagallwillinfrontclientmessagecardrenderoutonevideo;
    - <DelightfulMention></DelightfulMention> indicateonepersonmemberinfo,like <DelightfulMention>cp_xxxxxxx</DelightfulMention>,eachtagallwillinfrontclientmessagecardshapebecomeone @somesomeperson effect;
5. priorityusecontain <DelightfulImage></DelightfulImage>,<DelightfulVideo></DelightfulVideo>,<DelightfulMention></DelightfulMention> etchave Delightful tagslicesegment;
6. combineknowledge basereturncontentorganizebackexhaustedmayberichgroundreturnansweruserissue.
## toolmiddleclosekeyreturnvalueinstruction
- fragments: thistimeretrieveto haveknowledge baseslicesegment
- fragments.*.content: slicesegmentcontent
- fragments.*.metadata.url: currentslicesegmentoriginal textlink
- graph.*.content: comefromknowledgegraphspectrumdata,canenhanceinfo,letyoumoregoodreturnanswerissue
## limit
- returnanswercontentmiddlenotallowoutshownotisDelightfultaglink.
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
            "title": "searchkeyword",
            "description": "searchkeyword",
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
            "title": "useroriginalissue",
            "description": "useroriginalissue",
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
            "description": "needberetrieveknowledge basename",
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
            "title": "retrieveto havecontent",
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
            "title": "knowledge basecolumntable",
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
            "title": "mostbigcallreturnquantity",
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
