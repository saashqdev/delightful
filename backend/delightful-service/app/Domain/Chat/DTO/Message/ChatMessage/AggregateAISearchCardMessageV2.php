<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\DTO\Message\ChatMessage;

use App\Domain\Chat\DTO\Message\ChatMessage\Item\DeepSearch\EventItem;
use App\Domain\Chat\DTO\Message\ChatMessage\Item\DeepSearch\QuestionItem;
use App\Domain\Chat\DTO\Message\ChatMessage\Item\DeepSearch\QuestionSearchResult;
use App\Domain\Chat\DTO\Message\ChatMessage\Item\DeepSearch\SearchDetailItem;
use App\Domain\Chat\DTO\Message\ChatMessage\Item\DeepSearch\SummaryItem;
use App\Domain\Chat\DTO\Message\StreamMessage\StreamMessageTrait;
use App\Domain\Chat\DTO\Message\StreamMessageInterface;
use App\Domain\Chat\DTO\Message\TextContentInterface;
use App\Domain\Chat\Entity\ValueObject\AggregateSearch\SearchDeepLevel;
use App\Domain\Chat\Entity\ValueObject\MessageType\ChatMessageType;

/**
 * 聚合AIsearch的响应卡片message.
 */
class AggregateAISearchCardMessageV2 extends AbstractChatMessageStruct implements TextContentInterface, StreamMessageInterface
{
    use StreamMessageTrait;

    public const string NULL_PARENT_ID = '0';

    /**
     * associate_questions的 key的前缀，避免自动将string 0 转 int 0.
     */
    public const string QUESTION_DELIMITER = 'question_';

    # search级别：简单/search
    protected SearchDeepLevel $searchDeepLevel;

    /**
     * 子issue的关联issue。支持关联issue再产生子issue，但是会被拍平成二维array。
     * @var array<string,QuestionItem[]>
     * @example according touser输入的issue，生成关联issue。
     */
    protected ?array $associateQuestions;

    /**
     * （所有子issue的）search网页list.
     *
     * @var QuestionSearchResult[]
     */
    protected array $searchWebPages;

    /**
     * 由于多次子issuesearch时，会出现多个重复的searchresult，所以需要 ai 去重后，再丢给大model总结。
     *
     * @var SearchDetailItem[]
     */
    protected array $noRepeatSearchDetails;

    /**
     * 总结,分为思考过程和总结两部分.
     */
    protected SummaryItem $summary;

    /**
     * @var EventItem[]
     */
    protected array $events;

    /**
     * @var string 思维导图。markdown 格式的string
     */
    protected string $mindMap;

    /**
     * @var string ppt。markdown 格式的string
     */
    protected string $ppt;

    /**
     * get本次需要流式推送的field。
     * 支持一次推送多个field的流式message，如果 json 层级较深，use field_1.*.field_2 作为 key。 其中 * 是指array的下标。
     * service端会cache所有流式的数据，并在流式结束时一次性推送，以减少丢包的概率，提升message完整性。
     * for example：
     * [
     *     'users.0.name' => 'delightful',
     *     'total' => 32,
     * ].
     */
    private array $thisTimeStreamMessages;

    public function __construct(?array $messageStruct = null)
    {
        parent::__construct($messageStruct);
    }

    public function getTextContent(): string
    {
        return $this->getSummary()->getContent();
    }

    /**
     * @return null|array<string,QuestionItem[]>
     */
    public function getAssociateQuestions(): ?array
    {
        return $this->associateQuestions ?? null;
    }

    public function setAssociateQuestions(array $associateQuestions): void
    {
        // {
        //        "question_0": [
        //            {
        //                "parent_question_id": "0",
        //                "question_id": "1",
        //                "question": "小米集团旗下有哪些品牌"
        //            }
        //        ],
        //        "question_1": [
        //            {
        //                "parent_question_id": "1",
        //                "question_id": "3",
        //                "question": "百度是干嘛的"
        //            }
        //        ]
        //    }
        $this->associateQuestions = [];

        foreach ($associateQuestions as $key => $data) {
            if (str_contains((string) $key, self::QUESTION_DELIMITER) && is_array($data)) {
                // $data是 questionItem 的array
                foreach ($data as $item) {
                    $questionItem = $item instanceof QuestionItem ? $item : new QuestionItem($item);
                    $itemKey = self::QUESTION_DELIMITER . $questionItem->getParentQuestionId();
                    $this->associateQuestions[$itemKey][] = $questionItem;
                }
            } else {
                // 单个QuestionItem的情况
                $questionItem = $data instanceof QuestionItem ? $data : new QuestionItem($data);
                $itemKey = self::QUESTION_DELIMITER . $questionItem->getParentQuestionId();
                $this->associateQuestions[$itemKey][] = $questionItem;
            }
        }
    }

    /**
     * @return QuestionSearchResult[]
     */
    public function getSearchWebPages(): array
    {
        return $this->searchWebPages ?? [];
    }

    public function setSearchWebPages(array $searchWebPages): void
    {
        $this->searchWebPages = array_map(static function ($item) {
            return $item instanceof QuestionSearchResult ? $item : new QuestionSearchResult($item);
        }, $searchWebPages);
    }

    public function getSummary(): SummaryItem
    {
        return $this->summary ?? new SummaryItem();
    }

    public function setSummary(array|SummaryItem $summary): void
    {
        if (is_array($summary)) {
            $this->summary = new SummaryItem($summary);
        } else {
            $this->summary = $summary;
        }
    }

    /**
     * @return EventItem[]
     */
    public function getEvents(): array
    {
        return $this->events ?? [];
    }

    public function setEvents(array $events): void
    {
        $this->events = array_map(static function ($item) {
            return $item instanceof EventItem ? $item : new EventItem($item);
        }, $events);
    }

    public function getMindMap(): string
    {
        return $this->mindMap ?? '';
    }

    public function setMindMap(string $mindMap): void
    {
        $this->mindMap = $mindMap;
    }

    public function getPpt(): string
    {
        return $this->ppt ?? '';
    }

    public function setPpt(string $ppt): void
    {
        $this->ppt = $ppt;
    }

    public function getSearchDeepLevel(): SearchDeepLevel
    {
        return $this->searchDeepLevel;
    }

    public function setSearchDeepLevel(null|int|SearchDeepLevel|string $searchDeepLevel): AggregateAISearchCardMessageV2
    {
        if ($searchDeepLevel instanceof SearchDeepLevel) {
            $this->searchDeepLevel = $searchDeepLevel;
        } else {
            $this->searchDeepLevel = SearchDeepLevel::from((int) $searchDeepLevel);
        }
        return $this;
    }

    public function getNoRepeatSearchDetails(): array
    {
        return $this->noRepeatSearchDetails;
    }

    public function setNoRepeatSearchDetails(array $noRepeatSearchDetails): void
    {
        $this->noRepeatSearchDetails = array_map(static function ($item) {
            return $item instanceof SearchDetailItem ? $item : new SearchDetailItem($item);
        }, $noRepeatSearchDetails);
    }

    public function getThisTimeStreamMessages(): array
    {
        return $this->thisTimeStreamMessages;
    }

    public function setThisTimeStreamMessages(array $thisTimeStreamMessages): void
    {
        $this->thisTimeStreamMessages = $thisTimeStreamMessages;
    }

    protected function setMessageType(): void
    {
        $this->chatMessageType = ChatMessageType::AggregateAISearchCardV2;
    }
}
