<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Entity\ValueObject\MessageType;

/**
 * 聊天messagecontent的type.
 * value从0开始.
 */
enum ChatMessageType: string
{
    // 文本
    case Text = 'text';

    // 图片
    case Image = 'image';

    // 视频
    case Video = 'video';

    // 文件
    case File = 'file';
    case Files = 'files';

    // 附件
    case Attachment = 'attachment';

    // 语音
    case Voice = 'voice';

    // 文本卡片
    case TextCard = 'text_card';

    // markdown
    case Markdown = 'markdown';

    // 位置
    case Location = 'location';

    /**
     * 比较 low 的推送多次 seq，前端合并成一个渲染.
     */
    case AggregateAISearchCard = 'aggregate_ai_search_card';

    /**
     * 多次流式响应后，最终合并成一条message入库.
     */
    case AggregateAISearchCardV2 = 'aggregate_ai_search_card_v2';

    /**
     * 流式响应.
     */
    case StreamAggregateAISearchCard = 'stream_aggregate_ai_search_card';

    // 富文本
    case RichText = 'rich_text';

    // AI文生图卡片
    case AIImageCard = 'ai_image_card';

    // 图片转高清
    case ImageConvertHighCard = 'image_convert_high_card';

    // 通用 agent message
    case BeAgentCard = 'general_agent_card';

    /**
     * 未知message。
     * 由于版本迭代，发版time差异等原因，可能产生未知type的message。
     */
    case Unknown = 'unknown';

    case TextForm = 'text_form';
    case Raw = 'raw';

    public function getName(): string
    {
        return $this->value;
    }
}
