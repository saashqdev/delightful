<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Entity\ValueObject\MessageType;

/**
 * chatmessagecontenttype.
 * valuefrom0start.
 */
enum ChatMessageType: string
{
    // text
    case Text = 'text';

    // image
    case Image = 'image';

    // video
    case Video = 'video';

    // file
    case File = 'file';
    case Files = 'files';

    // attachment
    case Attachment = 'attachment';

    // voice
    case Voice = 'voice';

    // textcard
    case TextCard = 'text_card';

    // markdown
    case Markdown = 'markdown';

    // position
    case Location = 'location';

    /**
     * compare low push多time seq，front端mergebecomeone渲染.
     */
    case AggregateAISearchCard = 'aggregate_ai_search_card';

    /**
     * 多timestreamresponseback，finalmergebecomeoneitemmessage入library.
     */
    case AggregateAISearchCardV2 = 'aggregate_ai_search_card_v2';

    /**
     * streamresponse.
     */
    case StreamAggregateAISearchCard = 'stream_aggregate_ai_search_card';

    // rich text
    case RichText = 'rich_text';

    // AI文生graphcard
    case AIImageCard = 'ai_image_card';

    // image转高清
    case ImageConvertHighCard = 'image_convert_high_card';

    // 通use agent message
    case BeAgentCard = 'general_agent_card';

    /**
     * unknownmessage。
     * byatversion迭代，hair版timediffetcreason，maybe产生unknowntypemessage。
     */
    case Unknown = 'unknown';

    case TextForm = 'text_form';
    case Raw = 'raw';

    public function getName(): string
    {
        return $this->value;
    }
}
