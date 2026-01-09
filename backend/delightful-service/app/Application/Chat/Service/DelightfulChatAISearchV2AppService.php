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
use App\Domain\Chat\DTO\Message\ChatMessage\AggregateAISearchCardMessageV2;
use App\Domain\Chat\DTO\Message\ChatMessage\Item\DeepSearch\QuestionItem;
use App\Domain\Chat\DTO\Message\ChatMessage\Item\DeepSearch\QuestionSearchResult;
use App\Domain\Chat\DTO\Message\ChatMessage\Item\DeepSearch\SearchDetailItem;
use App\Domain\Chat\DTO\Message\StreamMessage\FinishedReasonEnum;
use App\Domain\Chat\DTO\Message\StreamMessage\StreamMessageStatus;
use App\Domain\Chat\DTO\Message\StreamMessage\StreamOptions;
use App\Domain\Chat\DTO\Stream\CreateStreamSeqDTO;
use App\Domain\Chat\Entity\DelightfulMessageEntity;
use App\Domain\Chat\Entity\ValueObject\AggregateSearch\SearchDeepLevel;
use App\Domain\Chat\Entity\ValueObject\AISearchCommonQueryVo;
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
use Psr\Log\LoggerInterface;
use RedisException;
use Throwable;

/**
 * chatmessage相关.
 */
class DelightfulChatAISearchV2AppService extends AbstractAppService
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
        $this->logger = di()->get(LoggerFactory::class)->get('aggregate_ai_search_card_v2');
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
            $topicId,
        );
        $this->logger->info(sprintf('mindSearch aggregateSearch startaggregatesearch  searchKeyword：%s searchtype：%s', $searchKeyword, $dto->getSearchDeepLevel()->name));
        $antiRepeatKey = md5($conversationId . $topicId);
        // 防重:如果同一conversation同一话题下,2秒内有重复的message,不触发process
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
        $dto->setAppMessageId(IdGenerator::getUniqueIdSha256());

        try {
            # initializestreammessage并sendsearch深度
            $this->initStreamAndSendSearchDeepLevel($dto);
            // get im 中指定conversation下某个话题的历史message，作为 llm 的历史message
            $rawHistoryMessages = $this->getDelightfulChatMessages($dto->getConversationId(), $dto->getTopicId());
            $dto->setDelightfulChatMessageHistory($rawHistoryMessages);

            # 2.searchuser问题.这里一定willsplit一次关联问题
            $searchDetailItems = $this->searchUserQuestion($dto);
            # 3.according tooriginal问题 + search结果，按多个维度拆解关联问题.
            // 3.1 generate关联问题并send给前端
            $associateQuestionsQueryVo = $this->getAssociateQuestionsQueryVo($dto, $searchDetailItems);
            $associateQuestions = $this->generateAndSendAssociateQuestions($dto, $associateQuestionsQueryVo, AggregateAISearchCardMessageV2::NULL_PARENT_ID);
            // 3.2 according to关联问题，发起简单search（不拿网页详情),并filter掉重复或者与问题关联性不高的网页内容
            $noRepeatSearchContexts = $this->generateSearchResults($dto, $associateQuestions);

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
            $associateQuestionIds = [];
            foreach ($associateQuestions as $associateQuestion) {
                $associateQuestionIds[] = $associateQuestion->getQuestionId();
            }
            $this->sendLLMResponseForAssociateQuestions($dto, $associateQuestionIds, $readPagesDetailChannel);
            // 4. according to每个关联问题reply，generate总结.
            $summarize = $this->generateAndSendSummary($dto, $noRepeatSearchContexts, $associateQuestions);
            // 5. according to总结，generate额外内容（思维导图、PPT、event等）
            if ($dto->getSearchDeepLevel() === SearchDeepLevel::DEEP) {
                $this->generateAndSendExtra($dto, $noRepeatSearchContexts, $summarize);
            }
            // 6. sendping pongresponse,代表endreply
            $this->streamSendDeepSearchMessages($dto, [], StreamMessageStatus::Completed);
        } catch (Throwable $e) {
            // 7. 发生exception时，send终止message，并抛出exception
            $this->streamSendDeepSearchMessages($dto, [], StreamMessageStatus::Completed);
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
            // 1.searchuser问题.这里一定willsplit一次关联问题
            $searchDetailItems = $this->searchUserQuestion($dto);

            // 2.according tooriginal问题 + search结果，按多个维度拆解关联问题.
            // 2.1 generate关联问题
            $associateQuestionsQueryVo = $this->getAssociateQuestionsQueryVo($dto, $searchDetailItems);
            $associateQuestions = $this->generateAssociateQuestions($associateQuestionsQueryVo, AggregateAISearchCardMessageV2::NULL_PARENT_ID);
            // 2.2 according to关联问题，发起简单search（不拿网页详情),并filter掉重复或者与问题关联性不高的网页内容
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
     * @param QuestionItem[] $associateQuestions
     * @param SearchDetailItem[] $noRepeatSearchContexts
     */
    public function simpleSearch(
        DelightfulChatAggregateSearchReqDTO $dto,
        array $associateQuestions,
        array $noRepeatSearchContexts
    ): void {
        $start = microtime(true);
        $parallel = new Parallel(5);
        $associateQuestions = array_values($associateQuestions);

        // --- Start of new chunking logic ---
        $chunkedContexts = $this->chunkContexts($associateQuestions, $noRepeatSearchContexts);
        // --- End of new chunking logic ---

        foreach ($associateQuestions as $index => $associateQuestion) {
            $questionId = $associateQuestion->getQuestionId();
            $currentContextChunk = $chunkedContexts[$index] ?? [];

            $parallel->add(function () use (
                $questionId,
                $associateQuestion,
                $dto,
                $currentContextChunk // Use this chunk
            ) {
                $start = microtime(true);
                CoContext::setRequestId($dto->getRequestId());
                // 已generate关联问题，准备sendsearch结果
                // 由于这里是对所有维度汇总后再精读，因此丢失了每个维度的quantity，只能随机generate。
                $pageCount = random_int(30, 60);
                $webSearchItem = new QuestionSearchResult([
                    'question_id' => $questionId,
                    'search' => $currentContextChunk, // Use the pre-calculated chunk
                    'page_count' => $pageCount,
                    'match_count' => random_int(1000, 5000),
                    'total_words' => $pageCount * random_int(50, 200),
                ]);
                $this->streamSendSearchWebPages($dto, $webSearchItem);
                $this->logger->info(sprintf(
                    'getSearchResults 关联问题：%s 的null白子问题generate并push完毕 end计时，耗时 %s 秒',
                    $associateQuestion->getQuestion(),
                    TimeUtil::getMillisecondDiffFromNow($start) / 1000
                ));
            });
        }
        $parallel->wait();
        $this->logger->info(sprintf(
            'getSearchResults 所有关联问题的null白子问题push完毕 end计时，耗时：%s 秒',
            TimeUtil::getMillisecondDiffFromNow($start) / 1000
        ));
    }

    /**
     * @param QuestionItem[] $associateQuestions
     * @param SearchDetailItem[] $noRepeatSearchContexts
     */
    public function generateAndSendAssociateSubQuestions(
        DelightfulChatAggregateSearchReqDTO $dto,
        array $noRepeatSearchContexts,
        array $associateQuestions
    ): void {
        $start = microtime(true);
        $parallel = new Parallel(5);
        $associateQuestions = array_values($associateQuestions);

        // --- Start of new chunking logic ---
        $chunkedContexts = $this->chunkContexts($associateQuestions, $noRepeatSearchContexts);
        // --- End of new chunking logic ---

        foreach ($associateQuestions as $index => $associateQuestion) { // $associateQuestion is QuestionItem
            $questionId = $associateQuestion->getQuestionId();
            $currentContextChunk = $chunkedContexts[$index] ?? []; // Get the pre-calculated chunk for this question

            $parallel->add(function () use (
                $questionId,
                $associateQuestion,
                $dto,
                $noRepeatSearchContexts, // Still needed for getAssociateQuestionsQueryVo
                $currentContextChunk     // New chunk for 'search'
            ) {
                CoContext::setRequestId($dto->getRequestId());
                $start = microtime(true);
                // Uses $noRepeatSearchContexts (the full list)
                $associateQuestionsQueryVo = $this->getAssociateQuestionsQueryVo($dto, $noRepeatSearchContexts, $associateQuestion->getQuestion());
                $associateQuestionsQueryVo->setMessageHistory(new MessageHistory());
                // get子问题
                $associateSubQuestions = $this->delightfulLLMDomainService->getRelatedQuestions($associateQuestionsQueryVo, 2, 3);
                $pageCount = random_int(30, 60);
                $onePageWords = random_int(200, 2000);
                $totalWords = $pageCount * $onePageWords;
                // todo 由于这里是对所有维度汇总后再精读，因此丢失了每个维度的quantity，只能随机generate。
                $webSearchItem = new QuestionSearchResult([
                    'question_id' => $questionId,
                    'search_keywords' => $associateSubQuestions,
                    'search' => $currentContextChunk, // Use the pre-calculated chunk
                    'page_count' => $pageCount,
                    'match_count' => random_int(1000, 5000),
                    'total_words' => $totalWords,
                ]);
                $this->streamSendSearchWebPages($dto, $webSearchItem);

                $this->logger->info(sprintf(
                    'getSearchResults 关联问题：%s 的子问题 %s generate并push完毕 end计时，耗时 %s 秒',
                    $associateQuestion->getQuestion(),
                    Json::encode($associateSubQuestions, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    TimeUtil::getMillisecondDiffFromNow($start) / 1000
                ));
            });
        }
        $parallel->wait();
        $this->logger->info(sprintf(
            'getSearchResults 所有关联问题的子问题 generate并push完毕 end计时，耗时：%s 秒',
            TimeUtil::getMillisecondDiffFromNow($start) / 1000
        ));
    }

    /**
     * @return array searchDetailItem object的二维array形式，这里为了兼容和方便，不进行objectconvert
     */
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
        // according touser的上下文，拆解子问题。need理解user想问什么，再去拆search关键词。
        $searchKeywords = $this->delightfulLLMDomainService->generateSearchKeywordsByUserInput($dto, $modelInterface);
        $queryVo->setSearchKeywords($searchKeywords);
        $searchDetailItems = $this->delightfulLLMDomainService->getSearchResults($queryVo)['search'] ?? [];
        $this->logger->info(sprintf(
            'getSearchResults searchUserQuestion 虚null拆解关键词并searchuser问题 end计时，耗时 %s 秒',
            microtime(true) - $start
        ));
        return $searchDetailItems;
    }

    /**
     * generate并send关联问题.
     * @return QuestionItem[]
     */
    public function generateAndSendAssociateQuestions(
        DelightfulChatAggregateSearchReqDTO $dto,
        AISearchCommonQueryVo $queryVo,
        string $questionParentId
    ): array {
        // generate关联问题
        $associateQuestions = $this->generateAssociateQuestions($queryVo, $questionParentId);
        // streampush关联问题
        $this->sendAssociateQuestions($dto, $associateQuestions, $questionParentId);
        return $associateQuestions;
    }

    /**
     * according tooriginal问题 + search结果，按多个维度拆解问题.
     * @return QuestionItem[]
     */
    public function generateAssociateQuestions(AISearchCommonQueryVo $queryVo, string $parentQuestionId): array
    {
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
        $associateQuestions = $this->buildAssociateQuestions($relatedQuestions, $parentQuestionId);
        $this->logger->info(sprintf(
            'getSearchResults 问题：%s 关联问题: %s .according tooriginal问题 + search结果，按多个维度拆解关联问题并push完毕 end计时，耗时 %s 秒',
            $queryVo->getUserMessage(),
            Json::encode($relatedQuestions, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            TimeUtil::getMillisecondDiffFromNow($start) / 1000
        ));
        return $associateQuestions;
    }

    /**
     * @param QuestionItem[] $associateQuestions
     * @return SearchDetailItem[]
     * @throws Throwable
     */
    public function generateSearchResults(DelightfulChatAggregateSearchReqDTO $dto, array $associateQuestions): array
    {
        $start = microtime(true);
        // according to关联问题，发起简单search（不拿网页详情),并filter掉重复或者与问题关联性不高的网页内容
        $searchKeywords = $this->getSearchKeywords($associateQuestions);
        $queryVo = (new AISearchCommonQueryVo())
            ->setSearchKeywords($searchKeywords)
            ->setSearchEngine($dto->getSearchEngine())
            ->setLanguage($dto->getLanguage());
        $allSearchContexts = $this->delightfulLLMDomainService->getSearchResults($queryVo)['search'] ?? [];
        // filter重复内容
        $noRepeatSearchContexts = [];
        if (! empty($allSearchContexts)) {
            // 清洗search结果中的重复项
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
        // array转object
        foreach ($noRepeatSearchContexts as &$searchContext) {
            if (! $searchContext instanceof SearchDetailItem) {
                $searchContext = new SearchDetailItem($searchContext);
            }
        }
        return $noRepeatSearchContexts;
    }

    /**
     * @param QuestionItem[] $associateQuestions
     * @param SearchDetailItem[] $noRepeatSearchContexts
     * @throws Throwable
     */
    public function generateAndSendSummary(
        DelightfulChatAggregateSearchReqDTO $dto,
        array $noRepeatSearchContexts,
        array $associateQuestions,
    ): DelightfulAggregateSearchSummaryDTO {
        // 由于是stream输出response，因此让前端判断 ai quote的search url。
        CoContext::setRequestId($dto->getRequestId());
        // 移除 detail
        $noRepeatSearchData = [];
        foreach ($noRepeatSearchContexts as $searchDetailItem) {
            $noRepeatSearch = $searchDetailItem->toArray();
            // 前端不要网页详情，移除 detail，同时保留总结时的search详情
            unset($noRepeatSearch['detail']);
            $noRepeatSearchData[] = $noRepeatSearch;
        }
        $this->streamSendDeepSearchMessages($dto, ['no_repeat_search_details' => $noRepeatSearchData]);
        // generate总结
        return $this->generateSummary($dto, $noRepeatSearchContexts, $associateQuestions);
    }

    /**
     * @param QuestionItem[] $associateQuestions
     * @param SearchDetailItem[] $noRepeatSearchContexts
     * @throws Throwable
     */
    public function generateSummary(
        DelightfulChatAggregateSearchReqDTO $dto,
        array $noRepeatSearchContexts,
        array $associateQuestions
    ): DelightfulAggregateSearchSummaryDTO {
        $searchKeywords = $this->getSearchKeywords($associateQuestions);
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
            ->setSearchKeywords($searchKeywords)
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
        // streamresponse
        $senderConversationEntity = $this->delightfulConversationDomainService->getConversationByIdWithoutCheck($dto->getConversationId());
        if ($senderConversationEntity === null) {
            ExceptionBuilder::throw(ChatErrorCode::CONVERSATION_NOT_FOUND);
        }
        $streamOptions = (new StreamOptions())->setStream(true)->setStreamAppMessageId($summaryMessageId);
        $messageContent = new AggregateAISearchCardMessage();
        $messageContent->setStreamOptions($streamOptions);
        // streamresponse
        $summarizeStreamResponse = '';
        $messageDTO = new DelightfulMessageEntity();
        $messageDTO->setMessageType(ChatMessageType::AggregateAISearchCard)->setSenderId($senderConversationEntity->getUserId());
        $streamOptions->setStatus(StreamMessageStatus::Processing);
        $hasReasoningContent = false;
        $hasSummaryContent = false;
        $hasPushedReasoningContentFinished = false;
        /**
         * @var ChatCompletionChoice $choice
         */
        foreach ($summarizeCompletionResponse as $choice) {
            /** @var AssistantMessage $assistantMessage */
            $assistantMessage = $choice->getMessage();
            // stream内容
            if ($assistantMessage->hasReasoningContent()) {
                // streampush思考或者总结过程
                $this->streamSendDeepSearchMessages($dto, ['summary.reasoning_content' => $assistantMessage->getReasoningContent()]);
                $hasReasoningContent = true;
            } else {
                $hasSummaryContent = true;
            }
            // 有思考内容，且思考内容end，start有总结，push思考end
            if ($hasReasoningContent && $hasSummaryContent && ! $hasPushedReasoningContentFinished) {
                $streamMessageKey = 'stream_options.steps_finished.summary.reasoning_content';
                $this->streamSendDeepSearchMessages($dto, [$streamMessageKey => [
                    'finished_reason' => FinishedReasonEnum::Finished->value,
                ]]);
                $hasPushedReasoningContentFinished = true;
            }
            // 先push思考end，再push总结
            if ($hasSummaryContent && $assistantMessage->getContent() !== '') {
                // streampush思考或者总结过程
                $this->streamSendDeepSearchMessages($dto, ['summary.content' => $assistantMessage->getContent()]);
            }
        }
        // push总结end
        $streamMessageKey = 'stream_options.steps_finished.summary.content';
        $this->streamSendDeepSearchMessages($dto, [$streamMessageKey => [
            'finished_reason' => FinishedReasonEnum::Finished,
        ]]);
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
            // odin will修改 vo object中的value，避免污染，复制再传入
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

        // generateevent
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

    public function getUserInfo(string $senderUserId): ?DelightfulUserEntity
    {
        return $this->delightfulUserDomainService->getUserById($senderUserId);
    }

    protected function sendAssociateQuestions(DelightfulChatAggregateSearchReqDTO $dto, array $associateQuestions, string $parentQuestionId): void
    {
        // 将关联问题push给前端
        $questionKey = AggregateAISearchCardMessageV2::QUESTION_DELIMITER . $parentQuestionId;
        $data = [];
        foreach ($associateQuestions as $questionItem) {
            $data[] = $questionItem->toArray();
        }
        $this->streamSendDeepSearchMessages($dto, ['associate_questions.' . $questionKey => $data]);
    }

    /**
     * @param string[] $relatedQuestions
     * @return QuestionItem[]
     */
    protected function buildAssociateQuestions(array $relatedQuestions, string $parentQuestionId): array
    {
        $associateQuestions = [];
        foreach ($relatedQuestions as $question) {
            $associateQuestions[] = new QuestionItem([
                'parent_question_id' => $parentQuestionId,
                'question_id' => (string) IdGenerator::getSnowId(),
                'question' => $question,
            ]);
        }
        return $associateQuestions;
    }

    protected function streamSendSearchWebPages(DelightfulChatAggregateSearchReqDTO $dto, QuestionSearchResult $webSearchItem): void
    {
        $webSearchItemArray = $webSearchItem->toArray();
        // 去掉 detail 字段
        foreach ($webSearchItemArray['search'] as &$searchItem) {
            if (! empty($searchItem['detail'])) {
                $searchItem['detail'] = null;
            }
        }
        unset($searchItem);
        $this->streamSendDeepSearchMessages($dto, ['search_web_pages' => [$webSearchItemArray]]);
    }

    /**
     * @throws Throwable
     */
    protected function generateAndSendPPT(DelightfulChatAggregateSearchReqDTO $dto, AISearchCommonQueryVo $queryVo, string $mindMap): void
    {
        $start = microtime(true);
        $ppt = $this->delightfulLLMDomainService->generatePPTFromMindMap($queryVo, $mindMap);
        $this->logger->info(sprintf(
            'getSearchResults generatePPT，end计时，耗时: %s 秒',
            TimeUtil::getMillisecondDiffFromNow($start) / 1000
        ));
        # streammessagepush
        $this->streamSendDeepSearchMessages($dto, ['ppt' => $ppt]);
    }

    /**
     * @throws Throwable
     */
    protected function generateAndSendMindMap(DelightfulChatAggregateSearchReqDTO $dto, AISearchCommonQueryVo $queryVo): string
    {
        $start = microtime(true);
        $mindMap = $this->delightfulLLMDomainService->generateMindMapFromMessage($queryVo);
        $this->logger->info(sprintf('getSearchResults generate思维导图，end计时，耗时: %s 秒', microtime(true) - $start));
        # streammessagepush
        $this->streamSendDeepSearchMessages($dto, ['mind_map' => $mindMap]);
        return $mindMap;
    }

    /**
     * @param SearchDetailItem[] $noRepeatSearchContexts
     */
    protected function generateAndSendEvent(DelightfulChatAggregateSearchReqDTO $dto, AISearchCommonQueryVo $queryVo, array $noRepeatSearchContexts): void
    {
        $start = microtime(true);
        $events = $this->delightfulLLMDomainService->generateEventFromMessage($queryVo, $noRepeatSearchContexts);
        $this->logger->info(sprintf('getSearchResults generateevent，end计时，耗时: %s 秒', microtime(true) - $start));
        // object转array
        $data = [];
        foreach ($events as $event) {
            $data[] = $event->toArray();
        }
        # streammessagepush
        $this->streamSendDeepSearchMessages($dto, ['events' => $data]);
    }

    protected function streamSendDeepSearchMessages(
        DelightfulChatAggregateSearchReqDTO $dto,
        array $messageContent,
        ?StreamMessageStatus $streamMessageStatus = null
    ): void {
        $this->delightfulChatDomainService->streamSendJsonMessage(
            $dto->getAppMessageId(),
            $messageContent,
            $streamMessageStatus
        );
    }

    /**
     * @param QuestionItem[] $associateQuestions
     */
    private function getSearchKeywords(array $associateQuestions): array
    {
        $searchKeywords = [];
        foreach ($associateQuestions as $questionItem) {
            $searchKeywords[] = $questionItem->getQuestion();
        }
        return $searchKeywords;
    }

    /**
     * @throws Throwable
     */
    private function initStreamAndSendSearchDeepLevel(DelightfulChatAggregateSearchReqDTO $dto): void
    {
        $senderConversationEntity = $this->delightfulConversationDomainService->getConversationByIdWithoutCheck($dto->getConversationId());
        if ($senderConversationEntity === null) {
            ExceptionBuilder::throw(ChatErrorCode::CONVERSATION_NOT_FOUND);
        }
        $messageContent = (new AggregateAISearchCardMessageV2())
            ->setStreamOptions(
                (new StreamOptions())->setStatus(StreamMessageStatus::Start)->setStream(true)
            );
        # pushstreamstart前generate一个 seq，markstreamstart，用于前端渲染占位
        $senderSeqEntity = $this->delightfulChatDomainService->createAndSendStreamStartSequence(
            (new CreateStreamSeqDTO())->setTopicId($dto->getTopicId())->setAppMessageId($dto->getAppMessageId()),
            $messageContent,
            $senderConversationEntity
        );
        $dto->setDelightfulSeqEntity($senderSeqEntity);

        // startupdate seq 的字段
        $this->streamSendDeepSearchMessages($dto, ['search_deep_level' => $dto->getSearchDeepLevel()->value]);
    }

    /**
     * 精读的过程中，隔随机时间push一次关联问题search完毕给前端。
     * 完全精读完毕时，最后再推一次
     */
    private function sendLLMResponseForAssociateQuestions(
        DelightfulChatAggregateSearchReqDTO $dto,
        array $associateQuestionIds,
        ?Channel $readPagesDetailChannel
    ): void {
        foreach ($associateQuestionIds as $questionId) {
            $readPagesDetailChannel && $readPagesDetailChannel->pop(15);
            # push每个子问题的searchend终止标识
            $questionKey = AggregateAISearchCardMessageV2::QUESTION_DELIMITER . $questionId;
            $streamMessageKey = 'stream_options.steps_finished.associate_questions.' . $questionKey;
            $this->streamSendDeepSearchMessages($dto, [$streamMessageKey => [
                'finished_reason' => FinishedReasonEnum::Finished,
            ]]);
        }
        // push父问题的searchend终止标识
        $streamMessageKey = 'stream_options.steps_finished.associate_questions.' . AggregateAISearchCardMessageV2::QUESTION_DELIMITER . '0';
        $this->streamSendDeepSearchMessages($dto, [$streamMessageKey => [
            'finished_reason' => FinishedReasonEnum::Finished,
        ]]);
    }

    /**
     * @param SearchDetailItem[] $noRepeatSearchContexts
     */
    private function getSearchPageDetails(array $noRepeatSearchContexts, array $associateQuestions, Channel $readPagesDetailChannel): void
    {
        $questionsNum = count($associateQuestions);
        $detailReadMaxNum = max(20, $questionsNum);
        $perReadResponseNum = intdiv($detailReadMaxNum, $questionsNum);
        // 限制并发requestquantity
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
                // 只取指定quantity网页的详细内容
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
                        // needpush的次数减少
                        --$questionsNum;
                    }
                } catch (Throwable $e) {
                    $this->logger->error(sprintf(
                        'mindSearch getSearchResults get详细内容时发生error:%s,file:%s,line:%s trace:%s',
                        $e->getMessage(),
                        $e->getFile(),
                        $e->getLine(),
                        $e->getTraceAsString()
                    ));
                }
            });
        }
        $parallel->wait();
        // 如果还有needpush的次数，循环push
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
     * @param QuestionItem[] $associateQuestions
     * @param SearchDetailItem[] $noRepeatSearchContexts
     */
    private function deepSearch(DelightfulChatAggregateSearchReqDTO $dto, array $associateQuestions, array &$noRepeatSearchContexts, Channel $readPagesDetailChannel): void
    {
        $timeStart = microtime(true);
        $parallel = new Parallel(2);
        $parallel->add(function () use ($dto, $noRepeatSearchContexts, $associateQuestions) {
            // 3.4.a.1 并行：according to关联问题和问题的简单search，generate关联问题的子问题.(关联问题的子问题只用于前端展示，目前不willaccording to子问题再次search+精读)
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

    /**
     * get im中指定conversation下某个话题的历史message，作为 llm 的历史message.
     */
    private function getDelightfulChatMessages(string $delightfulChatConversationId, string $topicId): array
    {
        $rawHistoryMessages = $this->delightfulChatDomainService->getLLMContentForAgent($delightfulChatConversationId, $topicId);
        // 取最后指定条数的conversation记录
        return array_slice($rawHistoryMessages, -10);
    }

    private function getChatModel(string $orgCode, string $userId, string $modelName = LLMModelEnum::DEEPSEEK_V3->value): ModelInterface
    {
        // pass降级链get模型名称
        $modelName = di(ModelConfigAppService::class)->getChatModelTypeByFallbackChain($orgCode, $userId, $modelName);
        // get模型代理
        $dataIsolation = ModelGatewayDataIsolation::createByOrganizationCodeWithoutSubscription($orgCode, $userId);
        return di(ModelGatewayMapper::class)->getChatModelProxy($dataIsolation, $modelName);
    }

    /**
     * @param QuestionItem[] $associateQuestions
     * @param SearchDetailItem[] $noRepeatSearchContexts
     */
    private function chunkContexts(array $associateQuestions, array $noRepeatSearchContexts): array
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
