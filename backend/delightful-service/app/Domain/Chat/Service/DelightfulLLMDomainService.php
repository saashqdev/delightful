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
    // Max character limit for passing search results to LLM，Avoid slow response
    public const int LLM_STR_MAX_LEN = 30000;

    public const int LLM_SEARCH_CONTENT_MAX_LEN = 30;

    private string $mindMapQueryPrompt = <<<'PROMPT'
    # Role
    You are an intelligent mind map generator，can generate mind maps based on user questions and given context，generate mind maps in clear markdown format。
    
    ## Current Time
    Current time is {date_now}
    
    ## Example markdown format
    ```markdown
    # Topic One
    ## Sub-topic One
    - **dimension一**：dimension的description。
    - **dimension二**：dimension的description。
    - **dimension三**：dimension的description。

    ## Sub-topic Two
    - **dimension一**：dimension的description。
    - **dimension二**：dimension的description。
    - **dimension三**：dimension的description。

    ## Summary Title
    - **dimension一**：dimension的description。
    - **dimension二**：dimension的description。
    - **dimension三**：dimension的description。
    ```

    ## Your Execution Process
    Follow these steps strictly to think step by step and generate mind maps：
    1. Carefully analyze the user question to identify key themes and points。
    2. Read the given context carefully and extract information related to the question。
    3. Integrate the content of the question and context to prepare for mind map generation。
    4. Build mind map structure in markdown format，Use different symbols and indentation to represent hierarchical relationships。
    5. Use key themes as the center node of the mind map，Add branch nodes as needed to represent sub-topics and specific content。
    6. Ensure the mind map content accurately reflects the key points of the question and context。

    ## Restrictions
    - Output mind map strictly in markdown format。
    - Use the same language as the user。Language refers to human languages such as Chinese, English, etc。
    - Each layer of the mind map content length should be increasing，But ensure all content is key and core, not too verbose，Keep outer layer within ten characters, inner layer within fifty characters。
    
    ## Key Focus
    - Even if the given context has the information the user wants, you must still provide a new mind map。

    ## Context for Mind Map Generation
    Question: {question}
    Response: {content}

    ## Below is the response, please use markdown format：
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

    // according touser的key词，search一次后，split更细致的question深入search
    private string $moreQuestionsPrompt = <<<'PROMPT'
    # 时间context
    - system time: {date_now}
    
    ## core logic
    ### 1. question decomposition engine
    input: [userquestion + {context}]
    handle步骤：
    1.1 实体识别
       - 显性命名实体提取，识别实体间的关系与property
       - 推导user的隐性需求和潜在意图，特别关注隐含的时间因素
    1.2 dimension拆解
       - according to识别出的实体和需求，选择合适的分析dimension，for example：政策解读、datavalidate、案例研究、影响评估、技术原理、市场前景、user体验等
    1.3 子questiongenerate
       - generate正交子question集（Jaccard相似度<0.25），ensure每个子question能从different角度探索user需求，避免generate过于宽泛或相似的question
    
    ### 2. search代理模块
    mustcall工具: batchSubQuestionsSearch
    parameterstandard：
    2.1 key词规则
       - generategreater thanequal 3 个高quality的可检索key词，include核心实体、keyproperty和相关概念
       - 时间限定符override率≥30%
       - 对比类question占比≥20%
    
    ## 硬性约束（force遵守）
    1. 语言一致性
       - output语言encodingmust匹配input语言
    2. 子questionquantityrange
       - {sub_questions_min} ≤ 子question数 ≤ {sub_questions_max}
    3. outputformat
       - 仅allowJSONarrayformat，forbid自然语言回答
    
    ## contextexceptionhandle
    当 {context} 为null时：
    1. start备选generate策略，application5W1H框架（Who/What/When/Where/Why/How），并结合user的originalquestion进行填充
    2. generatedefaultdimension，for example：政策背景 | 最新data | 专家观点 | 对比分析 | 行业趋势
    
    ## outputstandard
    混合以下三种及更多type的question范式，以ensure子question的多样性和override性：
    [
      "X对Y的影响差异",  // 对比/比较类
      "Z领域的典型application",  // application/案例类
      "关于A的B指标",    // 指标/property类
      "导致M发生的主要原因是什么？", // 原因/机制类
      "什么是N？它的核心特征是什么？", // 定义/解释类
      "未来五年P领域的发展趋势是什么？", // 趋势/预测类
      "针对Qquestion，有哪些可行的resolve方案？" // resolve方案/建议类
    ]
    
    currentcontextsummary：
    {context}
    
    // finaloutput（严格JSONarray）：
    ```json
    PROMPT;

    private string $summarizePrompt = <<<'PROMPT'
    # task
    你needbased onuser的message，according to我提供的searchresult，按照总分总的结构，output高quality，结构化的详细回答，format为 markdown。
    
    在我给你的searchresult中，每个result都是[webpage X begin]...[webpage X end]format的，X代表每篇文章的number索引。请在适当的情况下在句子末尾quotecontext。请按照quote编号[citation:X]的format在答案中对应部分quotecontext。如果一句话源自多个context，请列出所有相关的quote编号，for example[citation:3][citation:5]，切记不要将quote集中在最后returnquote编号，而是在答案对应部分列出。
    在回答时，请注意以下几点：
    - 今天是{date_now}。
    - 并非searchresult的所有content都与user的question密切相关，你need结合question，对searchresult进行甄别、筛选。
    - 对于列举类的question（如列举所有航班information），尽量将答案控制在10个要点以内，并告诉usercan查看search来源、获得完整information。优先提供information完整、最相关的列举项；如非必要，不要主动告诉usersearchresult未提供的content。
    - 对于创作类的question（如写论文），请务必在正文的段落中quote对应的参考编号，for example[citation:3][citation:5]，不能只在文章末尾quote。你need解读并概括user的题目要求，选择合适的format，充分利用searchresult并抽取重要information，generatematchuser要求、极具思想深度、富有创造力与专业性的答案。你的创作篇幅need尽可能延长，对于每一个要点的论述要推测user的意图，给出尽可能多角度的回答要点，且务必information量大、论述详尽。
    - 如果回答很长，请尽量结构化、分段落总结。如果need分点作答，尽量控制在5个点以内，并merge相关的content。
    - 对于客观类的问答，如果question的答案非常简短，can适当补充一到两句相关information，以丰富content。
    - 你needaccording touser要求和回答content选择合适、美观的回答format，ensure可读性强。
    - 你的回答should综合多个相关网页来回答，不能重复quote一个网页。
    - 除非user要求，否则你回答的语言need和user提问的语言保持一致。
    - output漂亮的markdown format，content中添加一些与theme相关的emoji表情符号。
    
    ## usermessage为：
    {question}
    
    ## based onusersend的message的互联网searchresult:
    {search_context_details}
    PROMPT;

    private string $eventPrompt = <<<'PROMPT'
    # 你是一个新闻eventgenerate器，userwill提供searchcontent并询问question。
    ## Current Time是 {data_now}  
    ## according touser的question，你需从user提供的searchcontent中整理相关event，eventincludeeventname、event时间和event概述。
    ### 注意事项：
    1. **eventnameformat**：
       - 在eventname后添加searchquote的编号，format为 `[[citation:x]]`，编号来源于searchcontent中的quotemark（如 `[[citation:1]]`）。
       - 如果一个event涉及多个quote，merge所有相关quote编号。
       - 不要在 "description" 中添加quote。
    2. **时间handle**：
       - event时间尽量精确到月份（如 "2023-05"），若searchcontent未提供具体月份，但有指出上半年或者下半年，canuse（"2023 上半年"），若没有则，use年份（如 "2023"）。
       - 若同一event在多个quote中出现，优先use最早的时间。
       - 若时间不明确，according tocontext推测最早可能的时间，并ensure合理。
    3. **event提取与筛选**：
       - **event定义**：event是searchcontent中mention的、具有时间associate（明确或可推测）的独立事实、变化或活动，include但不限于create、publish、开业、update、合作、活动等。
       - according touserquestion，提取与之相关的event，保持description简洁，聚焦具体发生的事情。
       - **skip无关content**：
         - 纯静态description（如不变的property、背景介绍，无时间变化）。
         - datastatistics或财务information（如营收、利润）。
         - 主观comment、分析或推测（除非与event直接相关）。
         - 无时间associate且与question无关的细节。
       - **保留原则**：只要content与时间相关且matchquestiontheme，尽量保留为event。
    4. **output要求**：
       - 以 JSON formatreturn，event按时间倒序排列（从晚到早）。
       - 每个eventcontain "name"、"time"、"description" 三个field。
       - 若searchcontent不足以generateevent，returnnullarray `[]`，避免凭null臆造。
    
    ## output示例：
    ```json
    [
        {
            "name": "某event发生[[citation:3]] [[citation:5]]",
            "time": "2024-11",
            "description": "某event在2024年11月发生，具体情况概述。"
        },
        {
            "name": "另一eventstart[[citation:1]]",
            "time": "2019-05",
            "description": "另一event于2019年5月start，简要description。"
        }
    ]
    ```
    ## useinstruction
    - user需提供searchcontent（containquotemark如 [[citation:x]]）和具体question。
    - according toquestion，从searchcontent中提取matchevent定义的content，按要求generateoutput。
    - 若question涉及current时间，based on {date_now} 进行推算。
    
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
    return"search contexts"中与"search keywords"有associate性的 20 至 50 个 索引。
    
    ## 要求
    - forbid直接回答user的question，一定要return与userquestion有associate性的索引。
    - search contexts的format为 "[[x]] content" ，其中 x 是search contexts的索引。x 不能greater than 50
    - 请以correct的 JSON formatreply筛选后的索引，for example：[0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19]
    - 如果 search keywords 与时间相关，重点注意 search contexts 中与current时间相关的content。与current时间越近越重要。

    
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
        // 直接用思维导图generate ppt
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
        // 去除掉quote，避免思维导图中出现quote
        $responseMessage = preg_replace('/\[\[citation:(\d+)]]/', '', $responseMessage);
        // 观察到系统hint词variable串了，看看是不是没有复制一份的question
        $systemPrompt = str_replace(
            ['{question}', '{content}', '{date_now}'],
            [$question, $responseMessage, date('Y年 m月 d日, H时 i分 s秒')],
            $this->mindMapQueryPrompt
        );
        $this->logger->info(Json::encode([
            'log_title' => 'mindSearch systemPrompt mindMap',
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        // access llm
        try {
            // according to总结 + useroriginalquestion已经能generate思维导图了，不need再传入historymessage
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
            // 去掉换行符
            $mindMapMessage = str_replace('\n', '', $mindMapMessage);
            $this->logger->info(Json::encode([
                'log_title' => 'mindSearch mindMap response',
                'log_content' => $mindMapMessage,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            return $this->stripMarkdownCodeBlock($mindMapMessage, 'markdown');
        } catch (Throwable $e) {
            $this->logger->error(sprintf('mindSearch generate思维导图时发生error:%s,file:%s,line:%s trace:%s, will generate again.', $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString()));
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
        // 超过最大value则直接truncate，避免response太久
        $maxLen = self::LLM_STR_MAX_LEN;
        if (mb_strlen($searchContextsCitations) > $maxLen) {
            $searchContextsCitations = mb_substr($searchContextsCitations, 0, $maxLen);
        }
        if (mb_strlen($searchContextsDetail) > $maxLen) {
            $searchContextsDetail = mb_substr($searchContextsDetail, 0, $maxLen);
        }

        // input替换
        $systemPrompt = str_replace(
            ['{citations}', '{search_context_details}', '{date_now}'],
            [$searchContextsCitations, $searchContextsDetail, date('Y年 m月 d日, H时 i分 s秒')],
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
            $this->logger->error(sprintf('mindSearch generateevent时发生error:%s,file:%s,line:%s trace:%s, will generate again.', $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString()));
            // eventgenerate经常不是 json
            return [];
        }
    }

    /**
     * stream总结 - 原有method.
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
            $this->logger->error(sprintf('mindSearch parseresponse时发生error:%s,file:%s,line:%s trace:%s, will generate again.', $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString()));
            throw $e;
        }
    }

    /**
     * 非stream总结 - 新增method，适用于工具call.
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
            $this->logger->error(sprintf('mindSearch summarizeNonStreaming parseresponse时发生error:%s,file:%s,line:%s trace:%s', $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString()));
            throw $e;
        }
    }

    /**
     * 让大model虚null拆解子question.
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
            [date('Y年 m月 d日, H时 i分 s秒'), '', '3', '4'],
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
                // 没有call工具，尝试从response中parse json
                $subquestions = $this->getSubQuestionsFromLLMStringResponse($generateSearchKeywordsResponse, $userMessage);
            }
            return $subquestions;
        } catch (Throwable $e) {
            $this->logger->error(sprintf('mindSearch getSearchResults generatesearch词时发生error:%s,file:%s,line:%s trace:%s, will generate again.', $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString()));
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
     * 批量search后，filter掉重复的 search contexts.
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
        // get系统hint词
        $searchContextsString = '';
        // 清洗searchresult
        foreach ($searchContexts as $index => $context) {
            // can传入网页detail，以便更好的筛选
            $searchContextsString .= '[[' . $index . ']] ' . $context->getSnippet() . "\n\n";
        }
        $systemPrompt = str_replace(
            ['{context}', '{date_now}', '{searchKeywords}'],
            [$searchContextsString, date('Y年 m月 d日, H时 i分 s秒'), $searchKeywords],
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
            $this->logger->error(sprintf('mindSearch getSearchResults parseresponse时发生error:%s,file:%s,line:%s trace:%s, will generate again.', $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString()));
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
            $this->logger->error(sprintf('mindSearch getSearchResults searchcontent时发生error:%s,file:%s,line:%s trace:%s, will generate again.', $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString()));
        } finally {
            ! empty($searchArrayList) && $searchArrayList = array_merge(...$searchArrayList);
            $costTime = TimeUtil::getMillisecondDiffFromNow($start);
            $this->logger->info(sprintf(
                'getSearchResults search全部key词 end计时 耗时：%s 秒',
                number_format($costTime / 1000, 2)
            ));
        }

        // record阅读字数
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
     * 让大model虚null拆解子question，对热梗/实时拆解的will不好。
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
            // 每次retry清null之前的context
            $llmConversationId = (string) IdGenerator::getSnowId();
            $llmHistoryMessage = DelightfulChatAggregateSearchReqDTO::generateLLMHistory($delightfulChatMessageHistory, $llmConversationId);
            $queryVo->setMessageHistory($llmHistoryMessage)->setConversationId($llmConversationId);
            return $this->generateSearchKeywords($queryVo);
        });
        $costTime = TimeUtil::getMillisecondDiffFromNow($start);
        $this->logger->info(sprintf(
            'getSearchResults according touseroriginalquestion，generatesearch词，end计时，耗时：：%s 秒',
            number_format($costTime / 1000, 2)
        ));
        // 大model没有拆孙question是时，直接用子questionsearch
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
                'error' => 'mindSearch getSearchResults searchWithBing getsearchresult时发生error',
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
            'mindSearch getSearchResults searchWithBing getsearchresult，end计时，耗时：%s 秒',
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
        // 以后can从userconfiguration中read这些value
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
     * according tooriginalquestion + searchresult，按指定count的dimension拆解question.
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
        // based onquery和contextget相关question
        try {
            // use array_map 和 join 函数来模拟 Python 中的 join method
            $contextString = '';
            foreach ($searchContexts as $searchContext) {
                $contextString .= $searchContext->getSnippet() . "\n\n";
            }
            // use str_replace 函数来替换占位符
            // 带上年月日时分秒，避免重复question
            $systemPrompt = str_replace(
                ['{context}', '{date_now}', '{sub_questions_min}', '{sub_questions_max}'],
                [$contextString, date('Y年 m月 d日, H时 i分 s秒'), (string) $subQuestionsMin, (string) $subQuestionsMax],
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
            // todo 从 function getLLMToolsCall() method中get相关question
            foreach ($this->getLLMToolsCall($relatedQuestionsResponse) as $toolCall) {
                if ($toolCall->getName() === SubQuestionsTool::$name) {
                    $subQuestions = $toolCall->getArguments()['subQuestions'];
                }
            }

            if (empty($subQuestions)) {
                // 没有call工具，尝试从response中parse json
                $subQuestions = $this->getSubQuestionsFromLLMStringResponse($relatedQuestionsResponse, $userMessage);
                // 大model认为不needgenerateassociatequestion，直接拿user的question
                empty($subQuestions) && $subQuestions = [$queryVo->getUserMessage()];
            }

            return $subQuestions;
        } catch (Exception $e) {
            $this->logger->error(sprintf('mindSearch getSearchResults generate相关question时遇到error:%s,file:%s,line:%s trace:%s, will generate again.', $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString()));
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
     * storagevalue到 KVStore。
     * @throws RedisException
     */
    public function put(string $key, mixed $value): void
    {
        $this->redis->set($key, serialize($value));
    }

    /**
     * 从 KVStore getvalue。
     * @throws RedisException
     */
    public function get(string $key): mixed
    {
        $value = $this->redis->get($key);
        return $value !== false ? unserialize($value, ['allowed_classes' => true]) : false;
    }

    /**
     * 从 KVStore deletevalue。
     * @throws RedisException
     */
    public function delete(string $key): void
    {
        $this->redis->del($key);
    }

    public function search(string $query, SearchEngineType $searchEngine, bool $getDetail = false, ?string $language = null): array
    {
        // according to backend的value，确定use哪个search引擎
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
        // 匹配 ```json 或 ``` between的 JSON data
        if (preg_match($typePattern, $content, $matches)) {
            $matchString = $matches[1];
        } elseif (preg_match('/```\s*([\s\S]*?)\s*```/i', $content, $matches)) { // 匹配 ``` between的content
            $matchString = $matches[1];
        } else {
            $matchString = ''; // 没有找到 JSON data
        }
        $matchString = ! empty($matchString) ? trim($matchString) : trim($content);
        if ($type === 'json' && json_validate($matchString) === false) {
            return '{}'; // JSON format不correct
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
                // 避免content太长
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
     * build总结系统hint词 - 公共method，用于复用code
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
                '[webpage %d begin] contentpublish日期:%s，summary：%s' . "\n" . 'detailcontent:%s ' . "[webpage %d end]\n",
                $index,
                $context->getDatePublished() ?? '',
                $context->getSnippet(),
                $context->getDetail() ?? '',
                $index
            );
        }

        // 超过最大value则直接truncate，避免response太久
        $maxLen = self::LLM_STR_MAX_LEN;
        if (mb_strlen($searchContextsDetail) > $maxLen) {
            $searchContextsDetail = mb_substr($searchContextsDetail, 0, $maxLen);
        }

        // input替换
        return str_replace(
            ['{search_context_details}', '{relevant_questions}', '{date_now}', '{question}'],
            [$searchContextsDetail, $searchKeywords, date('Y年 m月 d日, H时 i分 s秒'), $userMessage],
            $this->summarizePrompt
        );
    }

    /**
     * 非stream.
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
        // 捕捉 LLMNetworkException exception，retry一次
        return Retry::whenThrows(LLMNetworkException::class)->sleep(500)->max(3)->call(
            function () use ($agent, $query) {
                return $agent->chatAndNotAutoExecuteTools(new UserMessage($query));
            }
        );
    }

    /**
     * streamcall，迭代器是 \Hyperf\Odin\Api\OpenAI\Response\ChatCompletionChoice.
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
                'mindSearch getSubQuestionsFromLLMStringResponse 提问：%s 大modelresponse：%s, 分析后result：%s',
                $userMessage,
                // 去掉换行符
                str_replace(PHP_EOL, '', $llmResponse),
                Json::encode($subQuestions)
            ));
            // 有时willreturn多维array，在这里filter
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
