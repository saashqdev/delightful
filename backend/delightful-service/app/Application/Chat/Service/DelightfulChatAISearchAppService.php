<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Chat\Service;

use App\Application\ModelGateway\Mapper\ModelGatewayMapper;
use App\Application\ModelGateway\Service\ModelConfigAppService;
use App\Domain\Chat\DTO\AISearch\Request\DelightfulChatAggregateSearchReqDTO;
use App\Domain\Chat\DTO\AISearch\Response\DelightfulAggregateSearchSummaryDTO;
use App\Domain\Chat\DTO\Message\ChatMessage\AggregateAISearchCardMessage;
use App\Domain\Chat\DTO\Message\ChatMessage\Item\DeepSearch\SearchDetailItem;
use App\Domain\Chat\DTO\Message\StreamMessage\StreamMessageStatus;
use App\Domain\Chat\DTO\Message\StreamMessage\StreamOptions;
use App\Domain\Chat\DTO\Stream\CreateStreamSeqDTO;
use App\Domain\Chat\Entity\Items\SeqExtra;
use App\Domain\Chat\Entity\DelightfulConversationEntity;
use App\Domain\Chat\Entity\DelightfulMessageEntity;
use App\Domain\Chat\Entity\DelightfulSeqEntity;
use App\Domain\Chat\Entity\ValueObject\AggregateSearch\AggregateAISearchCardResponseType;
use App\Domain\Chat\Entity\ValueObject\AggregateSearch\SearchDeepLevel;
use App\Domain\Chat\Entity\ValueObject\AISearchCommonQueryVo;
use App\Domain\Chat\Entity\ValueObject\ConversationType;
use App\Domain\Chat\Entity\ValueObject\LLMModelEnum;
use App\Domain\Chat\Entity\ValueObject\MessageType\ChatMessageType;
use App\Domain\Chat\Entity\ValueObject\MessageType\ControlMessageType;
use App\Domain\Chat\Service\DelightfulChatDomainService;
use App\Domain\Chat\Service\DelightfulConversationDomainService;
use App\Domain\Chat\Service\DelightfulLLMDomainService;
use App\Domain\Contact\Entity\DelightfulUserEntity;
use App\Domain\Contact\Service\DelightfulUserDomainService;
use App\Domain\ModelGateway\Entity\ValueObject\ModelGatewayDataIsolation;
use App\ErrorCode\ChatErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\Context\CoContext;
use App\Infrastructure\Util\HTMLReader;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use App\Infrastructure\Util\Time\TimeUtil;
use Hyperf\Codec\Json;
use Hyperf\Coroutine\Coroutine;
use Hyperf\Coroutine\Exception\ParallelExecutionException;
use Hyperf\Coroutine\Parallel;
use Hyperf\Engine\Channel;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Odin\Api\Response\ChatCompletionChoice;
use Hyperf\Odin\Contract\Model\ModelInterface;
use Hyperf\Odin\Memory\MessageHistory;
use Hyperf\Odin\Message\AssistantMessage;
use Hyperf\Redis\Redis;
use Hyperf\Snowflake\IdGeneratorInterface;
use JetBrains\PhpStorm\ArrayShape;
use Psr\Log\LoggerInterface;
use RedisException;
use Throwable;

/**
 * chatmessage相关.
 * @deprecated use DelightfulChatAISearchV2AppService 代替
 */
class DelightfulChatAISearchAppService extends AbstractAppService
{
    protected LoggerInterface $logger;

    public function __construct(
        private readonly DelightfulLLMDomainService $delightfulLLMDomainService,
        private readonly IdGeneratorInterface $idGenerator,
        protected readonly DelightfulConversationDomainService $delightfulConversationDomainService,
        protected readonly DelightfulUserDomainService $delightfulUserDomainService,
        protected readonly DelightfulChatDomainService $delightfulChatDomainService,
        protected readonly Redis $redis
    ) {
        $this->logger = di()->get(LoggerFactory::class)->get(get_class($this));
    }

    /**
     * @throws Throwable
     */
    public function aggregateSearch(DelightfulChatAggregateSearchReqDTO $dto): void
    {
        $conversationId = $dto->getConversationId();
        $topicId = $dto->getTopicId();
        $searchKeyword = $dto->getUserMessage();
        // ai准备start发message了,end输入status
        $this->delightfulConversationDomainService->agentOperateConversationStatusV2(
            ControlMessageType::EndConversationInput,
            $conversationId,
            $topicId
        );
        $this->logger->info(sprintf('mindSearch aggregateSearch startaggregatesearch  searchKeyword：%s searchtype：%s', $searchKeyword, $dto->getSearchDeepLevel()->name));
        $antiRepeatKey = md5($conversationId . $topicId . $searchKeyword);
        // 防重(不知道哪来的bug):如果同一conversation同一话题下,2秒内有重复的message,不触发process
        if (! $this->redis->set($antiRepeatKey, '1', ['nx', 'ex' => 2])) {
            return;
        }
        // delightful-api 二期要求传入user id
        $agentConversationEntity = $this->delightfulConversationDomainService->getConversationByIdWithoutCheck($conversationId);
        if (! $agentConversationEntity) {
            ExceptionBuilder::throw(ChatErrorCode::CONVERSATION_NOT_FOUND);
        }
        // 计费用，传入的是触发assistant的user id和organization code
        $dto->setUserId($agentConversationEntity->getReceiveId());
        $dto->setOrganizationCode($agentConversationEntity->getReceiveOrganizationCode());
        if (empty($dto->getRequestId())) {
            $requestId = CoContext::getRequestId() ?: (string) $this->idGenerator->generate();
            CoContext::setRequestId($requestId);
            $dto->setRequestId($requestId);
        }
        $dto->setAppMessageId((string) $this->idGenerator->generate());

        try {
            // 1.sendping pong响应,代表startreply
            $this->sendPingPong($dto);
            // 获取 im 中指定conversation下某个话题的历史message，作为 llm 的历史message
            $rawHistoryMessages = $this->getDelightfulChatMessages($dto->getConversationId(), $dto->getTopicId());
            $dto->setDelightfulChatMessageHistory($rawHistoryMessages);
            // 3.0 sendsearch深度
            $this->sendSearchDeepLevel($dto);
            // 2.searchuser问题.这里一定会split一次关联问题
            $simpleSearchResults = $this->searchUserQuestion($dto);
            // 3.according to原始问题 + search结果，按多个维度拆解关联问题.
            // 3.1 generate关联问题并send给前端
            $associateQuestionsQueryVo = $this->getAssociateQuestionsQueryVo($dto, $simpleSearchResults['search'] ?? []);
            $associateQuestions = $this->generateAndSendAssociateQuestions($dto, $associateQuestionsQueryVo, '0');
            // 3.2 according to关联问题，发起简单search（不拿网页详情),并filter掉重复或者与问题关联性不高的网页内容
            $noRepeatSearchContexts = $this->generateSearchResults($dto, $associateQuestions);
            $this->sleepToFixBug();
            // 3.4 according tosearch深度，决定是否continuesearch关联问题的子问题
            $readPagesDetailChannel = new Channel(count($associateQuestions));
            // 3.4.a 深度search
            if ($dto->getSearchDeepLevel() === SearchDeepLevel::DEEP) {
                $this->deepSearch($dto, $associateQuestions, $noRepeatSearchContexts, $readPagesDetailChannel);
            } else {
                // 3.4.b 简单search
                $readPagesDetailChannel = null;
                $this->simpleSearch($dto, $associateQuestions, $noRepeatSearchContexts);
            }
            // use channel 通信，精读过程中就pushmessage给前端
            $associateQuestionIds = array_keys($associateQuestions);
            $this->sendLLMResponseForAssociateQuestions($dto, $associateQuestionIds, $readPagesDetailChannel);
            $this->sleepToFixBug(0.3);
            // 4. according to每个关联问题reply，generate总结.
            $summarize = $this->generateAndSendSummary($dto, $noRepeatSearchContexts, $associateQuestions);
            // 5. according to总结，generate额外内容（思维导图、PPT、事件等）
            if ($dto->getSearchDeepLevel() === SearchDeepLevel::DEEP) {
                $this->generateAndSendExtra($dto, $noRepeatSearchContexts, $summarize);
            }
            // 6. sendping pong响应,代表endreply
            $this->sendPingPong($dto);
        } catch (Throwable $e) {
            // 7. 发生exception时，send终止message，并抛出exception
            $this->aiSendMessage(
                $dto->getConversationId(),
                (string) $this->idGenerator->generate(),
                '0',
                AggregateAISearchCardResponseType::TERMINATE,
                [],
                $dto->getAppMessageId(),
                $dto->getTopicId(),
            );
            $errMsg = [
                'function' => 'aggregateSearchError',
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ];
            $this->logger->error('mindSearch ' . Json::encode($errMsg, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            throw $e;
        }
    }

    /**
     * 麦吉互联网search简单版，适配process，仅支持简单search.
     * @throws Throwable
     * @throws RedisException
     */
    public function easyInternetSearch(DelightfulChatAggregateSearchReqDTO $dto): ?DelightfulAggregateSearchSummaryDTO
    {
        $conversationId = $dto->getConversationId();
        $topicId = $dto->getTopicId();
        $searchKeyword = $dto->getUserMessage();
        $antiRepeatKey = md5($conversationId . $topicId . $searchKeyword);
        // 防重(不知道哪来的bug):如果同一conversation同一话题下,2秒内有重复的message,不触发process
        if (! $this->redis->set($antiRepeatKey, '1', ['nx', 'ex' => 2])) {
            return null;
        }
        if (empty($dto->getRequestId())) {
            $requestId = CoContext::getRequestId() ?: (string) $this->idGenerator->generate();
            $dto->setRequestId($requestId);
        }
        $dto->setAppMessageId((string) $this->idGenerator->generate());

        try {
            // 1.searchuser问题.这里一定会split一次关联问题
            $simpleSearchResults = $this->searchUserQuestion($dto);

            // 2.according to原始问题 + search结果，按多个维度拆解关联问题.
            // 2.1 generate关联问题
            $associateQuestionsQueryVo = $this->getAssociateQuestionsQueryVo($dto, $simpleSearchResults['search'] ?? []);
            $associateQuestions = $this->generateAssociateQuestions($associateQuestionsQueryVo);
            // 2.2 according to关联问题，发起简单search（不拿网页详情),并filter掉重复或者与问题关联性不高的网页内容
            $this->sleepToFixBug();
            $noRepeatSearchContexts = $this->generateSearchResults($dto, $associateQuestions);

            // 3. according to每个关联问题reply，generate总结.
            return $this->generateSummary($dto, $noRepeatSearchContexts, $associateQuestions);
        } catch (Throwable $e) {
            // 4. 发生exception时，记录报错
            $errMsg = [
                'function' => 'aggregateSearchError',
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ];
            $this->logger->error('mindSearch ' . Json::encode($errMsg, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            throw $e;
        }
    }

    // generatenull关联问题的子问题

    /**
     * @param SearchDetailItem[] $noRepeatSearchContexts
     */
    public function simpleSearch(
        DelightfulChatAggregateSearchReqDTO $dto,
        array $associateQuestions,
        array $noRepeatSearchContexts
    ): void {
        $start = microtime(true);
        $parallel = new Parallel(5);

        // --- Start of new chunking logic ---
        $chunkedContexts = $this->chunkContextsByAssociateQuestions($associateQuestions, $noRepeatSearchContexts);
        // --- End of new chunking logic ---

        $questionIndex = 0;

        foreach ($associateQuestions as $questionId => $associateQuestion) {
            $questionId = (string) $questionId;
            $currentContextChunk = $chunkedContexts[$questionIndex] ?? []; // Get the pre-calculated chunk for this question

            $parallel->add(function () use ($questionId, $associateQuestion, $dto, $currentContextChunk) {
                $start = microtime(true);
                CoContext::setRequestId($dto->getRequestId());
                // 已generate关联问题，准备sendsearch结果
                $pageCount = random_int(30, 60);
                $onePageWords = random_int(50, 200);
                $totalWords = $pageCount * $onePageWords;

                $this->aiSendMessage(
                    $dto->getConversationId(),
                    (string) $this->idGenerator->generate(),
                    $questionId,
                    AggregateAISearchCardResponseType::SEARCH,
                    [
                        'search_keywords' => [],
                        // push前 5 个search结果。 且兼容历史数据，key use小驼峰
                        'search' => $this->getSearchData($currentContextChunk),
                        'total_words' => $totalWords,
                        // 全网资料总计
                        'match_count' => random_int(1000, 5000),
                        // 获取摘要的网页数量
                        'page_count' => $pageCount,
                    ],
                    $dto->getAppMessageId(),
                    $dto->getTopicId()
                );
                $this->logger->info(sprintf(
                    'getSearchResults 关联问题：%s 的null白子问题generate并push完毕 end计时，耗时 %s 秒',
                    $associateQuestion['title'],
                    TimeUtil::getMillisecondDiffFromNow($start) / 1000
                ));
            });
            ++$questionIndex;
        }
        $parallel->wait();
        $this->logger->info(sprintf(
            'getSearchResults 所有关联问题的null白子问题push完毕 end计时，耗时：%s 秒',
            TimeUtil::getMillisecondDiffFromNow($start) / 1000
        ));
    }

    /**
     * according to关联问题和问题的简单search，generate关联问题的子问题.(关联问题的子问题目前只用于前端展示，不会according to子问题再次search+精读).
     * @param SearchDetailItem[] $noRepeatSearchContexts
     */
    public function generateAndSendAssociateSubQuestions(
        DelightfulChatAggregateSearchReqDTO $dto,
        array $noRepeatSearchContexts,
        array $associateQuestions
    ): void {
        $start = microtime(true);
        $parallel = new Parallel(5);

        // --- Start of new chunking logic ---
        $chunkedContexts = $this->chunkContextsByAssociateQuestions($associateQuestions, $noRepeatSearchContexts);
        // --- End of new chunking logic ---

        $questionIndex = 0;

        foreach ($associateQuestions as $questionId => $associateQuestion) {
            $questionId = (string) $questionId;
            $currentContextChunk = $chunkedContexts[$questionIndex] ?? []; // Get the pre-calculated chunk for this question

            $parallel->add(function () use ($questionId, $associateQuestion, $dto, $noRepeatSearchContexts, $currentContextChunk) {
                CoContext::setRequestId($dto->getRequestId());
                $start = microtime(true);
                $associateQuestionsQueryVo = $this->getAssociateQuestionsQueryVo($dto, $noRepeatSearchContexts, $associateQuestion['title']);
                $associateQuestionsQueryVo->setMessageHistory(new MessageHistory());
                $associateSubQuestions = $this->delightfulLLMDomainService->getRelatedQuestions($associateQuestionsQueryVo, 2, 3);
                // todo 由于这里是对所有维度汇总后再精读，因此丢失了每个维度的数量，只能随机generate。
                // 等待前端调整渲染 ui
                $pageCount = random_int(30, 60);
                $onePageWords = random_int(200, 2000);
                $totalWords = $pageCount * $onePageWords;
                $searchResult = [
                    'search_keywords' => $associateSubQuestions,
                    'search' => $this->getSearchData($currentContextChunk),
                    'total_words' => $totalWords,
                    // 全网资料总计
                    'match_count' => random_int(1000, 5000),
                    // 获取摘要的网页数量
                    'page_count' => $pageCount,
                ];
                // 已generate关联问题，准备sendsearch结果
                $this->aiSendMessage(
                    $dto->getConversationId(),
                    (string) $this->idGenerator->generate(),
                    $questionId,
                    AggregateAISearchCardResponseType::SEARCH,
                    $searchResult,
                    $dto->getAppMessageId(),
                    $dto->getTopicId()
                );
                $this->logger->info(sprintf(
                    'getSearchResults 关联问题：%s 的子问题 %s generate并push完毕 end计时，耗时 %s 秒',
                    $associateQuestion['title'],
                    Json::encode($associateSubQuestions, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    TimeUtil::getMillisecondDiffFromNow($start) / 1000
                ));
            });
            ++$questionIndex;
        }
        $parallel->wait();
        $this->logger->info(sprintf(
            'getSearchResults 所有关联问题的子问题 generate并push完毕 end计时，耗时：%s 秒',
            TimeUtil::getMillisecondDiffFromNow($start) / 1000
        ));
    }

    #[ArrayShape([
        'search_keywords' => 'array',
        'search' => 'array',
        'total_words' => 'int',
        'match_count' => 'int',
        'page_count' => 'int',
    ])]
    public function searchUserQuestion(DelightfulChatAggregateSearchReqDTO $dto): array
    {
        $modelInterface = $this->getChatModel($dto->getOrganizationCode(), $dto->getUserId());
        $start = microtime(true);
        $llmConversationId = (string) IdGenerator::getSnowId();
        $llmHistoryMessage = DelightfulChatAggregateSearchReqDTO::generateLLMHistory($dto->getDelightfulChatMessageHistory(), $llmConversationId);
        $queryVo = (new AISearchCommonQueryVo())
            ->setUserMessage($dto->getUserMessage())
            ->setSearchEngine($dto->getSearchEngine())
            ->setFilterSearchContexts(false)
            ->setGenerateSearchKeywords(true)
            ->setMessageHistory($llmHistoryMessage)
            ->setConversationId($llmConversationId)
            ->setLanguage($dto->getLanguage())
            ->setUserId($dto->getUserId())
            ->setOrganizationCode($dto->getOrganizationCode())
            ->setModel($modelInterface);
        // according touser的上下文，拆解子问题。需要理解user想问什么，再去拆search关键词。
        $searchKeywords = $this->delightfulLLMDomainService->generateSearchKeywordsByUserInput($dto, $modelInterface);
        $queryVo->setSearchKeywords($searchKeywords);
        $searchResult = $this->delightfulLLMDomainService->getSearchResults($queryVo);
        $this->logger->info(sprintf(
            'getSearchResults searchUserQuestion 虚null拆解关键词并searchuser问题 end计时，耗时 %s 秒',
            microtime(true) - $start
        ));
        return $searchResult;
    }

    /**
     * generate并send关联问题.
     * @throws Throwable
     */
    public function generateAndSendAssociateQuestions(
        DelightfulChatAggregateSearchReqDTO $dto,
        AISearchCommonQueryVo $queryVo,
        string $questionParentId
    ): array {
        // generate关联问题
        $associateQuestions = $this->generateAssociateQuestions($queryVo);
        // 将关联问题push给前端
        $this->aiSendMessage(
            $dto->getConversationId(),
            (string) $this->idGenerator->generate(),
            $questionParentId,
            AggregateAISearchCardResponseType::ASSOCIATE_QUESTIONS,
            ['associate_questions' => $associateQuestions],
            $dto->getAppMessageId(),
            $dto->getTopicId(),
        );
        return $associateQuestions;
    }

    /**
     * according to原始问题 + search结果，按多个维度拆解问题.
     * @todo 支持传入维度的数量范围
     */
    public function generateAssociateQuestions(AISearchCommonQueryVo $queryVo): array
    {
        $associateQuestions = [];
        $start = microtime(true);
        $relatedQuestions = [];
        try {
            $relatedQuestions = $this->delightfulLLMDomainService->getRelatedQuestions($queryVo, 3, 5);
        } catch (Throwable $exception) {
            $errMsg = [
                'function' => 'generateAndSendAssociateQuestionsError',
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ];
            $this->logger->error('mindSearch generateAndSendAssociateQuestionsError ' . Json::encode($errMsg, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        }
        foreach ($relatedQuestions as $question) {
            $associateQuestions[(string) $this->idGenerator->generate()] = [
                'title' => $question,
                'llm_response' => null,
            ];
        }
        $this->logger->info(sprintf(
            'getSearchResults 问题：%s 关联问题: %s .according to原始问题 + search结果，按多个维度拆解关联问题并push完毕 end计时，耗时 %s 秒',
            $queryVo->getUserMessage(),
            Json::encode($relatedQuestions, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            TimeUtil::getMillisecondDiffFromNow($start) / 1000
        ));
        return $associateQuestions;
    }

    /**
     * @return SearchDetailItem[]
     * @throws Throwable
     */
    public function generateSearchResults(DelightfulChatAggregateSearchReqDTO $dto, array $associateQuestions): array
    {
        $start = microtime(true);
        // according to关联问题，发起简单search（不拿网页详情),并filter掉重复或者与问题关联性不高的网页内容
        $searchKeywords = array_column($associateQuestions, 'title');
        $queryVo = (new AISearchCommonQueryVo())
            ->setSearchKeywords($searchKeywords)
            ->setSearchEngine($dto->getSearchEngine())
            ->setLanguage($dto->getLanguage());
        $allSearchContexts = $this->delightfulLLMDomainService->getSearchResults($queryVo)['search'] ?? [];
        // filter重复内容
        $noRepeatSearchContexts = [];
        if (! empty($allSearchContexts)) {
            // 清洗search结果中的重复项
            $searchKeywords = array_column($associateQuestions, 'title');
            $modelInterface = $this->getChatModel($dto->getOrganizationCode(), $dto->getUserId());
            $queryVo = (new AISearchCommonQueryVo())
                ->setSearchKeywords($searchKeywords)
                ->setUserMessage($dto->getUserMessage())
                ->setModel($modelInterface)
                ->setConversationId((string) IdGenerator::getSnowId())
                ->setMessageHistory(new MessageHistory())
                ->setSearchContexts($allSearchContexts)
                ->setUserId($dto->getUserId())
                ->setOrganizationCode($dto->getOrganizationCode());
            // 与search关键词关联性最高且不重复的search结果
            $noRepeatSearchContexts = $this->delightfulLLMDomainService->filterSearchContexts($queryVo);
            $costMircoTime = TimeUtil::getMillisecondDiffFromNow($start);
            $this->logger->info(sprintf(
                'mindSearch getSearchResults filterSearchContexts 清洗search结果中的重复项 清洗前：%s 清洗后:%s end计时 累计耗时 %s 秒',
                count($allSearchContexts),
                count($noRepeatSearchContexts),
                $costMircoTime / 1000
            ));
        }
        if (empty($noRepeatSearchContexts)) {
            $noRepeatSearchContexts = $allSearchContexts;
        }
        foreach ($noRepeatSearchContexts as $key => $noRepeatSearchContext) {
            if (! $noRepeatSearchContext instanceof SearchDetailItem) {
                $noRepeatSearchContexts[$key] = new SearchDetailItem($noRepeatSearchContext);
            }
        }
        return $noRepeatSearchContexts;
    }

    /**
     * @throws Throwable
     */
    public function sendSearchDeepLevel(DelightfulChatAggregateSearchReqDTO $dto): void
    {
        $this->aiSendMessage(
            $dto->getConversationId(),
            (string) $this->idGenerator->generate(),
            '0',
            AggregateAISearchCardResponseType::SEARCH_DEEP_LEVEL,
            ['search_deep_level' => $dto->getSearchDeepLevel()],
            $dto->getAppMessageId(),
            $dto->getTopicId()
        );
    }

    /**
     * push最后一个关联问题的 llm 响应end标识.
     * @throws Throwable
     */
    public function sendAssociateQuestionResponse(DelightfulChatAggregateSearchReqDTO $dto, string $associateQuestionId): void
    {
        $content = ['llm_response' => '已经为您找到答案，请等待generate总结'];
        $this->aiSendMessage(
            $dto->getConversationId(),
            (string) $this->idGenerator->generate(),
            $associateQuestionId,
            AggregateAISearchCardResponseType::LLM_RESPONSE,
            $content,
            $dto->getAppMessageId(),
            $dto->getTopicId()
        );
    }

    /**
     * 精读的过程中，隔随机时间push一次关联问题search完毕给前端。
     * 完全精读完毕时，最后再推一次
     * @throws Throwable
     */
    public function sendLLMResponseForAssociateQuestions(
        DelightfulChatAggregateSearchReqDTO $dto,
        array $associateQuestionIds,
        ?Channel $readPagesDetailChannel
    ): void {
        foreach ($associateQuestionIds as $associateQuestionId) {
            $readPagesDetailChannel && $readPagesDetailChannel->pop(15);
            $this->sendAssociateQuestionResponse($dto, (string) $associateQuestionId);
        }
    }

    /**
     * @param SearchDetailItem[] $noRepeatSearchContexts
     * @throws Throwable
     */
    public function generateAndSendSummary(
        DelightfulChatAggregateSearchReqDTO $dto,
        array $noRepeatSearchContexts,
        array $associateQuestions,
    ): DelightfulAggregateSearchSummaryDTO {
        // 由于是stream输出响应，因此让前端判断 ai quote的search url。
        Coroutine::create(function () use ($dto, $noRepeatSearchContexts) {
            CoContext::setRequestId($dto->getRequestId());
            $messageId = (string) $this->idGenerator->generate();
            $messageType = AggregateAISearchCardResponseType::SEARCH;
            $content = ['search' => $this->getSearchData($noRepeatSearchContexts)];
            $this->aiSendMessage(
                $dto->getConversationId(),
                $messageId,
                '0',
                $messageType,
                $content,
                $dto->getAppMessageId(),
                $dto->getTopicId()
            );
        });
        // generate总结
        return $this->generateSummary($dto, $noRepeatSearchContexts, $associateQuestions);
    }

    /**
     * @param SearchDetailItem[] $noRepeatSearchContexts
     * @throws Throwable
     */
    public function generateSummary(
        DelightfulChatAggregateSearchReqDTO $dto,
        array $noRepeatSearchContexts,
        array $associateQuestions
    ): DelightfulAggregateSearchSummaryDTO {
        $dto->setRequestId(CoContext::getRequestId());
        $summaryMessageId = (string) $this->idGenerator->generate();
        $start = microtime(true);
        $llmConversationId = (string) IdGenerator::getSnowId();
        $llmHistoryMessage = DelightfulChatAggregateSearchReqDTO::generateLLMHistory($dto->getDelightfulChatMessageHistory(), $llmConversationId);
        $queryVo = (new AISearchCommonQueryVo())
            ->setUserMessage($dto->getUserMessage())
            ->setMessageHistory($llmHistoryMessage)
            ->setConversationId($llmConversationId)
            ->setSearchContexts($noRepeatSearchContexts)
            ->setSearchKeywords(array_column($associateQuestions, 'title'))
            ->setUserId($dto->getUserId())
            ->setOrganizationCode($dto->getOrganizationCode());
        // 深度search的总结use deepseek-r1 模型
        if ($dto->getSearchDeepLevel() === SearchDeepLevel::DEEP) {
            $modelInterface = $this->getChatModel($dto->getOrganizationCode(), $dto->getUserId(), LLMModelEnum::DEEPSEEK_R1->value);
        } else {
            $modelInterface = $this->getChatModel($dto->getOrganizationCode(), $dto->getUserId());
        }
        $queryVo->setModel($modelInterface);
        $summarizeCompletionResponse = $this->delightfulLLMDomainService->summarize($queryVo);
        // stream响应
        $senderConversationEntity = $this->delightfulConversationDomainService->getConversationByIdWithoutCheck($dto->getConversationId());
        if ($senderConversationEntity === null) {
            ExceptionBuilder::throw(ChatErrorCode::CONVERSATION_NOT_FOUND);
        }
        $senderSeqDTO = (new DelightfulSeqEntity())
            ->setConversationId($dto->getConversationId())
            ->setSeqType(ChatMessageType::AggregateAISearchCard)
            ->setAppMessageId($dto->getAppMessageId())
            ->setExtra((new SeqExtra())->setTopicId($dto->getTopicId()));
        $streamOptions = (new StreamOptions())->setStream(true)->setStreamAppMessageId($summaryMessageId);
        $messageContent = new AggregateAISearchCardMessage([
            'parent_id' => '0',
            'id' => $summaryMessageId,
            'type' => AggregateAISearchCardResponseType::LLM_RESPONSE,
        ]);
        // stream响应
        $summarizeStreamResponse = '';
        $messageDTO = new DelightfulMessageEntity();
        $messageDTO->setMessageType(ChatMessageType::AggregateAISearchCard)->setSenderId($senderConversationEntity->getUserId());

        /**
         * @var ChatCompletionChoice $choice
         */
        foreach ($summarizeCompletionResponse as $index => $choice) {
            /** @var AssistantMessage $assistantMessage */
            $assistantMessage = $choice->getMessage();
            $messageContent->setStreamOptions($streamOptions);
            if ($index === 0) {
                $streamOptions->setStatus(StreamMessageStatus::Start);
                // create一个 seq 用于渲染占位
                $this->delightfulChatDomainService->createAndSendStreamStartSequence(
                    (new CreateStreamSeqDTO())->setTopicId($dto->getTopicId())->setAppMessageId($dto->getAppMessageId()),
                    $messageContent,
                    $senderConversationEntity
                );
                // push一次 parent_id/id/type 数据，用于更新streamcache，避免最终落库时，parent_id/id/type 数据丢失
                $this->delightfulChatDomainService->streamSendJsonMessage($senderSeqDTO->getAppMessageId(), [
                    'parent_id' => '0',
                    'id' => $summaryMessageId,
                    'type' => AggregateAISearchCardResponseType::LLM_RESPONSE,
                ]);
            } else {
                $streamOptions->setStatus(StreamMessageStatus::Processing);
            }
            // stream内容
            if ($assistantMessage->hasReasoningContent()) {
                // send思考内容
                $this->delightfulChatDomainService->streamSendJsonMessage($senderSeqDTO->getAppMessageId(), [
                    'reasoning_content' => $assistantMessage->getReasoningContent(),
                ]);
            } else {
                // 总结内容
                $this->delightfulChatDomainService->streamSendJsonMessage($senderSeqDTO->getAppMessageId(), [
                    'llm_response' => $assistantMessage->getContent(),
                ]);
                // 累加stream内容，用作最后的return
                $summarizeStreamResponse .= $assistantMessage->getContent();
            }
        }
        // sendend
        $this->delightfulChatDomainService->streamSendJsonMessage($senderSeqDTO->getAppMessageId(), [], StreamMessageStatus::Completed);
        $this->logger->info(sprintf('getSearchResults generate总结，end计时，耗时：%s 秒', microtime(true) - $start));
        $summaryDTO = new DelightfulAggregateSearchSummaryDTO();
        $summaryDTO->setLlmResponse($summarizeStreamResponse);
        $summaryDTO->setSearchContext($noRepeatSearchContexts);
        return $summaryDTO;
    }

    /**
     * @param SearchDetailItem[] $noRepeatSearchContexts
     */
    public function generateAndSendExtra(DelightfulChatAggregateSearchReqDTO $dto, array $noRepeatSearchContexts, DelightfulAggregateSearchSummaryDTO $summarize): void
    {
        // generate思维导图和PPT
        $extraContentParallel = new Parallel(3);
        $modelInterface = $this->getChatModel($dto->getOrganizationCode(), $dto->getUserId());
        $extraContentParallel->add(function () use ($summarize, $dto, $modelInterface) {
            // odin 会修改 vo object中的value，避免污染，复制再传入
            CoContext::setRequestId($dto->getRequestId());
            // 思维导图
            $mindMapQueryVo = $this->getSearchVOByAggregateSearchDTO($dto, $summarize);
            $mindMapQueryVo->setModel($modelInterface);
            $mindMap = $this->generateAndSendMindMap($dto, $mindMapQueryVo);
            // ppt
            $pptQueryVo = $this->getSearchVOByAggregateSearchDTO($dto, $summarize);
            $pptQueryVo->setModel($modelInterface);
            $this->generateAndSendPPT($dto, $pptQueryVo, $mindMap);
        });

        // generate事件
        $extraContentParallel->add(function () use ($dto, $noRepeatSearchContexts, $summarize, $modelInterface) {
            CoContext::setRequestId($dto->getRequestId());
            $eventQueryVo = $this->getSearchVOByAggregateSearchDTO($dto, $summarize);
            $eventQueryVo->setModel($modelInterface);
            $this->generateAndSendEvent($dto, $eventQueryVo, $noRepeatSearchContexts);
        });

        try {
            $extraContentParallel->wait();
        } catch (ParallelExecutionException $parallelExecutionException) {
            foreach ($parallelExecutionException->getThrowables() as $throwable) {
                // 仅记录
                $this->logger->error('error_during_tool_call', [
                    'function' => 'generateAndSendExtraError',
                    'error_message' => $throwable->getMessage(),
                    'error_code' => $throwable->getCode(),
                    'error_file' => $throwable->getFile(),
                    'error_line' => $throwable->getLine(),
                    'error_trace' => $throwable->getTraceAsString(),
                ]);
            }
            throw $parallelExecutionException;
        }
    }

    /**
     * @throws Throwable
     */
    public function generateAndSendPPT(DelightfulChatAggregateSearchReqDTO $dto, AISearchCommonQueryVo $queryVo, string $mindMap): void
    {
        $start = microtime(true);
        $ppt = $this->delightfulLLMDomainService->generatePPTFromMindMap($queryVo, $mindMap);
        $this->logger->info(sprintf('getSearchResults generatePPT，end计时，耗时: %s 秒', microtime(true) - $start));
        $messageId = (string) $this->idGenerator->generate();
        $messageType = AggregateAISearchCardResponseType::PPT;
        $this->aiSendMessage(
            $dto->getConversationId(),
            $messageId,
            '0',
            $messageType,
            ['ppt' => $ppt],
            $dto->getAppMessageId(),
            $dto->getTopicId()
        );
    }

    /**
     * @throws Throwable
     */
    public function generateAndSendMindMap(DelightfulChatAggregateSearchReqDTO $dto, AISearchCommonQueryVo $queryVo): string
    {
        $start = microtime(true);
        $mindMap = $this->delightfulLLMDomainService->generateMindMapFromMessage($queryVo);
        $this->logger->info(sprintf('getSearchResults generate思维导图，end计时，耗时: %s 秒', microtime(true) - $start));
        $messageId = (string) $this->idGenerator->generate();
        $messageType = AggregateAISearchCardResponseType::MIND_MAP;
        $this->aiSendMessage(
            $dto->getConversationId(),
            $messageId,
            '0',
            $messageType,
            ['mind_map' => $mindMap],
            $dto->getAppMessageId(),
            $dto->getTopicId()
        );
        return $mindMap;
    }

    /**
     * @param SearchDetailItem[] $noRepeatSearchContexts
     * @throws Throwable
     */
    public function generateAndSendEvent(DelightfulChatAggregateSearchReqDTO $dto, AISearchCommonQueryVo $queryVo, array $noRepeatSearchContexts): void
    {
        $start = microtime(true);
        $event = $this->delightfulLLMDomainService->generateEventFromMessage($queryVo, $noRepeatSearchContexts);
        $this->logger->info(sprintf('getSearchResults generate事件，end计时，耗时: %s 秒', microtime(true) - $start));
        $messageId = (string) $this->idGenerator->generate();
        $messageType = AggregateAISearchCardResponseType::EVENT;
        $this->aiSendMessage(
            $dto->getConversationId(),
            $messageId,
            '0',
            $messageType,
            ['event' => $event],
            $dto->getAppMessageId(),
            $dto->getTopicId(),
        );
    }

    /**
     * @throws Throwable
     */
    public function sendPingPong(DelightfulChatAggregateSearchReqDTO $dto): void
    {
        $this->aiSendMessage(
            $dto->getConversationId(),
            (string) $this->idGenerator->generate(),
            '0',
            AggregateAISearchCardResponseType::PING_PONG,
            [],
            $dto->getAppMessageId(),
            $dto->getTopicId()
        );
    }

    public function getOrCreateConversation(string $senderUserId, string $receiveId, ?ConversationType $receiverType = null): DelightfulConversationEntity
    {
        return $this->delightfulConversationDomainService->getOrCreateConversation($senderUserId, $receiveId, $receiverType);
    }

    public function getUserInfo(string $senderUserId): ?DelightfulUserEntity
    {
        return $this->delightfulUserDomainService->getUserById($senderUserId);
    }

    /**
     * @param SearchDetailItem[] $noRepeatSearchContexts
     */
    private function getSearchData(array $noRepeatSearchContexts): array
    {
        $searchList = [];
        foreach ($noRepeatSearchContexts as $search) { // $search 已经是切片后的元素
            // 兼容历史数据，key use小驼峰
            $searchList[] = [
                'id' => $search->getId(),
                'name' => $search->getName(),
                'url' => $search->getUrl(),
                'datePublished' => $search->getDatePublished(),
                'datePublishedDisplayText' => $search->getDatePublishedDisplayText(),
                'isFamilyFriendly' => $search->isFamilyFriendly(),
                'displayUrl' => $search->getDisplayUrl(),
                'snippet' => $search->getSnippet(),
                'dateLastCrawled' => $search->getDateLastCrawled(),
                'cachedPageUrl' => $search->getCachedPageUrl(),
                'language' => $search->getLanguage(),
                'isNavigational' => $search->isNavigational(),
                'noCache' => $search->isNoCache(),
                'detail' => '', // 节省流量，给前端推message不传 detail
            ];
        }
        return $searchList;
    }

    /**
     * @param SearchDetailItem[] $noRepeatSearchContexts
     */
    private function getSearchPageDetails(array $noRepeatSearchContexts, array $associateQuestions, Channel $readPagesDetailChannel): void
    {
        $questionsNum = count($associateQuestions);
        $detailReadMaxNum = max(20, $questionsNum);
        $perReadResponseNum = intdiv($detailReadMaxNum, $questionsNum);
        // 限制并发请求数量
        $parallel = new Parallel(5);
        $timeStart = microtime(true);
        $currentDetailReadCount = 0;
        foreach ($noRepeatSearchContexts as $context) {
            $requestId = CoContext::getRequestId();
            $parallel->add(function () use ($context, $detailReadMaxNum, $requestId, &$currentDetailReadCount, $readPagesDetailChannel, $perReadResponseNum, &$questionsNum) {
                // 知乎读不了一点
                if (str_contains($context->getCachedPageUrl(), 'zhihu.com')) {
                    return;
                }
                // 只取指定数量网页的详细内容
                if ($currentDetailReadCount > $detailReadMaxNum) {
                    return;
                }
                CoContext::setRequestId($requestId);
                $htmlReader = make(HTMLReader::class);
                try {
                    // 用快照去拿内容！！
                    $content = $htmlReader->getText($context->getCachedPageUrl());
                    $content = mb_substr($content, 0, 2048);
                    $context->setDetail($content);
                    ++$currentDetailReadCount;
                    // according to精读进度，push关联问题search完毕给前端
                    if (($currentDetailReadCount % $perReadResponseNum === 0) && $readPagesDetailChannel->isAvailable()) {
                        $readPagesDetailChannel->push(1, 5);
                        // 需要push的次数减少
                        --$questionsNum;
                    }
                } catch (Throwable $e) {
                    $this->logger->error(sprintf(
                        'mindSearch getSearchResults 获取详细内容时发生error:%s,file:%s,line:%s trace:%s',
                        $e->getMessage(),
                        $e->getFile(),
                        $e->getLine(),
                        $e->getTraceAsString()
                    ));
                }
            });
        }
        $parallel->wait();
        // 如果还有需要push的次数，循环push
        while ($questionsNum > 0 && $readPagesDetailChannel->isAvailable()) {
            $readPagesDetailChannel->push(1, 5);
            --$questionsNum;
        }
        $this->logger->info(sprintf(
            'mindSearch getSearchResults 精读所有search结果 精读累计耗时：%s 秒',
            number_format(TimeUtil::getMillisecondDiffFromNow($timeStart) / 1000, 2)
        ));
    }

    /**
     * @param SearchDetailItem[] $noRepeatSearchContexts
     */
    private function getAssociateQuestionsQueryVo(
        DelightfulChatAggregateSearchReqDTO $dto,
        array $noRepeatSearchContexts,
        string $searchKeyword = ''
    ): AISearchCommonQueryVo {
        if (empty($searchKeyword)) {
            $searchKeyword = $dto->getUserMessage();
        }
        $llmConversationId = (string) IdGenerator::getSnowId();
        $llmHistoryMessage = DelightfulChatAggregateSearchReqDTO::generateLLMHistory($dto->getDelightfulChatMessageHistory(), $llmConversationId);
        $modelInterface = $this->getChatModel($dto->getOrganizationCode(), $dto->getUserId());
        return (new AISearchCommonQueryVo())
            ->setUserMessage($searchKeyword)
            ->setSearchEngine($dto->getSearchEngine())
            ->setFilterSearchContexts(false)
            ->setGenerateSearchKeywords(false)
            ->setMessageHistory($llmHistoryMessage)
            ->setConversationId($llmConversationId)
            ->setModel($modelInterface)
            ->setSearchContexts($noRepeatSearchContexts)
            ->setUserId($dto->getUserId())
            ->setOrganizationCode($dto->getOrganizationCode());
    }

    private function getSearchVOByAggregateSearchDTO(DelightfulChatAggregateSearchReqDTO $dto, DelightfulAggregateSearchSummaryDTO $summarize): AISearchCommonQueryVo
    {
        $llmConversationId = (string) IdGenerator::getSnowId();
        $llmHistoryMessage = DelightfulChatAggregateSearchReqDTO::generateLLMHistory($dto->getDelightfulChatMessageHistory(), $llmConversationId);
        return (new AISearchCommonQueryVo())
            ->setConversationId($llmConversationId)
            ->setUserMessage($dto->getUserMessage())
            ->setMessageHistory($llmHistoryMessage)
            ->appendLlmResponse($summarize->getLlmResponse())
            ->setSearchContexts($summarize->getSearchContext())
            ->setUserId($dto->getUserId())
            ->setOrganizationCode($dto->getOrganizationCode());
    }

    /**
     * @param SearchDetailItem[] $noRepeatSearchContexts
     */
    private function deepSearch(DelightfulChatAggregateSearchReqDTO $dto, array $associateQuestions, array &$noRepeatSearchContexts, Channel $readPagesDetailChannel): void
    {
        $timeStart = microtime(true);
        $parallel = new Parallel(2);
        $parallel->add(function () use ($dto, $noRepeatSearchContexts, $associateQuestions) {
            // 3.4.a.1 并行：according to关联问题和问题的简单search，generate关联问题的子问题.(关联问题的子问题只用于前端展示，目前不会according to子问题再次search+精读)
            $this->generateAndSendAssociateSubQuestions($dto, $noRepeatSearchContexts, $associateQuestions);
        });
        $parallel->add(function () use (&$noRepeatSearchContexts, $readPagesDetailChannel, $associateQuestions) {
            // 3.4.a.2 并行：精读关联问题search的网页详情
            $this->getSearchPageDetails($noRepeatSearchContexts, $associateQuestions, $readPagesDetailChannel);
        });
        $parallel->wait();
        $this->logger->info(sprintf(
            'mindSearch getSearchResults generate关联问题的子问题，并精读所有search结果，end 累计耗时：%s 秒',
            number_format(TimeUtil::getMillisecondDiffFromNow($timeStart) / 1000, 2)
        ));
    }

    private function sleepToFixBug(float $seconds = 0.2): void
    {
        // !!! 由于收件方的messagegenerate是async的，可能乱序，因此，这里 sleep 一小会，尽量保证收件方messagegenerate的顺序
        Coroutine::sleep($seconds);
    }

    /**
     * @throws Throwable
     */
    private function aiSendMessage(
        string $conversationId,
        string $id,
        string $parentId,
        int $type,
        array $content,
        // todo stream响应，拿到客户端传来的 app_message_id ，作为响应时候的唯一标识
        string $appMessageId = '',
        string $topicId = ''
    ): void {
        $logMessageContent = Json::encode($content, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (mb_strlen($logMessageContent) > 300) {
            $logMessageContent = '';
        }
        $this->logger->info(sprintf(
            'deepSearchSendMessage conversationId:%s id:%s messageName:%s Type:%s parentId:%s  appMessageId:%s topicId:%s logMessageContent:%s',
            $conversationId,
            $id,
            AggregateAISearchCardResponseType::getNameFromType($type),
            $type,
            $parentId,
            $appMessageId,
            $topicId,
            $logMessageContent
        ));
        $content = array_merge($content, [
            'parent_id' => $parentId,
            'id' => $id,
            'type' => $type,
        ]);
        $messageInterface = new AggregateAISearchCardMessage($content);
        $extra = new SeqExtra();
        $extra->setTopicId($topicId);
        $seqDTO = (new DelightfulSeqEntity())
            ->setConversationId($conversationId)
            ->setContent($messageInterface)
            ->setSeqType($messageInterface->getMessageTypeEnum())
            ->setAppMessageId($appMessageId)
            ->setExtra($extra);
        // setting话题 id
        $this->getDelightfulChatMessageAppService()->aiSendMessage($seqDTO, $appMessageId);
    }

    /**
     * 获取 im中指定conversation下某个话题的历史message，作为 llm 的历史message.
     */
    private function getDelightfulChatMessages(string $delightfulChatConversationId, string $topicId): array
    {
        $rawHistoryMessages = $this->delightfulChatDomainService->getLLMContentForAgent($delightfulChatConversationId, $topicId);
        // 取最后指定条数的conversation记录
        return array_slice($rawHistoryMessages, -10);
    }

    private function getDelightfulChatMessageAppService(): DelightfulChatMessageAppService
    {
        return di(DelightfulChatMessageAppService::class);
    }

    private function getChatModel(string $orgCode, string $userId, string $modelName = LLMModelEnum::DEEPSEEK_V3->value): ModelInterface
    {
        // 通过降级链获取模型名称
        $modelName = di(ModelConfigAppService::class)->getChatModelTypeByFallbackChain($orgCode, $userId, $modelName);
        // 获取模型代理
        $dataIsolation = ModelGatewayDataIsolation::createByOrganizationCodeWithoutSubscription($orgCode, $userId);
        return di(ModelGatewayMapper::class)->getChatModelProxy($dataIsolation, $modelName);
    }

    /**
     * @param SearchDetailItem[] $noRepeatSearchContexts
     */
    private function chunkContextsByAssociateQuestions(array $associateQuestions, array $noRepeatSearchContexts): array
    {
        $numAssociateQuestions = count($associateQuestions);
        $numTotalContexts = count($noRepeatSearchContexts);
        $chunkedContexts = [];

        if ($numAssociateQuestions > 0) {
            if ($numTotalContexts > 0) {
                $chunkSize = (int) ceil($numTotalContexts / $numAssociateQuestions);
                $chunkSize = min($chunkSize, 5);
                $chunkedContexts = array_chunk($noRepeatSearchContexts, $chunkSize);
            }
        }
        return $chunkedContexts;
    }
}
