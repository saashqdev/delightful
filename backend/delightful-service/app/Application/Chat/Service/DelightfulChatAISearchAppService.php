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
 * chatmessage相close.
 * @deprecated use DelightfulChatAISearchV2AppService replace
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
        // aipreparestarthairmessage,endinputstatus
        $this->delightfulConversationDomainService->agentOperateConversationStatusV2(
            ControlMessageType::EndConversationInput,
            $conversationId,
            $topicId
        );
        $this->logger->info(sprintf('mindSearch aggregateSearch startaggregatesearch  searchKeyword:%s searchtype:%s', $searchKeyword, $dto->getSearchDeepLevel()->name));
        $antiRepeatKey = md5($conversationId . $topicId . $searchKeyword);
        // prevent duplicate(notknow哪comebug):if同oneconversation同onetopicdown,2secondinsidehaveduplicatemessage,not触hairprocess
        if (! $this->redis->set($antiRepeatKey, '1', ['nx', 'ex' => 2])) {
            return;
        }
        // delightful-api two期requirepass inuser id
        $agentConversationEntity = $this->delightfulConversationDomainService->getConversationByIdWithoutCheck($conversationId);
        if (! $agentConversationEntity) {
            ExceptionBuilder::throw(ChatErrorCode::CONVERSATION_NOT_FOUND);
        }
        // 计fee,pass inis触hairassistantuser idandorganization code
        $dto->setUserId($agentConversationEntity->getReceiveId());
        $dto->setOrganizationCode($agentConversationEntity->getReceiveOrganizationCode());
        if (empty($dto->getRequestId())) {
            $requestId = CoContext::getRequestId() ?: (string) $this->idGenerator->generate();
            CoContext::setRequestId($requestId);
            $dto->setRequestId($requestId);
        }
        $dto->setAppMessageId((string) $this->idGenerator->generate());

        try {
            // 1.sendping pongresponse,representstartreply
            $this->sendPingPong($dto);
            // get im middlefinger定conversationdownsometopichistorymessage,asfor llm historymessage
            $rawHistoryMessages = $this->getDelightfulChatMessages($dto->getConversationId(), $dto->getTopicId());
            $dto->setDelightfulChatMessageHistory($rawHistoryMessages);
            // 3.0 sendsearch深degree
            $this->sendSearchDeepLevel($dto);
            // 2.searchuserissue.thiswithinone定willsplitonetimeassociateissue
            $simpleSearchResults = $this->searchUserQuestion($dto);
            // 3.according tooriginalissue + searchresult,by multi-dimensiondegreedecomposeassociateissue.
            // 3.1 generateassociateissueandsendgivefront端
            $associateQuestionsQueryVo = $this->getAssociateQuestionsQueryVo($dto, $simpleSearchResults['search'] ?? []);
            $associateQuestions = $this->generateAndSendAssociateQuestions($dto, $associateQuestionsQueryVo, '0');
            // 3.2 according toassociateissue,hairup简singlesearch(not拿webpagedetail),andfilter掉duplicateor者andissueassociatepropertynothighwebpagecontent
            $noRepeatSearchContexts = $this->generateSearchResults($dto, $associateQuestions);
            $this->sleepToFixBug();
            // 3.4 according tosearch深degree,decidewhethercontinuesearchassociateissue子issue
            $readPagesDetailChannel = new Channel(count($associateQuestions));
            // 3.4.a 深degreesearch
            if ($dto->getSearchDeepLevel() === SearchDeepLevel::DEEP) {
                $this->deepSearch($dto, $associateQuestions, $noRepeatSearchContexts, $readPagesDetailChannel);
            } else {
                // 3.4.b 简singlesearch
                $readPagesDetailChannel = null;
                $this->simpleSearch($dto, $associateQuestions, $noRepeatSearchContexts);
            }
            // use channel communication,intensive readingproceduremiddlethenpushmessagegivefront端
            $associateQuestionIds = array_keys($associateQuestions);
            $this->sendLLMResponseForAssociateQuestions($dto, $associateQuestionIds, $readPagesDetailChannel);
            $this->sleepToFixBug(0.3);
            // 4. according toeachassociateissuereply,generatesummary.
            $summarize = $this->generateAndSendSummary($dto, $noRepeatSearchContexts, $associateQuestions);
            // 5. according tosummary,generate额outsidecontent(thinking导graph,PPT,eventetc)
            if ($dto->getSearchDeepLevel() === SearchDeepLevel::DEEP) {
                $this->generateAndSendExtra($dto, $noRepeatSearchContexts, $summarize);
            }
            // 6. sendping pongresponse,representendreply
            $this->sendPingPong($dto);
        } catch (Throwable $e) {
            // 7. hair生exceptiono clock,sendterminationmessage,andthrowexception
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
     * MagicInternetsearch简single版,adaptprocess,onlysupport简singlesearch.
     * @throws Throwable
     * @throws RedisException
     */
    public function easyInternetSearch(DelightfulChatAggregateSearchReqDTO $dto): ?DelightfulAggregateSearchSummaryDTO
    {
        $conversationId = $dto->getConversationId();
        $topicId = $dto->getTopicId();
        $searchKeyword = $dto->getUserMessage();
        $antiRepeatKey = md5($conversationId . $topicId . $searchKeyword);
        // prevent duplicate(notknow哪comebug):if同oneconversation同onetopicdown,2secondinsidehaveduplicatemessage,not触hairprocess
        if (! $this->redis->set($antiRepeatKey, '1', ['nx', 'ex' => 2])) {
            return null;
        }
        if (empty($dto->getRequestId())) {
            $requestId = CoContext::getRequestId() ?: (string) $this->idGenerator->generate();
            $dto->setRequestId($requestId);
        }
        $dto->setAppMessageId((string) $this->idGenerator->generate());

        try {
            // 1.searchuserissue.thiswithinone定willsplitonetimeassociateissue
            $simpleSearchResults = $this->searchUserQuestion($dto);

            // 2.according tooriginalissue + searchresult,by multi-dimensiondegreedecomposeassociateissue.
            // 2.1 generateassociateissue
            $associateQuestionsQueryVo = $this->getAssociateQuestionsQueryVo($dto, $simpleSearchResults['search'] ?? []);
            $associateQuestions = $this->generateAssociateQuestions($associateQuestionsQueryVo);
            // 2.2 according toassociateissue,hairup简singlesearch(not拿webpagedetail),andfilter掉duplicateor者andissueassociatepropertynothighwebpagecontent
            $this->sleepToFixBug();
            $noRepeatSearchContexts = $this->generateSearchResults($dto, $associateQuestions);

            // 3. according toeachassociateissuereply,generatesummary.
            return $this->generateSummary($dto, $noRepeatSearchContexts, $associateQuestions);
        } catch (Throwable $e) {
            // 4. hair生exceptiono clock,recorderror
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

    // generatenullassociateissue子issue

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
                // alreadygenerateassociateissue,preparesendsearchresult
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
                        // pushfront 5 searchresult. andcompatiblehistorydata,key usesmallcamel case
                        'search' => $this->getSearchData($currentContextChunk),
                        'total_words' => $totalWords,
                        // allonline materialstotal
                        'match_count' => random_int(1000, 5000),
                        // getsummarywebpagequantity
                        'page_count' => $pageCount,
                    ],
                    $dto->getAppMessageId(),
                    $dto->getTopicId()
                );
                $this->logger->info(sprintf(
                    'getSearchResults associateissue:%s nullblankissuegenerateandpushcompleted end计o clock,耗o clock %s second',
                    $associateQuestion['title'],
                    TimeUtil::getMillisecondDiffFromNow($start) / 1000
                ));
            });
            ++$questionIndex;
        }
        $parallel->wait();
        $this->logger->info(sprintf(
            'getSearchResults 所haveassociateissuenullblankissuepushcompleted end计o clock,耗o clock:%s second',
            TimeUtil::getMillisecondDiffFromNow($start) / 1000
        ));
    }

    /**
     * according toassociateissueandissue简singlesearch,generateassociateissue子issue.(associateissue子issue目frontonlyuseatfront端show,notwillaccording to子issueagaintimesearch+intensive reading).
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
                // todo byatthiswithinisto所have维degreesummarybackagainintensive reading,thereforelosteach维degreequantity,onlycanrandomgenerate.
                // etc待front端adjustrender ui
                $pageCount = random_int(30, 60);
                $onePageWords = random_int(200, 2000);
                $totalWords = $pageCount * $onePageWords;
                $searchResult = [
                    'search_keywords' => $associateSubQuestions,
                    'search' => $this->getSearchData($currentContextChunk),
                    'total_words' => $totalWords,
                    // allonline materialstotal
                    'match_count' => random_int(1000, 5000),
                    // getsummarywebpagequantity
                    'page_count' => $pageCount,
                ];
                // alreadygenerateassociateissue,preparesendsearchresult
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
                    'getSearchResults associateissue:%s 子issue %s generateandpushcompleted end计o clock,耗o clock %s second',
                    $associateQuestion['title'],
                    Json::encode($associateSubQuestions, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    TimeUtil::getMillisecondDiffFromNow($start) / 1000
                ));
            });
            ++$questionIndex;
        }
        $parallel->wait();
        $this->logger->info(sprintf(
            'getSearchResults 所haveassociateissue子issue generateandpushcompleted end计o clock,耗o clock:%s second',
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
        // according touserupdown文,decompose子issue.needcomprehenduserwant to askwhat,againgo拆searchkeyword.
        $searchKeywords = $this->delightfulLLMDomainService->generateSearchKeywordsByUserInput($dto, $modelInterface);
        $queryVo->setSearchKeywords($searchKeywords);
        $searchResult = $this->delightfulLLMDomainService->getSearchResults($queryVo);
        $this->logger->info(sprintf(
            'getSearchResults searchUserQuestion 虚nulldecomposekeywordandsearchuserissue end计o clock,耗o clock %s second',
            microtime(true) - $start
        ));
        return $searchResult;
    }

    /**
     * generateandsendassociateissue.
     * @throws Throwable
     */
    public function generateAndSendAssociateQuestions(
        DelightfulChatAggregateSearchReqDTO $dto,
        AISearchCommonQueryVo $queryVo,
        string $questionParentId
    ): array {
        // generateassociateissue
        $associateQuestions = $this->generateAssociateQuestions($queryVo);
        // willassociateissuepushgivefront端
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
     * according tooriginalissue + searchresult,by multi-dimensiondegreedecomposeissue.
     * @todo supportpass in维degreequantityrange
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
            'getSearchResults issue:%s associateissue: %s .according tooriginalissue + searchresult,by multi-dimensiondegreedecomposeassociateissueandpushcompleted end计o clock,耗o clock %s second',
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
        // according toassociateissue,hairup简singlesearch(not拿webpagedetail),andfilter掉duplicateor者andissueassociatepropertynothighwebpagecontent
        $searchKeywords = array_column($associateQuestions, 'title');
        $queryVo = (new AISearchCommonQueryVo())
            ->setSearchKeywords($searchKeywords)
            ->setSearchEngine($dto->getSearchEngine())
            ->setLanguage($dto->getLanguage());
        $allSearchContexts = $this->delightfulLLMDomainService->getSearchResults($queryVo)['search'] ?? [];
        // filterduplicatecontent
        $noRepeatSearchContexts = [];
        if (! empty($allSearchContexts)) {
            // cleansearchresultmiddleduplicateitem
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
            // andsearchkeywordassociatepropertymosthighandnotduplicatesearchresult
            $noRepeatSearchContexts = $this->delightfulLLMDomainService->filterSearchContexts($queryVo);
            $costMircoTime = TimeUtil::getMillisecondDiffFromNow($start);
            $this->logger->info(sprintf(
                'mindSearch getSearchResults filterSearchContexts cleansearchresultmiddleduplicateitem cleanfront:%s cleanback:%s end计o clock accumulated costo clock %s second',
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
     * pushmostnextassociateissue llm responseendidentifier.
     * @throws Throwable
     */
    public function sendAssociateQuestionResponse(DelightfulChatAggregateSearchReqDTO $dto, string $associateQuestionId): void
    {
        $content = ['llm_response' => 'already经foryou are looking fortoanswer,请etc待generatesummary'];
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
     * intensive readingproceduremiddle,隔randomtimepushonetimeassociateissuesearchcompletedgivefront端.
     * 完allintensive readingcompletedo clock,mostbackagain推onetime
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
        // byatisstreamoutputresponse,thereforeletfront端judge ai quotesearch url.
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
        // generatesummary
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
        // 深degreesearchsummaryuse deepseek-r1 model
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
        // streamresponse
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
                // createone seq useatrenderplaceholder
                $this->delightfulChatDomainService->createAndSendStreamStartSequence(
                    (new CreateStreamSeqDTO())->setTopicId($dto->getTopicId())->setAppMessageId($dto->getAppMessageId()),
                    $messageContent,
                    $senderConversationEntity
                );
                // pushonetime parent_id/id/type data,useatupdatestreamcache,avoidfinal落libraryo clock,parent_id/id/type datalost
                $this->delightfulChatDomainService->streamSendJsonMessage($senderSeqDTO->getAppMessageId(), [
                    'parent_id' => '0',
                    'id' => $summaryMessageId,
                    'type' => AggregateAISearchCardResponseType::LLM_RESPONSE,
                ]);
            } else {
                $streamOptions->setStatus(StreamMessageStatus::Processing);
            }
            // streamcontent
            if ($assistantMessage->hasReasoningContent()) {
                // sendthinkcontent
                $this->delightfulChatDomainService->streamSendJsonMessage($senderSeqDTO->getAppMessageId(), [
                    'reasoning_content' => $assistantMessage->getReasoningContent(),
                ]);
            } else {
                // summarycontent
                $this->delightfulChatDomainService->streamSendJsonMessage($senderSeqDTO->getAppMessageId(), [
                    'llm_response' => $assistantMessage->getContent(),
                ]);
                // 累addstreamcontent,useasmostbackreturn
                $summarizeStreamResponse .= $assistantMessage->getContent();
            }
        }
        // sendend
        $this->delightfulChatDomainService->streamSendJsonMessage($senderSeqDTO->getAppMessageId(), [], StreamMessageStatus::Completed);
        $this->logger->info(sprintf('getSearchResults generatesummary,end计o clock,耗o clock:%s second', microtime(true) - $start));
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
        // generatethinking导graphandPPT
        $extraContentParallel = new Parallel(3);
        $modelInterface = $this->getChatModel($dto->getOrganizationCode(), $dto->getUserId());
        $extraContentParallel->add(function () use ($summarize, $dto, $modelInterface) {
            // odin willmodify vo objectmiddlevalue,avoidpollution,copyagainpass in
            CoContext::setRequestId($dto->getRequestId());
            // thinking导graph
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
                // onlyrecord
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
        $this->logger->info(sprintf('getSearchResults generatePPT,end计o clock,耗o clock: %s second', microtime(true) - $start));
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
        $this->logger->info(sprintf('getSearchResults generatethinking导graph,end计o clock,耗o clock: %s second', microtime(true) - $start));
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
        $this->logger->info(sprintf('getSearchResults generateevent,end计o clock,耗o clock: %s second', microtime(true) - $start));
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
        foreach ($noRepeatSearchContexts as $search) { // $search already经is切slicebackyuan素
            // compatiblehistorydata,key usesmallcamel case
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
                'detail' => '', // section省streamquantity,givefront端推messagenot传 detail
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
        // limitandhairrequestquantity
        $parallel = new Parallel(5);
        $timeStart = microtime(true);
        $currentDetailReadCount = 0;
        foreach ($noRepeatSearchContexts as $context) {
            $requestId = CoContext::getRequestId();
            $parallel->add(function () use ($context, $detailReadMaxNum, $requestId, &$currentDetailReadCount, $readPagesDetailChannel, $perReadResponseNum, &$questionsNum) {
                // Zhihu readingnotonepoint
                if (str_contains($context->getCachedPageUrl(), 'zhihu.com')) {
                    return;
                }
                // only取finger定quantitywebpagedetailedcontent
                if ($currentDetailReadCount > $detailReadMaxNum) {
                    return;
                }
                CoContext::setRequestId($requestId);
                $htmlReader = make(HTMLReader::class);
                try {
                    // usesnapshotgo拿content!!
                    $content = $htmlReader->getText($context->getCachedPageUrl());
                    $content = mb_substr($content, 0, 2048);
                    $context->setDetail($content);
                    ++$currentDetailReadCount;
                    // according tointensive readingenterdegree,pushassociateissuesearchcompletedgivefront端
                    if (($currentDetailReadCount % $perReadResponseNum === 0) && $readPagesDetailChannel->isAvailable()) {
                        $readPagesDetailChannel->push(1, 5);
                        // needpushcountdecrease
                        --$questionsNum;
                    }
                } catch (Throwable $e) {
                    $this->logger->error(sprintf(
                        'mindSearch getSearchResults getdetailedcontento clockhair生error:%s,file:%s,line:%s trace:%s',
                        $e->getMessage(),
                        $e->getFile(),
                        $e->getLine(),
                        $e->getTraceAsString()
                    ));
                }
            });
        }
        $parallel->wait();
        // ifalsohaveneedpushcount,looppush
        while ($questionsNum > 0 && $readPagesDetailChannel->isAvailable()) {
            $readPagesDetailChannel->push(1, 5);
            --$questionsNum;
        }
        $this->logger->info(sprintf(
            'mindSearch getSearchResults intensive reading所havesearchresult intensive readingaccumulated costo clock:%s second',
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
            // 3.4.a.1 andline:according toassociateissueandissue简singlesearch,generateassociateissue子issue.(associateissue子issueonlyuseatfront端show,目frontnotwillaccording to子issueagaintimesearch+intensive reading)
            $this->generateAndSendAssociateSubQuestions($dto, $noRepeatSearchContexts, $associateQuestions);
        });
        $parallel->add(function () use (&$noRepeatSearchContexts, $readPagesDetailChannel, $associateQuestions) {
            // 3.4.a.2 andline:intensive readingassociateissuesearchwebpagedetail
            $this->getSearchPageDetails($noRepeatSearchContexts, $associateQuestions, $readPagesDetailChannel);
        });
        $parallel->wait();
        $this->logger->info(sprintf(
            'mindSearch getSearchResults generateassociateissue子issue,andintensive reading所havesearchresult,end accumulated costo clock:%s second',
            number_format(TimeUtil::getMillisecondDiffFromNow($timeStart) / 1000, 2)
        ));
    }

    private function sleepToFixBug(float $seconds = 0.2): void
    {
        // !!! byat收item方messagegenerateisasync,mayberandom order,therefore,thiswithin sleep onesmallwill,尽quantityguarantee收item方messagegenerateorder
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
        // todo streamresponse,拿tocustomerclient transmissioncome app_message_id ,asforresponsetime唯oneidentifier
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
        // settingtopic id
        $this->getDelightfulChatMessageAppService()->aiSendMessage($seqDTO, $appMessageId);
    }

    /**
     * get immiddlefinger定conversationdownsometopichistorymessage,asfor llm historymessage.
     */
    private function getDelightfulChatMessages(string $delightfulChatConversationId, string $topicId): array
    {
        $rawHistoryMessages = $this->delightfulChatDomainService->getLLMContentForAgent($delightfulChatConversationId, $topicId);
        // 取mostbackfinger定item数conversationrecord
        return array_slice($rawHistoryMessages, -10);
    }

    private function getDelightfulChatMessageAppService(): DelightfulChatMessageAppService
    {
        return di(DelightfulChatMessageAppService::class);
    }

    private function getChatModel(string $orgCode, string $userId, string $modelName = LLMModelEnum::DEEPSEEK_V3->value): ModelInterface
    {
        // pass降level链getmodelname
        $modelName = di(ModelConfigAppService::class)->getChatModelTypeByFallbackChain($orgCode, $userId, $modelName);
        // getmodelproxy
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
