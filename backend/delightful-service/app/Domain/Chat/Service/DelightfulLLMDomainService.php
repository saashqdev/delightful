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

    // according touserkey词,searchonetimeback,splitmore细致question深入search
    private string $moreQuestionsPrompt = <<<'PROMPT'
    # timecontext
    - system time: {date_now}
    
    ## core logic
    ### 1. question decomposition engine
    input: [userquestion + {context}]
    handlestep:
    1.1 实bodyidentify
       - 显property命名实bodyextract,identify实bodybetweenclose系andproperty
       - 推导user隐property需求and潜in意graph,especiallyclose注隐含time因素
    1.2 dimension拆解
       - according toidentifyout实bodyand需求,choose合适analyzedimension,for example:policy解读、datavalidate、案例research、impactevaluate、技术原理、市场front景、userbody验etc
    1.3 子questiongenerate
       - generatejust交子questioncollection(Jaccardsimilardegree<0.25),ensureeach子questioncanfromdifferentangledegreeexploreuser需求,avoidgeneratepassat宽泛orsimilarquestion
    
    ### 2. searchproxy模piece
    mustcalltool: batchSubQuestionsSearch
    parameterstandard:
    2.1 key词rule
       - generategreater thanequal 3 highqualitycanretrievekey词,include核core实body、keypropertyand相close概念
       - time限定符overriderate≥30%
       - toratiocategoryquestion占ratio≥20%
    
    ## 硬property约束(forcecomply)
    1. languageone致property
       - outputlanguageencodingmustmatchinputlanguage
    2. 子questionquantityrange
       - {sub_questions_min} ≤ 子question数 ≤ {sub_questions_max}
    3. outputformat
       - onlyallowJSONarrayformat,forbidfrom然languagereturn答
    
    ## contextexceptionhandle
    when {context} fornullo clock:
    1. start备选generatestrategy,application5W1Hframework(Who/What/When/Where/Why/How),and结合useroriginalquestionconduct填充
    2. generatedefaultdimension,for example:policybackground | mostnewdata | 专家观point | toratioanalyze | line业trend
    
    ## outputstandard
    混合bydownthreetypeandmore多typequestion范type,byensure子question多样propertyandoverrideproperty:
    [
      "XtoYimpactdiff",  // toratio/comparecategory
      "Z领域典型application",  // application/案例category
      "closeatABfinger标",    // finger标/propertycategory
      "导致Mhair生mainreasonis什么?", // reason/机制category
      "什么isN?it核core特征is什么?", // definition/解释category
      "notcomefiveyearP领域hair展trendis什么?", // trend/预测category
      "针toQquestion,have哪thesecanlineresolvesolution?" // resolvesolution/suggestioncategory
    ]
    
    currentcontextsummary:
    {context}
    
    // finaloutput(严格JSONarray):
    ```json
    PROMPT;

    private string $summarizePrompt = <<<'PROMPT'
    # task
    youneedbased onusermessage,according toIprovidesearchresult,按照总minute总结构,outputhighquality,结构化detailedreturn答,formatfor markdown.
    
    inIgiveyousearchresultmiddle,eachresultallis[webpage X begin]...[webpage X end]format,Xrepresenteach篇文chapternumberindex.请in适when情况downinsentence子末tailquotecontext.请按照quote编number[citation:X]formatin答案middleto应部minutequotecontext.ifonesentence话源from多context,请columnout所have相closequote编number,for example[citation:3][citation:5],切记notwantwillquotecollectionmiddleinmostbackreturnquote编number,whileisin答案to应部minutecolumnout.
    inreturn答o clock,请noticebydown几point:
    - 今dayis{date_now}.
    - andnonsearchresult所havecontentallanduserquestion密切相close,youneed结合question,tosearchresultconduct甄别、filter.
    - toatcolumn举categoryquestion(如column举所have航班information),尽quantitywill答案controlin10wantpointbyinside,and告诉usercanviewsearchcome源、获completeinformation.优先provideinformationcomplete、most相closecolumn举item;如non必want,notwant主动告诉usersearchresultnotprovidecontent.
    - toat创ascategoryquestion(如写论文),请务必injust文segment落middlequoteto应参考编number,for example[citation:3][citation:5],notcanonlyin文chapter末tailquote.youneed解读and概括user题目require,choose合适format,充minute利usesearchresultanddraw重wantinformation,generatematchuserrequire、极具思想深degree、富havecreate力and专业property答案.you创as篇幅need尽maybe延long,toateachonewantpoint论述wantspeculateduser意graph,giveout尽maybe多angledegreereturn答wantpoint,and务必informationquantitybig、论述详尽.
    - ifreturn答verylong,请尽quantity结构化、minutesegment落总结.ifneedminutepointas答,尽quantitycontrolin5pointbyinside,andmerge相closecontent.
    - toat客观category问答,ifquestion答案non常简short,can适when补充oneto两sentence相closeinformation,by丰富content.
    - youneedaccording touserrequireandreturn答contentchoose合适、美观return答format,ensurecan读propertystrong.
    - youreturn答should综合多相closewebpagecomereturn答,notcanduplicatequoteonewebpage.
    - unlessuserrequire,否thenyoureturn答languageneedanduser提问languagemaintainone致.
    - output漂亮markdown format,contentmiddleaddonetheseandtheme相closeemoji表情符number.
    
    ## usermessagefor:
    {question}
    
    ## based onusersendmessage互联网searchresult:
    {search_context_details}
    PROMPT;

    private string $eventPrompt = <<<'PROMPT'
    # youisonenew闻eventgenerate器,userwillprovidesearchcontentandaskquestion.
    ## Current Timeis {data_now}  
    ## according touserquestion,you需fromuserprovidesearchcontentmiddle整理相closeevent,eventincludeeventname、eventtimeandevent概述.
    ### notice事item:
    1. **eventnameformat**:
       - ineventnamebackaddsearchquote编number,formatfor `[[citation:x]]`,编numbercome源atsearchcontentmiddlequotemark(如 `[[citation:1]]`).
       - ifoneevent涉and多quote,merge所have相closequote编number.
       - notwantin "description" middleaddquote.
    2. **timehandle**:
       - eventtime尽quantityprecisetomonthshare(如 "2023-05"),若searchcontentnotprovidespecificmonthshare,buthavefingeroutuphalfyearor者downhalfyear,canuse("2023 uphalfyear"),若nothavethen,useyearshare(如 "2023").
       - 若同oneeventin多quotemiddleout现,优先usemost早time.
       - 若timenotexplicit,according tocontextspeculatedmost早maybetime,andensure合理.
    3. **eventextractandfilter**:
       - **eventdefinition**:eventissearchcontentmiddlemention、具havetimeassociate(explicitorcanspeculated)独立事实、changeoractivity,includebutnot限atcreate、publish、open业、update、合as、activityetc.
       - according touserquestion,extractand之相closeevent,maintaindescription简洁,聚焦specifichair生thing.
       - **skipnoclosecontent**:
         - 纯静statedescription(如not变property、background介绍,notimechange).
         - datastatisticsor财务information(如营收、利润).
         - 主观comment、analyzeorspeculated(unlessandevent直接相close).
         - notimeassociateandandquestionnoclosedetail.
       - **保留原then**:as long ascontentandtime相closeandmatchquestiontheme,尽quantity保留forevent.
    4. **outputrequire**:
       - by JSON formatreturn,event按time倒序rowcolumn(from晚to早).
       - eacheventcontain "name"、"time"、"description" threefield.
       - 若searchcontentnot足bygenerateevent,returnnullarray `[]`,avoid凭null臆造.
    
    ## outputexample:
    ```json
    [
        {
            "name": "someeventhair生[[citation:3]] [[citation:5]]",
            "time": "2024-11",
            "description": "someeventin2024year11monthhair生,specific情况概述."
        },
        {
            "name": "另oneeventstart[[citation:1]]",
            "time": "2019-05",
            "description": "另oneeventat2019year5monthstart,简wantdescription."
        }
    ]
    ```
    ## useinstruction
    - user需providesearchcontent(containquotemark如 [[citation:x]])andspecificquestion.
    - according toquestion,fromsearchcontentmiddleextractmatcheventdefinitioncontent,按requiregenerateoutput.
    - 若question涉andcurrenttime,based on {date_now} conduct推算.
    
    ## quote
    {citations}
    
    ## searchcontextdetail:
    {search_context_details}

    ## 请直接output json format:
    ```json
    PROMPT;

    private string $filterSearchContextPrompt = <<<'PROMPT'
    ## Current Time
    {date_now}
    
    ## task
    return"search contexts"middleand"search keywords"haveassociateproperty 20 to 50  index.
    
    ## require
    - forbid直接return答userquestion,one定wantreturnanduserquestionhaveassociatepropertyindex.
    - search contextsformatfor "[[x]] content" ,itsmiddle x issearch contextsindex.x notcangreater than 50
    - 请bycorrect JSON formatreplyfilterbackindex,for example:[0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19]
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
        // 直接use思维导graphgenerate ppt
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
        // goexcept掉quote,avoid思维导graphmiddleout现quote
        $responseMessage = preg_replace('/\[\[citation:(\d+)]]/', '', $responseMessage);
        // observetosystemhint词variable串,看看isnotisnothavecopyonesharequestion
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
            // according to总结 + useroriginalquestionalready经cangenerate思维导graph,notneedagain传入historymessage
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
            // go掉换line符
            $mindMapMessage = str_replace('\n', '', $mindMapMessage);
            $this->logger->info(Json::encode([
                'log_title' => 'mindSearch mindMap response',
                'log_content' => $mindMapMessage,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            return $this->stripMarkdownCodeBlock($mindMapMessage, 'markdown');
        } catch (Throwable $e) {
            $this->logger->error(sprintf('mindSearch generate思维导grapho clockhair生error:%s,file:%s,line:%s trace:%s, will generate again.', $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString()));
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
        // 清洗search contexts
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
        // 超passmostbigvaluethen直接truncate,avoidresponsetoo久
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
            $this->logger->error(sprintf('mindSearch generateevento clockhair生error:%s,file:%s,line:%s trace:%s, will generate again.', $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString()));
            // eventgenerateoftennotis json
            return [];
        }
    }

    /**
     * stream总结 - 原havemethod.
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
            $this->logger->error(sprintf('mindSearch parseresponseo clockhair生error:%s,file:%s,line:%s trace:%s, will generate again.', $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString()));
            throw $e;
        }
    }

    /**
     * nonstream总结 - newmethod,适useattoolcall.
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
            $this->logger->error(sprintf('mindSearch summarizeNonStreaming parseresponseo clockhair生error:%s,file:%s,line:%s trace:%s', $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString()));
            throw $e;
        }
    }

    /**
     * letbigmodel虚null拆解子question.
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
                // nothavecalltool,尝试fromresponsemiddleparse json
                $subquestions = $this->getSubQuestionsFromLLMStringResponse($generateSearchKeywordsResponse, $userMessage);
            }
            return $subquestions;
        } catch (Throwable $e) {
            $this->logger->error(sprintf('mindSearch getSearchResults generatesearch词o clockhair生error:%s,file:%s,line:%s trace:%s, will generate again.', $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString()));
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
     * batchquantitysearchback,filter掉duplicate search contexts.
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
        // getsystemhint词
        $searchContextsString = '';
        // 清洗searchresult
        foreach ($searchContexts as $index => $context) {
            // can传入webpagedetail,by便moregoodfilter
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
            $this->logger->error(sprintf('mindSearch getSearchResults parseresponseo clockhair生error:%s,file:%s,line:%s trace:%s, will generate again.', $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString()));
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
            $this->logger->error(sprintf('mindSearch getSearchResults searchcontento clockhair生error:%s,file:%s,line:%s trace:%s, will generate again.', $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString()));
        } finally {
            ! empty($searchArrayList) && $searchArrayList = array_merge(...$searchArrayList);
            $costTime = TimeUtil::getMillisecondDiffFromNow($start);
            $this->logger->info(sprintf(
                'getSearchResults searchall部key词 end计o clock 耗o clock:%s second',
                number_format($costTime / 1000, 2)
            ));
        }

        // record阅读word count
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
     * letbigmodel虚null拆解子question,to热梗/实o clock拆解willnotgood.
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
            // eachtimeretry清null之frontcontext
            $llmConversationId = (string) IdGenerator::getSnowId();
            $llmHistoryMessage = DelightfulChatAggregateSearchReqDTO::generateLLMHistory($delightfulChatMessageHistory, $llmConversationId);
            $queryVo->setMessageHistory($llmHistoryMessage)->setConversationId($llmConversationId);
            return $this->generateSearchKeywords($queryVo);
        });
        $costTime = TimeUtil::getMillisecondDiffFromNow($start);
        $this->logger->info(sprintf(
            'getSearchResults according touseroriginalquestion,generatesearch词,end计o clock,耗o clock::%s second',
            number_format($costTime / 1000, 2)
        ));
        // bigmodelnothave拆孙questioniso clock,直接use子questionsearch
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
                'error' => 'mindSearch getSearchResults searchWithBing getsearchresulto clockhair生error',
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
            'mindSearch getSearchResults searchWithBing getsearchresult,end计o clock,耗o clock:%s second',
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
     * according tooriginalquestion + searchresult,按finger定countdimension拆解question.
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
            // use array_map and join functioncome模拟 Python middle join method
            $contextString = '';
            foreach ($searchContexts as $searchContext) {
                $contextString .= $searchContext->getSnippet() . "\n\n";
            }
            // use str_replace functioncomereplace占位符
            // 带upyearmonthdayo clockminutesecond,avoidduplicatequestion
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
                // nothavecalltool,尝试fromresponsemiddleparse json
                $subQuestions = $this->getSubQuestionsFromLLMStringResponse($relatedQuestionsResponse, $userMessage);
                // bigmodel认fornotneedgenerateassociatequestion,直接拿userquestion
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
        // according to backendvalue,certainuse哪searchengine
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
            $matchString = ''; // nothave找to JSON data
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
     * build总结systemhint词 - publicmethod,useat复usecode
     */
    private function buildSummarizeSystemPrompt(AISearchCommonQueryVo $queryVo): string
    {
        $searchContexts = $queryVo->getSearchContexts();
        $userMessage = $queryVo->getUserMessage();
        $searchKeywords = $queryVo->getSearchKeywords();
        $searchKeywords = Json::encode($searchKeywords, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // 清洗search contexts
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

        // 超passmostbigvaluethen直接truncate,avoidresponsetoo久
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
        // 捕捉 LLMNetworkException exception,retryonetime
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
                'mindSearch getSubQuestionsFromLLMStringResponse 提问:%s bigmodelresponse:%s, analyzebackresult:%s',
                $userMessage,
                // go掉换line符
                str_replace(PHP_EOL, '', $llmResponse),
                Json::encode($subQuestions)
            ));
            // haveo clockwillreturn多维array,inthiswithinfilter
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
