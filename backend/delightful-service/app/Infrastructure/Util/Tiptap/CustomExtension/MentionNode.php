<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Util\Tiptap\CustomExtension;

use App\Domain\Chat\DTO\Message\Common\MessageExtra\BeAgent\Mention\MentionInterface;
use App\Infrastructure\Util\Tiptap\AbstractCustomNode;
use App\Interfaces\Agent\Assembler\MentionAssembler;
use Hyperf\Codec\Json;

/**
 * rich text的@feature.
 */
class MentionNode extends AbstractCustomNode
{
    public static $name = 'mention';

    public function addAttributes(): array
    {
        return [
            'type' => [
                'type' => '',
                'isRequired' => true,
            ],
            'id' => [
                'default' => null,
                'isRequired' => false,
            ],
            'label' => [
                'default' => null,
                'isRequired' => false,
            ],
            'avatar' => [
                'default' => null,
            ],
            'attrs' => [
                'default' => null,
            ],
        ];
    }

    public function renderText($node): string
    {
        $nodeForArray = Json::decode(Json::encode($node));
        // 可能quote superAgent 的file/mcp/flow等
        $superAgentMention = MentionAssembler::fromArray($nodeForArray);
        if ($superAgentMention instanceof MentionInterface) {
            return $superAgentMention->getMentionTextStruct();
        }
        $userName = $nodeForArray['attrs']['label'] ?? '';
        return '@' . $userName;
    }
}
