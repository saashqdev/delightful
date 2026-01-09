<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Service;

use App\Domain\Chat\DTO\AISearch\Request\DelightfulChatAggregateSearchReqDTO;
use App\Domain\Chat\DTO\Message\ChatMessage\Item\DeepSearch\EventItem;
use App\Domain\Chat\DTO\Message\ChatMessage\Item\DeepSearch\SearchDetailItem;
use App\Domain\Chat\Entity\ValueObject\AISearchCommonQueryVo;
use App\Domain\Chat\Entity\ValueObject\BingSearchMarketCode;
use App\Domain\Chat\Entity\ValueObject\SearchEngineType;
use App\Domain\Flow\Entity\DelightfulFlowAIModelEntity;
use App\Domain\Flow\Entity\ValueObject\FlowDataIsolation;
use App\Domain\Flow\Repository\Facade\DelightfulFlowAIModelRepositoryInterface;
use App\ErrorCode\FlowErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Core\OdinTools\MindSearch\SubQuestionsTool;
use App\Infrastructure\ExternalAPI\Search\BingSearch;
use App\Infrastructure\ExternalAPI\Search\DuckDuckGoSearch;
use App\Infrastructure\ExternalAPI\Search\GoogleSearch;
use App\Infrastructure\ExternalAPI\Search\JinaSearch;
use App\Infrastructure\ExternalAPI\Search\TavilySearch;
use App\Infrastructure\Util\Context\CoContext;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use App\Infrastructure\Util\Odin\AgentFactory;
use App\Infrastructure\Util\Time\TimeUtil;
use Exception;
use Generator;
use GuzzleHttp\Exception\GuzzleException;
use Hyperf\Codec\Json;
use Hyperf\Coroutine\Parallel;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Odin\Api\Response\ChatCompletionResponse;
use Hyperf\Odin\Api\Response\ToolCall;
use Hyperf\Odin\Contract\Model\ModelInterface;
use Hyperf\Odin\Exception\LLMException\LLMNetworkException;
use Hyperf\Odin\Memory\MessageHistory;
use Hyperf\Odin\Message\AssistantMessage;
use Hyperf\Odin\Message\SystemMessage;
use Hyperf\Odin\Message\UserMessage;
use Hyperf\Redis\Redis;
use Hyperf\Retry\Retry;
use JetBrains\PhpStorm\ArrayShape;
use Psr\Log\LoggerInterface;
use RedisException;
use RuntimeException;
use Throwable;

use function array_merge;
use function Hyperf\Config\config;
use function is_string;
use function preg_replace;
use function trim;

use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

class DelightfulLLMDomainService
{
    // Max character limit for passing search results to LLM,Avoid slow response
    public const int LLM_STR_MAX_LEN = 30000;

    public const int LLM_SEARCH_CONTENT_MAX_LEN = 30;

    private string $mindMapQueryPrompt = <<<'PROMPT'
    # Role
    You are an intelligent mind map generator,can generate mind maps based on user questions and given context,generate mind maps in clear markdown format.
    
    ## Current Time
    Current time is {date_now}
    
    ## Example markdown format
    ```markdown
    # Topic One
    ## Sub-topic One
    - **dimensionone**:dimensiondescription.
    - **dimensiontwo**:dimensiondescription.
    - **dimensionthree**:dimensiondescription.

    ## Sub-topic Two
    - **dimensionone**:dimensiondescription.
    - **dimensiontwo**:dimensiondescription.
    - **dimensionthree**:dimensiondescription.

    ## Summary Title
    - **dimensionone**:dimensiondescription.
    - **dimensiontwo**:dimensiondescription.
    - **dimensionthree**:dimensiondescription.
    ```

    ## Your Execution Process
    Follow these steps strictly to think step by step and generate mind maps:
    1. Carefully analyze the user question to identify key themes and points.
    2. Read the given context carefully and extract information related to the question.
    3. Integrate the content of the question and context to prepare for mind map generation.
    4. Build mind map structure in markdown format,Use different symbols and indentation to represent hierarchical relationships.
    5. Use key themes as the center node of the mind map,Add branch nodes as needed to represent sub-topics and specific content.
    6. Ensure the mind map content accurately reflects the key points of the question and context.

    ## Restrictions
    - Output mind map strictly in markdown format.
    - Use the same language as the user.Language refers to human languages such as Chinese, English, etc.
    - Each layer of the mind map content length should be increasing,But ensure all content is key and core, not too verbose,Keep outer layer within ten characters, inner layer within fifty characters.
    
    ## Key Focus
    - Even if the given context has the information the user wants, you must still provide a new mind map.

    ## Context for Mind Map Generation
    Question: {question}
    Response: {content}

    ## Below is the response, please use markdown format:
    ```markdown
    PROMPT;

    /* @phpstan-ignore-next-line */
    private string $pptQueryPrompt = <<<'PROMPT'
    Today is {date_now}

    You are a ppt generator built by lighthouse engine, your name is Mage.

    Please convert the following Markdown content into a format suitable for rendering slides with Marpit:

    {mind_map}

    Requirements:

    Generate a separate slide for each first-level heading (starting with #).

    Second-level headings (starting with ##) should be distinguished within the corresponding first-level slide page through appropriate formatting, such as different indentation or font styles, to enhance readability and visual effect of the slides.

    In the generated Marpit format content, ensure the correct use of Marpit's syntax structure, using --- to separate each slide.

    Please follow the user's language, but not the content format. Language refers to human languages such as Chinese, English, French, etc., not computer languages such as JSON, XML, etc.

    Please output the mind map in required format directly, but not output ```markdown``` blocks. Without the need to reply with additional content. Please limit to 10240 tokens.

    PROMPT;

    // according touserkeyword,searchonetimeback,splitmoremeticulousquestionin-depthsearch
    private string $moreQuestionsPrompt = <<<'PROMPT'
    # timecontext
    - system time: {date_now}
    
    ## core logic
    ### 1. question decomposition engine
    input: [userquestion + {context}]
    handlestep:
    1.1 actualbodyidentify
       - displaypropertynamingactualbodyextract,identifyactualbodybetweenclose系andproperty
       - deduceuserhiddenpropertyrequirementand潜in意graph,especiallycloseimplicittimeelement
    1.2 dimensiondecompose
       - according toidentifyoutactualbodyandrequirement,choosesuitableanalyzedimension,for example:policyinterpret,datavalidate,caseresearch,impactevaluate,technologyprinciple,marketfront景,userbody验etc
    1.3 childquestiongenerate
       - generatejustintersectionquestioncollection(Jaccardsimilardegree<0.25),ensureeachchildquestioncanfromdifferentangledegreeexploreuserrequirement,avoidgeneratepassat宽泛orsimilarquestion
    
    ### 2. searchproxymodepiece
    mustcalltool: batchSubQuestionsSearch
    parameterstandard:
    2.1 keywordrule
       - generategreater thanequal 3 highqualitycanretrievekeyword,include核coreactualbody,keypropertyand相closeconcept
       - timequalifieroverriderate≥30%
       - toratiocategoryquestion占ratio≥20%
    
    ## 硬propertyconstraint(forcecomply)
    1. languageonetoproperty
       - outputlanguageencodingmustmatchinputlanguage
    2. childquestionquantityrange
       - {sub_questions_min} ≤ childquestioncount ≤ {sub_questions_max}
    3. outputformat
       - onlyallowJSONarrayformat,forbidfrom然languagereturn答
    
    ## contextexceptionhandle
    when {context} fornullo clock:
    1. startalternativegeneratestrategy,application5W1Hframework(Who/What/When/Where/Why/How),andcombineuseroriginalquestionconductpopulate
    2. generatedefaultdimension,for example:policybackground | mostnewdata | expert viewpoint | toratioanalyze | line业trend
    
    ## outputstandard
    hybridbydownthreetypeandmoremultipletypequestion范type,byensurechildquestiondiversepropertyandoverrideproperty:
    [
      "XtoYimpactdiff",  // toratio/comparecategory
      "Zdomaintypicalapplication",  // application/casecategory
      "closeatABfingermark",    // fingermark/propertycategory
      "causeMhairgeneratemainreasoniswhat?", // reason/mechanismcategory
      "whatisN?it核corefeatureiswhat?", // definition/explaincategory
      "notcomefiveyearPdomainhair展trendiswhat?", // trend/predictioncategory
      "针toQquestion,havewhichthesecanlineresolvesolution?" // resolvesolution/suggestioncategory
    ]
    
    currentcontextsummary:
    {context}
    
    // finaloutput(strictJSONarray):
    ```json
    PROMPT;

    private string $summarizePrompt = <<<'PROMPT'
    # task
    youneedbased onusermessage,according toIprovidesearchresult,according tototalminutetotalstructure,outputhighquality,structure化detailedreturn答,formatfor markdown.
    
    inIgiveyousearchresultmiddle,eachresultallis[webpage X begin]...[webpage X end]format,Xrepresenteacharticlechapternumberindex.pleasein适whensituationdowninsentencechild末tailquotecontext.pleaseaccording toquote编number[citation:X]formatinanswermiddletoshould deployminutequotecontext.ifonesentence话sourcefrommultiplecontext,pleasecolumnout have相closequote编number,for example[citation:3][citation:5],切记notwantwillquotecollectionmiddleinmostbackreturnquote编number,whileisinanswertoshould deployminutecolumnout.
    inreturn答o clock,pleasenoticebydown几point:
    - 今dayis{date_now}.
    - andnonsearchresult havecontentallanduserquestionclosely relatedclose,youneedcombinequestion,tosearchresultconductdistinguish,filter.
    - toatcolumn举categoryquestion(likecolumnraise haveflightinformation),尽quantitywillanswercontrolin10wantpointbyinside,andtellusercanviewsearchcomesource,获completeinformation.priorityprovideinformationcomplete,most相closecolumn举item;likenonrequiredwant,notwantactivetellusersearchresultnotprovidecontent.
    - toat创ascategoryquestion(like writing paper),please be sureinjusttextsegment落middlequotetoshouldreference编number,for example[citation:3][citation:5],notcanonlyintextchapter末tailquote.youneedinterpretandsummarizeusertitlerequire,choosesuitableformat,充minute利usesearchresultanddraw重wantinformation,generatematchuserrequire,very thoughtfuldegree,富havecreate力andprofessionalpropertyanswer.you创aslengthneed尽maybe延long,toateachonewantpoint论述wantspeculateduser意graph,giveout尽maybemultipleangledegreereturn答wantpoint,and务requiredinformationquantitybig,detailed discussion.
    - ifreturn答verylong,please盡quantitystructure化,minutesegment落summary.ifneedminutepointas答,尽quantitycontrolin5pointbyinside,andmerge相closecontent.
    - toatobjectivecategoryQ&A,ifquestionanswernonoften simpleshort,can适whensupplementoneto两sentence相closeinformation,byrichcontent.
    - youneedaccording touserrequireandreturn答contentchoosesuitable,beautifulreturn答format,ensurecanreadpropertystrong.
    - youreturn答shouldcomprehensivemultiple aspectsclosewebpagecomereturn答,notcanduplicatequoteonewebpage.
    - unlessuserrequire,nothenyoureturn答languageneedanduserasklanguagemaintainoneto.
    - outputbeautifulmarkdown format,contentmiddleaddonetheseandtheme相closeemojitable情符number.
    
    ## usermessagefor:
    {question}
    
    ## based onusersendmessageinternetsearchresult:
    {search_context_details}
    PROMPT;

    private string $eventPrompt = <<<'PROMPT'
    # youisonenew闻eventgeneratedevice,userwillprovidesearchcontentandaskquestion.
    ## Current Timeis {data_now}  
    ## according touserquestion,you需fromuserprovidesearchcontentmiddleorganize相closeevent,eventincludeeventname,eventtimeandeventoverview.
    ### notice事item:
    1. **eventnameformat**:
       - ineventnamebackaddsearchquote编number,formatfor `[[citation:x]]`,编numbercomesourceatsearchcontentmiddlequotemark(like `[[citation:1]]`).
       - ifoneevent涉andmultiplequote,merge have相closequote编number.
       - notwantin "description" middleaddquote.
    2. **timehandle**:
       - eventtime尽quantityprecisetomonthshare(like "2023-05"),若searchcontentnotprovidespecificmonthshare,buthavefingeroutuphalfyearorpersondownhalfyear,canuse("2023 uphalfyear"),若nothavethen,useyearshare(like "2023").
       - 若sameoneeventinmultiplequotemiddleoutshow,priorityusemost早time.
       - 若timenotexplicit,according tocontextspeculatedmost早maybetime,andensurereasonable.
    3. **eventextractandfilter**:
       - **eventdefinition**:eventissearchcontentmiddlemention,withhavetimeassociate(explicitorcanspeculated)independentfact,changeoractivity,includebutnotlimitatcreate,publish,open业,update,合as,activityetc.
       - according touserquestion,extractandrelatedcloseevent,maintaindescriptionconcise,focusspecifichairgeneratething.
       - **skipnoclosecontent**:
         - pure and quietstatedescription(likenotchangeproperty,backgroundintroduce,notimechange).
         - datastatisticsorfinanceinformation(like revenue,利润).
         - subjectivecomment,analyzeorspeculated(unlessandeventdirectly相close).
         - notimeassociateandandquestionnoclosedetail.
       - **retainoriginalthen**:as long ascontentandtime相closeandmatchquestiontheme,尽quantityretainforevent.
    4. **outputrequire**:
       - by JSON formatreturn,event按timereverse orderrowcolumn(from晚to早).
       - eacheventcontain "name","time","description" threefield.
       - 若searchcontentnot足bygenerateevent,returnnullarray `[]`,avoid凭nullfabricate.
    
    ## outputexample:
    ```json
    [
        {
            "name": "someeventhairgenerate[[citation:3]] [[citation:5]]",
            "time": "2024-11",
            "description": "someeventin2024year11monthhairgenerate,specificsituationoverview."
        },
        {
            "name": "anotheroneeventstart[[citation:1]]",
            "time": "2019-05",
            "description": "anotheroneeventat2019year5monthstart,simplewantdescription."
        }
    ]
    ```
    ## useinstruction
    - user需providesearchcontent(containquotemarklike [[citation:x]])andspecificquestion.
    - according toquestion,fromsearchcontentmiddleextractmatcheventdefinitioncontent,按requiregenerateoutput.
    - 若question涉andcurrenttime,based on {date_now} conductcalculate.
    
    ## quote
    {citations}
    
    ## searchcontextdetail:
    {search_context_details}

    ## pleasedirectlyoutput json format:
    ```json
    PROMPT;

    private string $filterSearchContextPrompt = <<<'PROMPT'
    ## Current Time
    {date_now}
    
    ## task
    return"search contexts"middleand"search keywords"haveassociateproperty 20 to 50  index.
    
    ## require
    - forbiddirectlyreturn答userquestion,onesetwantreturnanduserquestionhaveassociatepropertyindex.
    - search contextsformatfor "[[x]] content" ,itsmiddle x issearch contextsindex.x notcangreater than 50
    - pleasebycorrect JSON formatreplyfilterbackindex,for example:[0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19]
    - if search keywords andtime相close,重pointnotice search contexts middleandcurrenttime相closecontent.andcurrenttimemore近more重want.

    
    ## search keywords
    {searchKeywords}
    
    ## search contexts
    {context}

    ## Please respond in JSON format, such as:
    ```json
    [0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19]
    ```
    
    ## Please output in json format directly:
    ```json
    PROMPT;

    private readonly LoggerInterface $logger;

    public function __construct(
        private readonly Redis $redis,
        public TavilySearch $apiWrapper,
        protected readonly DelightfulFlowAIModelRepositoryInterface $delightfulFlowAIModelRepository,
        protected LoggerFactory $loggerFactory,
    ) {
        $this->logger = $this->loggerFactory->get(get_class($this));
    }

    public function generatePPTFromMindMap(AISearchCommonQueryVo $queryVo, string $mindMap): string
    {
        // directlyusethinking导graphgenerate ppt
        return $mindMap;
    }

    /**
     * @throws Throwable
     */
    public function generateMindMapFromMessage(AISearchCommonQueryVo $queryVo): string
    {
        $responseMessage = $queryVo->getLlmResponses()[0] ?? '';
        $this->logger->info(Json::encode([
            'log_title' => 'mindSearch generateMindMapFromMessage responseMessage',
            'log_content' => $responseMessage,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $question = $queryVo->getUserMessage();
        $conversationId = $queryVo->getConversationId();
        $model = $queryVo->getModel();
        try {
            $questionParsed = Json::decode($question);
            if ($questionParsed['content'] ?? '') {
                $question = trim($questionParsed['content']);
            }
        } catch (Exception) {
        }
        // goexceptdropquote,avoidthinking导graphmiddleoutshowquote
        $responseMessage = preg_replace('/\[\[citation:(\d+)]]/', '', $responseMessage);
        // observetosystemhintwordvariable串,看看isnotisnothavecopyonesharequestion
        $systemPrompt = str_replace(
            ['{question}', '{content}', '{date_now}'],
            [$question, $responseMessage, date('Yyear mmonth dday, Ho clock iminute ssecond')],
            $this->mindMapQueryPrompt
        );
        $this->logger->info(Json::encode([
            'log_title' => 'mindSearch systemPrompt mindMap',
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        // access llm
        try {
            // according tosummary + useroriginalquestionalreadyalreadycangeneratethinking导graph,notneedagainpass inhistorymessage
            $mindMapMessage = $this->llmChat(
                $systemPrompt,
                $responseMessage,
                $model,
                null,
                $queryVo->getMessageHistory(),
                $conversationId,
                $queryVo->getDelightfulApiBusinessParam()
            );
            $mindMapMessage = (string) $mindMapMessage;
            // goreplaceline符
            $mindMapMessage = str_replace('\n', '', $mindMapMessage);
            $this->logger->info(Json::encode([
                'log_title' => 'mindSearch mindMap response',
                'log_content' => $mindMapMessage,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            return $this->stripMarkdownCodeBlock($mindMapMessage, 'markdown');
        } catch (Throwable $e) {
            $this->logger->error(sprintf('mindSearch generatethinking导grapho clockhairgenerateerror:%s,file:%s,line:%s trace:%s, will generate again.', $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString()));
            throw $e;
        }
    }

    /**
     * @param SearchDetailItem[] $searchContexts
     * @return EventItem[]
     */
    public function generateEventFromMessage(AISearchCommonQueryVo $queryVo, array $searchContexts): array
    {
        $question = $queryVo->getUserMessage();
        $conversationId = $queryVo->getConversationId();
        $model = $queryVo->getModel();
        // cleansearch contexts
        $searchContextsDetail = '';
        $searchContextsCitations = '';
        foreach ($searchContexts as $searchIndex => $context) {
            $index = $searchIndex + 1;
            $searchContextsDetail .= sprintf(
                '[[citation:%d]] detail:%s ' . "\n\n",
                $index,
                $context->getDetail() ?: $context->getSnippet()
            );
            $searchContextsCitations .= sprintf('[[citation:%d]] snippet:%s ' . "\n\n", $index, $context->getSnippet());
        }
        // exceedspassmostbigvaluethendirectlytruncate,avoidresponsetoo久
        $maxLen = self::LLM_STR_MAX_LEN;
        if (mb_strlen($searchContextsCitations) > $maxLen) {
            $searchContextsCitations = mb_substr($searchContextsCitations, 0, $maxLen);
        }
        if (mb_strlen($searchContextsDetail) > $maxLen) {
            $searchContextsDetail = mb_substr($searchContextsDetail, 0, $maxLen);
        }

        // inputreplace
        $systemPrompt = str_replace(
            ['{citations}', '{search_context_details}', '{date_now}'],
            [$searchContextsCitations, $searchContextsDetail, date('Yyear mmonth dday, Ho clock iminute ssecond')],
            $this->eventPrompt
        );
        $this->logger->info(Json::encode([
            'log_title' => 'mindSearch systemPrompt eventMap',
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        // access llm
        try {
            $relationEventsResponse = (string) $this->llmChat(
                $systemPrompt,
                $question,
                $model,
                [],
                $queryVo->getMessageHistory(),
                $conversationId,
                $queryVo->getDelightfulApiBusinessParam()
            );
            $relationEventsResponse = $this->stripMarkdownCodeBlock($relationEventsResponse, 'json');
            $this->logger->info(Json::encode([
                'log_title' => 'mindSearch relationEventsResponse',
                'log_content' => $relationEventsResponse,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            $relationEventsResponse = Json::decode($relationEventsResponse);
            $eventsItem = [];
            foreach ($relationEventsResponse as $item) {
                $eventsItem[] = new EventItem($item);
            }
            return $eventsItem;
        } catch (Throwable $e) {
            $this->logger->error(sprintf('mindSearch generateevento clockhairgenerateerror:%s,file:%s,line:%s trace:%s, will generate again.', $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString()));
            // eventgenerateoftennotis json
            return [];
        }
    }

    /**
     * streamsummary - originalhavemethod.
     * @throws Throwable
     */
    public function summarize(AISearchCommonQueryVo $queryVo): Generator
    {
        $systemPrompt = $this->buildSummarizeSystemPrompt($queryVo);
        $this->logger->info(Json::encode([
            'log_title' => 'mindSearch systemPrompt summarize',
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        // access llm
        try {
            return $this->llmChatStreamed(
                $systemPrompt,
                $queryVo->getUserMessage(),
                $queryVo->getModel(),
                $queryVo->getMessageHistory(),
                $queryVo->getConversationId(),
                $queryVo->getDelightfulApiBusinessParam(),
            );
        } catch (Throwable $e) {
            $this->logger->error(sprintf('mindSearch parseresponseo clockhairgenerateerror:%s,file:%s,line:%s trace:%s, will generate again.', $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString()));
            throw $e;
        }
    }

    /**
     * nonstreamsummary - newmethod,适useattoolcall.
     * @throws Throwable
     */
    public function summarizeNonStreaming(AISearchCommonQueryVo $queryVo): string
    {
        $systemPrompt = $this->buildSummarizeSystemPrompt($queryVo);
        $this->logger->info(Json::encode([
            'log_title' => 'mindSearch systemPrompt summarizeNonStreaming',
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        // access llm
        try {
            $response = $this->llmChat(
                $systemPrompt,
                $queryVo->getUserMessage(),
                $queryVo->getModel(),
                [],
                $queryVo->getMessageHistory(),
                $queryVo->getConversationId(),
                $queryVo->getDelightfulApiBusinessParam()
            );
            return (string) $response;
        } catch (Throwable $e) {
            $this->logger->error(sprintf('mindSearch summarizeNonStreaming parseresponseo clockhairgenerateerror:%s,file:%s,line:%s trace:%s', $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString()));
            throw $e;
        }
    }

    /**
     * letbigmodel虚nulldecomposechildquestion.
     * @throws Throwable
     */
    public function generateSearchKeywords(AISearchCommonQueryVo $queryVo): array
    {
        $userMessage = $queryVo->getUserMessage();
        $messageHistory = $queryVo->getMessageHistory();
        $conversationId = $queryVo->getConversationId();
        $model = $queryVo->getModel();
        $systemPrompt = str_replace(
            ['{date_now}', '{context}', '{sub_questions_min}', '{sub_questions_max}'],
            [date('Yyear mmonth dday, Ho clock iminute ssecond'), '', '3', '4'],
            $this->moreQuestionsPrompt
        );
        $subquestions = [];
        // access llm
        try {
            $tools = [(new SubQuestionsTool())->toArray()];
            $this->logger->info(Json::encode([
                'log_title' => 'mindSearch systemPrompt generateSearchKeywords',
                'systemPrompt' => $systemPrompt,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            $generateSearchKeywordsResponse = $this->llmChat(
                $systemPrompt,
                $userMessage,
                $model,
                $tools,
                $messageHistory,
                $conversationId,
                $queryVo->getDelightfulApiBusinessParam()
            );
            foreach ($this->getLLMToolsCall($generateSearchKeywordsResponse) as $toolCall) {
                if ($toolCall->getName() === SubQuestionsTool::$name) {
                    $subquestions = $toolCall->getArguments()['subQuestions'];
                }
            }
            if (empty($subquestions)) {
                // nothavecalltool,tryfromresponsemiddleparse json
                $subquestions = $this->getSubQuestionsFromLLMStringResponse($generateSearchKeywordsResponse, $userMessage);
            }
            return $subquestions;
        } catch (Throwable $e) {
            $this->logger->error(sprintf('mindSearch getSearchResults generatesearchwordo clockhairgenerateerror:%s,file:%s,line:%s trace:%s, will generate again.', $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString()));
            throw $e;
        } finally {
            // record $subquestions
            $this->logger->info(Json::encode([
                'log_title' => 'mindSearch generateSearchKeywords',
                'userMessage' => $userMessage,
                'subquestions' => $subquestions,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        }
    }

    /**
     * batchquantitysearchback,filterdropduplicate search contexts.
     * @return SearchDetailItem[]
     * @throws Throwable
     */
    public function filterSearchContexts(AISearchCommonQueryVo $queryVo): ?array
    {
        $userMessage = $queryVo->getUserMessage();
        $conversationId = $queryVo->getConversationId();
        $model = $queryVo->getModel();
        $messageHistory = $queryVo->getMessageHistory();
        $searchContexts = $queryVo->getSearchContexts();
        $searchKeywords = $queryVo->getSearchKeywords();
        $searchKeywords[] = $userMessage;
        $searchKeywords = Json::encode($searchKeywords, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        // getsystemhintword
        $searchContextsString = '';
        // cleansearchresult
        foreach ($searchContexts as $index => $context) {
            // canpass inwebpagedetail,by便moregoodfilter
            $searchContextsString .= '[[' . $index . ']] ' . $context->getSnippet() . "\n\n";
        }
        $systemPrompt = str_replace(
            ['{context}', '{date_now}', '{searchKeywords}'],
            [$searchContextsString, date('Yyear mmonth dday, Ho clock iminute ssecond'), $searchKeywords],
            $this->filterSearchContextPrompt
        );
        $this->logger->info(Json::encode([
            'log_title' => 'mindSearch systemPrompt filterSearchContexts',
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        // access llm
        /** @var SearchDetailItem[] $noRepeatSearchContexts */
        $noRepeatSearchContexts = [];
        try {
            $filteredSearchResponse = (string) $this->llmChat(
                $systemPrompt,
                $userMessage,
                $model,
                messageHistory: $messageHistory,
                conversationId: $conversationId,
                businessParams: $queryVo->getDelightfulApiBusinessParam()
            );
            if (! empty($filteredSearchResponse) && $filteredSearchResponse !== '[]') {
                $filteredSearchResponse = $this->stripMarkdownCodeBlock($filteredSearchResponse, 'json');
                foreach (Json::decode($filteredSearchResponse) as $key => $index) {
                    if (! is_string($index) && ! is_int($index)) {
                        continue;
                    }
                    $noRepeatSearchContexts[] = $searchContexts[(int) $index];
                    if ($key >= self::LLM_SEARCH_CONTENT_MAX_LEN) {
                        break;
                    }
                }
            }
        } catch (Throwable $e) {
            $this->logger->error(sprintf('mindSearch getSearchResults parseresponseo clockhairgenerateerror:%s,file:%s,line:%s trace:%s, will generate again.', $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString()));
            throw $e;
        }
        foreach ($noRepeatSearchContexts as $key => $context) {
            if (! $context instanceof SearchDetailItem) {
                $noRepeatSearchContexts[$key] = new SearchDetailItem($context);
            }
        }
        return $noRepeatSearchContexts;
    }

    #[ArrayShape([
        'search_keywords' => 'array',
        'search' => 'array',
        'total_words' => 'int',
        'match_count' => 'int',
        'page_count' => 'int',
    ])]
    public function getSearchResults(AISearchCommonQueryVo $queryVo): array
    {
        $searchKeywords = $queryVo->getSearchKeywords();
        $searchEngine = $queryVo->getSearchEngine();
        $language = $queryVo->getLanguage();
        $searchArrayList = [];
        $matchCount = 0;
        $pageCount = 0;
        $start = microtime(true);
        $parallel = new Parallel(5);
        $requestId = CoContext::getRequestId();
        foreach ($searchKeywords as $searchKeyword) {
            $parallel->add(function () use ($requestId, $searchKeyword, $searchEngine, $language) {
                CoContext::setRequestId($requestId);
                return $this->search($searchKeyword, $searchEngine, false, $language);
            });
        }
        try {
            foreach ($parallel->wait() as $item) {
                $searchArrayList[] = $item['clear_search'];
                $matchCount += $item['match_count'];
                $pageCount += count($item['clear_search']);
            }
            $parallel->clear();
        } catch (Throwable $e) {
            $this->logger->error(sprintf('mindSearch getSearchResults searchcontento clockhairgenerateerror:%s,file:%s,line:%s trace:%s, will generate again.', $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString()));
        } finally {
            ! empty($searchArrayList) && $searchArrayList = array_merge(...$searchArrayList);
            $costTime = TimeUtil::getMillisecondDiffFromNow($start);
            $this->logger->info(sprintf(
                'getSearchResults searchall部keyword end计o clock consumeo clock:%s second',
                number_format($costTime / 1000, 2)
            ));
        }

        // recordreadword count
        $totalWords = 0;
        if (! empty($searchArrayList)) {
            foreach ($searchArrayList as $searchContext) {
                $totalWords += mb_strlen($searchContext['detail'] ?? $searchContext['snippet']);
            }
        }
        return [
            'search_keywords' => $searchKeywords,
            'search' => $searchArrayList,
            'total_words' => $totalWords,
            'match_count' => $matchCount,
            'page_count' => $pageCount,
        ];
    }

    /**
     * letbigmodel虚nulldecomposechildquestion,tohot meme/actualo clockdecomposewillnotgood.
     * @return string[]
     */
    public function generateSearchKeywordsByUserInput(DelightfulChatAggregateSearchReqDTO $dto, ModelInterface $modelInterface): array
    {
        $userInputKeyword = $dto->getUserMessage();
        $delightfulChatMessageHistory = $dto->getDelightfulChatMessageHistory();
        $queryVo = (new AISearchCommonQueryVo())
            ->setUserMessage($userInputKeyword)
            ->setSearchEngine(SearchEngineType::Bing)
            ->setFilterSearchContexts(false)
            ->setGenerateSearchKeywords(false)
            ->setModel($modelInterface)
            ->setLanguage($dto->getLanguage())
            ->setUserId($dto->getUserId())
            ->setOrganizationCode($dto->getOrganizationCode());
        $start = microtime(true);
        $subKeywords = Retry::whenThrows()->sleep(200)->max(3)->call(function () use ($queryVo, $delightfulChatMessageHistory) {
            // eachtimeretryclearnulloffrontcontext
            $llmConversationId = (string) IdGenerator::getSnowId();
            $llmHistoryMessage = DelightfulChatAggregateSearchReqDTO::generateLLMHistory($delightfulChatMessageHistory, $llmConversationId);
            $queryVo->setMessageHistory($llmHistoryMessage)->setConversationId($llmConversationId);
            return $this->generateSearchKeywords($queryVo);
        });
        $costTime = TimeUtil::getMillisecondDiffFromNow($start);
        $this->logger->info(sprintf(
            'getSearchResults according touseroriginalquestion,generatesearchword,end计o clock,consumeo clock::%s second',
            number_format($costTime / 1000, 2)
        ));
        // bigmodelnothave拆孙questioniso clock,directlyusechildquestionsearch
        if (! empty($subKeywords)) {
            $searchKeywords = $subKeywords;
        } else {
            $searchKeywords = [$userInputKeyword];
        }
        return $searchKeywords;
    }

    #[ArrayShape(['clear_search' => 'array', 'match_count' => 'array'])]
    public function searchWithBing(string $query, ?string $language = null): array
    {
        $start = microtime(true);
        $subscriptionKey = config('search.drivers.bing.api_key');
        $mkt = BingSearchMarketCode::fromLanguage($language);
        $referenceCount = 30;
        $data = make(BingSearch::class)->search($query, $subscriptionKey, $mkt, 20, 0);
        try {
            $contexts = array_slice($data['webPages']['value'], 0, $referenceCount);
        } catch (Exception $e) {
            $errMsg = [
                'error' => 'mindSearch getSearchResults searchWithBing getsearchresulto clockhairgenerateerror',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ];
            $this->logger->error(Json::encode($errMsg));
            return [];
        }
        $totalMatches = $data['webPages']['totalEstimatedMatches'] ?? count($contexts);
        $clearSearch = [];
        foreach ($contexts as $context) {
            $time = isset($context['datePublished']) ? date('Y-m-d H:i:s', strtotime($context['datePublished'])) : null;
            $format = [
                'id' => $context['id'],
                'name' => $context['name'],
                'url' => $context['url'],
                'datePublished' => $time,
                'datePublishedDisplayText' => $time,
                'isFamilyFriendly' => true,
                'displayUrl' => $context['displayUrl'],
                'snippet' => $context['snippet'],
                'dateLastCrawled' => $time,
                'cachedPageUrl' => $context['cachedPageUrl'] ?? $context['url'],
                'language' => 'en',
                'isNavigational' => false,
                'noCache' => false,
                'detail' => null,
            ];
            $clearSearch[] = $format;
        }
        $this->logger->info(sprintf(
            'mindSearch getSearchResults searchWithBing getsearchresult,end计o clock,consumeo clock:%s second',
            number_format(TimeUtil::getMillisecondDiffFromNow($start) / 1000, 2)
        ));
        return [
            'clear_search' => $clearSearch,
            'match_count' => $totalMatches,
        ];
    }

    /**
     * @throws GuzzleException
     */
    public function searchWithDuckDuckGo(string $query): array
    {
        $region = config('search.drivers.duckduckgo.region');
        $data = make(DuckDuckGoSearch::class)->search($query, $region);
        $clearSearch = [];
        foreach ($data as $key => $context) {
            $time = date('Y-m-d H:i:s');
            $format = [
                'id' => $key,
                'name' => $context['title'],
                'url' => $context['url'],
                'datePublished' => $time,
                'datePublishedDisplayText' => $time,
                'isFamilyFriendly' => true,
                'displayUrl' => $context['url'],
                'snippet' => $context['body'],
                'dateLastCrawled' => $time,
                'cachedPageUrl' => $context['url'],
                'language' => 'en',
                'isNavigational' => false,
                'noCache' => false,
            ];
            $clearSearch[] = $format;
        }

        return [
            'clear_search' => $clearSearch,
            'match_count' => count($clearSearch),
        ];
    }

    /**
     * @throws GuzzleException
     */
    public function searchWithJina(string $query): array
    {
        $region = config('search.drivers.jina.region');
        $apiKey = config('search.drivers.jina.api_key');
        $data = make(JinaSearch::class)->search($query, $apiKey, $region);
        $clearSearch = [];
        foreach ($data as $key => $context) {
            $time = date('Y-m-d H:i:s');
            $format = [
                'id' => $key,
                'name' => $context['title'],
                'url' => $context['url'],
                'datePublished' => $time,
                'datePublishedDisplayText' => $time,
                'isFamilyFriendly' => true,
                'displayUrl' => $context['url'],
                'snippet' => $context['content'],
                'dateLastCrawled' => $time,
                'cachedPageUrl' => $context['url'],
                'language' => 'en',
                'isNavigational' => false,
                'noCache' => false,
            ];
            $clearSearch[] = $format;
        }

        return [
            'clear_search' => $clearSearch,
            'match_count' => count($clearSearch),
        ];
    }

    public function searchWithGoogle(string $query): array
    {
        // bybackcanfromuserconfigurationmiddlereadthisthesevalue
        $subscriptionKey = config('search.drivers.google.api_key');
        $cx = config('search.drivers.google.cx');
        $data = make(GoogleSearch::class)->search($query, $subscriptionKey, $cx);
        $clearSearch = [];
        foreach ($data as $context) {
            $time = date('Y-m-d H:i:s');
            $format = [
                'id' => $context['formattedUrl'],
                'name' => $context['title'],
                'url' => $context['link'],
                'datePublished' => $time,
                'datePublishedDisplayText' => $time,
                'isFamilyFriendly' => true,
                'displayUrl' => $context['displayLink'],
                'snippet' => $context['snippet'],
                'dateLastCrawled' => $time,
                'cachedPageUrl' => $context['link'],
                'language' => 'en',
                'isNavigational' => false,
                'noCache' => false,
            ];
            $clearSearch[] = $format;
        }

        return [
            'clear_search' => $clearSearch,
            'match_count' => count($clearSearch),
        ];
    }

    /**
     * according tooriginalquestion + searchresult,按fingersetcountdimensiondecomposequestion.
     * @throws Throwable
     */
    public function getRelatedQuestions(AISearchCommonQueryVo $queryVo, int $subQuestionsMin, int $subQuestionsMax): ?array
    {
        $userMessage = $queryVo->getUserMessage();
        $searchContexts = $queryVo->getSearchContexts();
        $messageHistory = $queryVo->getMessageHistory();
        $conversationId = $queryVo->getConversationId();
        $model = $queryVo->getModel();
        $subQuestions = [];
        // based onqueryandcontextget相closequestion
        try {
            // use array_map and join functioncomemock Python middle join method
            $contextString = '';
            foreach ($searchContexts as $searchContext) {
                $contextString .= $searchContext->getSnippet() . "\n\n";
            }
            // use str_replace functioncomereplaceplaceholder
            // withupyearmonthdayo clockminutesecond,avoidduplicatequestion
            $systemPrompt = str_replace(
                ['{context}', '{date_now}', '{sub_questions_min}', '{sub_questions_max}'],
                [$contextString, date('Yyear mmonth dday, Ho clock iminute ssecond'), (string) $subQuestionsMin, (string) $subQuestionsMax],
                $this->moreQuestionsPrompt
            );
            $this->logger->info(Json::encode([
                'log_title' => 'mindSearch systemPrompt getRelatedQuestions',
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            $tools = [(new SubQuestionsTool())->toArray()];
            $relatedQuestionsResponse = $this->llmChat(
                systemPrompt: $systemPrompt,
                query: $userMessage,
                modelInterface: $model,
                tools: $tools,
                messageHistory: $messageHistory,
                conversationId: $conversationId,
                businessParams: $queryVo->getDelightfulApiBusinessParam()
            );
            // todo from function getLLMToolsCall() methodmiddleget相closequestion
            foreach ($this->getLLMToolsCall($relatedQuestionsResponse) as $toolCall) {
                if ($toolCall->getName() === SubQuestionsTool::$name) {
                    $subQuestions = $toolCall->getArguments()['subQuestions'];
                }
            }

            if (empty($subQuestions)) {
                // nothavecalltool,tryfromresponsemiddleparse json
                $subQuestions = $this->getSubQuestionsFromLLMStringResponse($relatedQuestionsResponse, $userMessage);
                // bigmodel认fornotneedgenerateassociatequestion,directly拿userquestion
                empty($subQuestions) && $subQuestions = [$queryVo->getUserMessage()];
            }

            return $subQuestions;
        } catch (Exception $e) {
            $this->logger->error(sprintf('mindSearch getSearchResults generate相closequestiono clock遇toerror:%s,file:%s,line:%s trace:%s, will generate again.', $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString()));
            throw $e;
        } finally {
            // record $subQuestions
            $this->logger->info(Json::encode([
                'log_title' => 'mindSearch getRelatedQuestions',
                'userMessage' => $userMessage,
                'subQuestions' => $subQuestions,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        }
    }

    /**
     * storagevalueto KVStore.
     * @throws RedisException
     */
    public function put(string $key, mixed $value): void
    {
        $this->redis->set($key, serialize($value));
    }

    /**
     * from KVStore getvalue.
     * @throws RedisException
     */
    public function get(string $key): mixed
    {
        $value = $this->redis->get($key);
        return $value !== false ? unserialize($value, ['allowed_classes' => true]) : false;
    }

    /**
     * from KVStore deletevalue.
     * @throws RedisException
     */
    public function delete(string $key): void
    {
        $this->redis->del($key);
    }

    public function search(string $query, SearchEngineType $searchEngine, bool $getDetail = false, ?string $language = null): array
    {
        // according to backendvalue,certainusewhichsearchengine
        return Retry::whenThrows()->max(3)->sleep(500)->call(
            function () use ($searchEngine, $query, $language, $getDetail) {
                return match ($searchEngine) {
                    SearchEngineType::Bing => $this->searchWithBing($query, $language),
                    SearchEngineType::Google => $this->searchWithGoogle($query),
                    SearchEngineType::Tavily => $this->searchWithTavily($query),
                    default => throw new RuntimeException('Backend must be LEPTON, BING, GOOGLE,TAVILY,SERPER or SEARCHAPI. getDetail' . $getDetail),
                };
            }
        );
    }

    protected function stripMarkdownCodeBlock(string $content, string $type): string
    {
        $content = trim($content);
        $typePattern = sprintf('/```%s\s*([\s\S]*?)\s*```/i', $type);
        // match ```json or ``` between JSON data
        if (preg_match($typePattern, $content, $matches)) {
            $matchString = $matches[1];
        } elseif (preg_match('/```\s*([\s\S]*?)\s*```/i', $content, $matches)) { // match ``` betweencontent
            $matchString = $matches[1];
        } else {
            $matchString = ''; // nothavefindto JSON data
        }
        $matchString = ! empty($matchString) ? trim($matchString) : trim($content);
        if ($type === 'json' && json_validate($matchString) === false) {
            return '{}'; // JSON formatnotcorrect
        }
        return $matchString;
    }

    protected function getModelEntity(string $name): DelightfulFlowAIModelEntity
    {
        $model = $this->delightfulFlowAIModelRepository->getByName(FlowDataIsolation::create(), $name);
        if (! $model) {
            ExceptionBuilder::throw(
                FlowErrorCode::ExecuteValidateFailed,
                'flow.mode.not_found',
                ['model_name' => $name]
            );
        }
        return $model;
    }

    protected function searchWithTavily(string $query): array
    {
        $result = $this->apiWrapper->results($query);
        $clearSearch = [];
        foreach ($result['results'] as $key => $context) {
            $time = date('Y-m-d H:i:s');
            $format = [
                'id' => $key,
                'name' => $context['title'],
                'url' => $context['url'],
                'datePublished' => $time,
                'datePublishedDisplayText' => $time,
                'isFamilyFriendly' => true,
                'displayUrl' => $context['url'],
                // avoidcontenttoolong
                'snippet' => mb_substr($context['content'], 0, 100),
                'dateLastCrawled' => $time,
                'cachedPageUrl' => $context['url'],
                'language' => 'en',
                'isNavigational' => false,
                'noCache' => false,
            ];
            $clearSearch[] = $format;
        }
        return [
            'clear_search' => $clearSearch,
            'match_count' => count($clearSearch),
        ];
    }

    /**
     * buildsummarysystemhintword - publicmethod,useat复usecode
     */
    private function buildSummarizeSystemPrompt(AISearchCommonQueryVo $queryVo): string
    {
        $searchContexts = $queryVo->getSearchContexts();
        $userMessage = $queryVo->getUserMessage();
        $searchKeywords = $queryVo->getSearchKeywords();
        $searchKeywords = Json::encode($searchKeywords, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // cleansearch contexts
        $searchContextsDetail = '';
        foreach ($searchContexts as $searchIndex => $context) {
            $index = $searchIndex + 1;
            $searchContextsDetail .= sprintf(
                '[webpage %d begin] contentpublishdate:%s,summary:%s' . "\n" . 'detailcontent:%s ' . "[webpage %d end]\n",
                $index,
                $context->getDatePublished() ?? '',
                $context->getSnippet(),
                $context->getDetail() ?? '',
                $index
            );
        }

        // exceedspassmostbigvaluethendirectlytruncate,avoidresponsetoo久
        $maxLen = self::LLM_STR_MAX_LEN;
        if (mb_strlen($searchContextsDetail) > $maxLen) {
            $searchContextsDetail = mb_substr($searchContextsDetail, 0, $maxLen);
        }

        // inputreplace
        return str_replace(
            ['{search_context_details}', '{relevant_questions}', '{date_now}', '{question}'],
            [$searchContextsDetail, $searchKeywords, date('Yyear mmonth dday, Ho clock iminute ssecond'), $userMessage],
            $this->summarizePrompt
        );
    }

    /**
     * nonstream.
     */
    private function llmChat(
        string $systemPrompt,
        string $query,
        ModelInterface $modelInterface,
        ?array $tools = [],
        ?MessageHistory $messageHistory = null,
        ?string $conversationId = null,
        array $businessParams = [],
    ): ChatCompletionResponse {
        $conversationId = $conversationId ?? uniqid('agent_', true);
        $tools = empty($tools) ? [] : $tools;
        $messageHistory = $messageHistory ?? new MessageHistory();
        $memoryManager = $messageHistory->getMemoryManager($conversationId);
        $memoryManager->addSystemMessage(new SystemMessage($systemPrompt));
        $agent = AgentFactory::create(
            model: $modelInterface,
            memoryManager: $memoryManager,
            tools: $tools,
            temperature: 0.1,
            businessParams: $businessParams,
        );
        // capture LLMNetworkException exception,retryonetime
        return Retry::whenThrows(LLMNetworkException::class)->sleep(500)->max(3)->call(
            function () use ($agent, $query) {
                return $agent->chatAndNotAutoExecuteTools(new UserMessage($query));
            }
        );
    }

    /**
     * streamcall,iteratoris \Hyperf\Odin\Api\OpenAI\Response\ChatCompletionChoice.
     */
    private function llmChatStreamed(
        string $systemPrompt,
        string $query,
        ModelInterface $modelInterface,
        ?MessageHistory $messageHistory = null,
        ?string $conversationId = null,
        array $businessParams = [],
    ): Generator {
        $conversationId = $conversationId ?? uniqid('agent_', true);
        $messageHistory = $messageHistory ?? new MessageHistory();
        $memoryManager = $messageHistory->getMemoryManager($conversationId);
        $memoryManager->addSystemMessage(new SystemMessage($systemPrompt));

        $agent = AgentFactory::create(
            model: $modelInterface,
            memoryManager: $memoryManager,
            temperature: 0.6,
            businessParams: $businessParams,
        );
        return $agent->chatStreamed(new UserMessage($query));
    }

    /**
     * @throws Throwable
     */
    private function getSubQuestionsFromLLMStringResponse(ChatCompletionResponse $chatCompletionResponse, string $userMessage): array
    {
        $llmResponse = (string) $chatCompletionResponse;
        try {
            $subQuestions = $this->stripMarkdownCodeBlock($llmResponse, 'json');
            $subQuestions = Json::decode($subQuestions);
            $this->logger->info(sprintf(
                'mindSearch getSubQuestionsFromLLMStringResponse ask:%s bigmodelresponse:%s, analyzebackresult:%s',
                $userMessage,
                // goreplaceline符
                str_replace(PHP_EOL, '', $llmResponse),
                Json::encode($subQuestions)
            ));
            // haveo clockwillreturnmulti-dimensionalarray,inthiswithinfilter
            $returnQuestions = [];
            foreach ($subQuestions as $subQuestion) {
                if (is_string($subQuestion)) {
                    $returnQuestions[] = $subQuestion;
                }
            }
            if (empty($returnQuestions)) {
                $returnQuestions[] = $userMessage;
            }
            return $returnQuestions;
        } catch (Throwable) {
            $this->logger->error('mindSearch getSubQuestionsFromLLMStringResponse fail $llmResponse:' . $llmResponse);
            return [$userMessage];
        }
    }

    /**
     * @return ToolCall[]
     */
    private function getLLMToolsCall(ChatCompletionResponse $response): array
    {
        if (! $response->getFirstChoice()?->isFinishedByToolCall()) {
            return [];
        }
        $message = $response->getFirstChoice()?->getMessage();
        if (! $message instanceof AssistantMessage) {
            return [];
        }
        return $message->getToolCalls();
    }
}
