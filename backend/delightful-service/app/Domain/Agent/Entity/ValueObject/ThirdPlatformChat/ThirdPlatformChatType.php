<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Agent\Entity\ValueObject\ThirdPlatformChat;

enum ThirdPlatformChatType: string
{
    /**
     * 钉钉机器person.
     */
    case DingRobot = 'ding_robot';

    /**
     * 企业微信机器person.
     */
    case WeChatRobot = 'wechat_robot';

    /**
     * 飞书机器person.
     */
    case FeiShuRobot = 'fei_shu_robot';

    public function getConversationPrefix(): string
    {
        return match ($this) {
            self::DingRobot => 'D',
            self::WeChatRobot => 'W',
            self::FeiShuRobot => 'F',
        };
    }
}
