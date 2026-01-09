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
 * aggregateAIsearchresponsecardmessage.
 */
class AggregateAISearchCardMessageV2 extends AbstractChatMessageStruct implements TextContentInterface, StreamMessageInterface
{
    use StreamMessageTrait;

    public const string NULL_PARENT_ID = '0';

    /**
     * associate_questions keyfrontsuffix,avoidfromautowillstring 0 transfer int 0.
     */
    public const string QUESTION_DELIMITER = 'question_';

    # searchlevelother:simplesingle/search
    protected SearchDeepLevel $searchDeepLevel;

    /**
     * childissueassociateissue.supportassociateissueagainproducechildissue,butiswillbeflattenbecometwodimensionarray.
     * @var array<string,QuestionItem[]>
     * @example according touserinputissue,generateassociateissue.
     */
    protected ?array $associateQuestions;

    /**
     * ( havechildissue)searchwebpagelist.
     *
     * @var QuestionSearchResult[]
     */
    protected array $searchWebPages;

    /**
     * byatmultipletimechildissuesearcho clock,willoutmultipleduplicatesearchresult, byneed ai goreloadback,againdiscardgivebigmodelsummary.
     *
     * @var SearchDetailItem[]
     */
    protected array $noRepeatSearchDetails;

    /**
     * summary,minuteforthinkprocedureandsummarytwo departmentsminute.
     */
    protected SummaryItem $summary;

    /**
     * @var EventItem[]
     */
    protected array $events;

    /**
     * @var string thinkingguidegraph.markdown formatstring
     */
    protected string $mindMap;

    /**
     * @var string ppt.markdown formatstring
     */
    protected string $ppt;

    /**
     * getthistimeneedstreampushfield.
     * supportonetimepushmultiplefieldstreammessage,if json layerlevelmoredeep,use field_1.*.field_2 asfor key. itsmiddle * isfingerarraydownmark.
     * serviceclientwillcache havestreamdata,andinstreamendo clockonetimepropertypush,bydecreasediscardpackagegenerallyrate,enhancemessagecompleteproperty.
     * for example:
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
        //                "question": "smallricecollectionteam flagdownhavewhichthesebrand"
        //            }
        //        ],
        //        "question_1": [
        //            {
        //                "parent_question_id": "1",
        //                "question_id": "3",
        //                "question": "hundreddegreeiswhat for"
        //            }
        //        ]
        //    }
        $this->associateQuestions = [];

        foreach ($associateQuestions as $key => $data) {
            if (str_contains((string) $key, self::QUESTION_DELIMITER) && is_array($data)) {
                // $datais questionItem array
                foreach ($data as $item) {
                    $questionItem = $item instanceof QuestionItem ? $item : new QuestionItem($item);
                    $itemKey = self::QUESTION_DELIMITER . $questionItem->getParentQuestionId();
                    $this->associateQuestions[$itemKey][] = $questionItem;
                }
            } else {
                // singleQuestionItemsituation
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
