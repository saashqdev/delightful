<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Util\Tiptap\CustomExtension;

use App\Infrastructure\Util\Tiptap\AbstractCustomNode;
use Hyperf\Codec\Json;

/**
 * 富文本的table情解析.
 */
class DelightfulEmojiNode extends AbstractCustomNode
{
    public static $name = 'delightful-emoji';

    public function addAttributes(): array
    {
        return [
            'code' => [
                'type' => '',
                'isRequired' => true,
            ],
            'suffix' => [
                'default' => null,
                'isRequired' => true,
            ],
            'size' => [
                'default' => null,
            ],
            'ns' => [
                'default' => null,
            ],
        ];
    }

    public function renderText($node): string
    {
        $nodeForArray = Json::decode(Json::encode($node));
        $delightfulEmoji = $nodeForArray['attrs']['code'] ?? '';
        ! empty($delightfulEmoji) && $delightfulEmoji = sprintf('[%s]', $delightfulEmoji);
        return $delightfulEmoji;
    }
}
