<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Agent\Entity\ValueObject\ThirdPlatformChat;

enum ThirdPlatformChatType: string
{
    /**
     * DingTalkmachineperson.
     */
    case DingRobot = 'ding_robot';

    /**
     * enterprise微信machineperson.
     */
    case WeChatRobot = 'wechat_robot';

    /**
     * Feishumachineperson.
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
