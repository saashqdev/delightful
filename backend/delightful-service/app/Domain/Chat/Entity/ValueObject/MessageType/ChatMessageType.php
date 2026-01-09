<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Entity\ValueObject\MessageType;

/**
 * chatmessagecontent的type.
 * value从0开始.
 */
enum ChatMessageType: string
{
    // 文本
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

    // 文本card
    case TextCard = 'text_card';

    // markdown
    case Markdown = 'markdown';

    // position
    case Location = 'location';

    /**
     * 比较 low 的push多次 seq，前端merge成一个渲染.
     */
    case AggregateAISearchCard = 'aggregate_ai_search_card';

    /**
     * 多次streamresponse后，finalmerge成一条message入库.
     */
    case AggregateAISearchCardV2 = 'aggregate_ai_search_card_v2';

    /**
     * streamresponse.
     */
    case StreamAggregateAISearchCard = 'stream_aggregate_ai_search_card';

    // rich text
    case RichText = 'rich_text';

    // AI文生图card
    case AIImageCard = 'ai_image_card';

    // image转高清
    case ImageConvertHighCard = 'image_convert_high_card';

    // 通用 agent message
    case BeAgentCard = 'general_agent_card';

    /**
     * 未知message。
     * 由于version迭代，发版time差异等原因，可能产生未知type的message。
     */
    case Unknown = 'unknown';

    case TextForm = 'text_form';
    case Raw = 'raw';

    public function getName(): string
    {
        return $this->value;
    }
}
