<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Entity\ValueObject\MessageType;

/**
 * chatmessagecontent的type.
 */
enum ControlMessageType: string
{
    // queue等场景的心跳
    case Ping = 'ping';

    // createsession窗口
    case CreateConversation = 'create_conversation';

    // 移除session窗口（list不显示）
    case HideConversation = 'hide_conversation';

    // 置顶session窗口
    case TopConversation = 'top_conversation';

    // session免打扰
    case MuteConversation = 'mute_conversation';

    // 已读
    case SeenMessages = 'seen_messages';

    // 已查看message
    case ReadMessage = 'read_message';

    // withdrawmessage
    case RevokeMessage = 'revoke_message';

    // editmessage
    case EditMessage = 'edit_message';

    // 开始在session窗口input
    case StartConversationInput = 'start_conversation_input';

    // 结束在session窗口input
    case EndConversationInput = 'end_conversation_input';

    // opensession窗口
    case OpenConversation = 'open_conversation';

    // create话题
    case CreateTopic = 'create_topic';

    // update话题
    case UpdateTopic = 'update_topic';

    // delete话题
    case DeleteTopic = 'delete_topic';

    // setsession的话题(set为空table示离开话题)
    case SetConversationTopic = 'set_conversation_topic';

    // creategroup chat
    case GroupCreate = 'group_create';

    // updategroup chat
    case GroupUpdate = 'group_update';

    // 系统notify(xx加入/离开group chat,群温馨reminder等)
    case SystemNotice = 'system_notice';

    // 群member变更
    case GroupUsersAdd = 'group_users_add';

    // 群member变更
    case GroupUsersRemove = 'group_users_remove';

    // 解散group chat
    case GroupDisband = 'group_disband';

    // 群memberrole变更(批量set管理员/普通member)
    case GroupUserRoleChange = 'group_user_role_change';

    // 转让群主
    case GroupOwnerChange = 'group_owner_change';

    // 助理的交互指令
    case AgentInstruct = 'bot_instruct';

    // 翻译configuration项
    case TranslateConfig = 'translate_config';

    // 翻译
    case Translate = 'translate';

    // 添加好友success
    case AddFriendSuccess = 'add_friend_success';

    // 添加好友申请
    case AddFriendApply = 'add_friend_apply';

    /**
     * 未知message。
     * 由于version迭代，发版timediff等原因，可能产生未知type的message。
     */
    case Unknown = 'unknown';

    public function getName(): string
    {
        return $this->value;
    }

    /**
     * @return ControlMessageType[]
     */
    public static function getMessageStatusChangeType(): array
    {
        // 不containeditmessage的status变更!
        // editmessage不will改变message的status,只will改变message的content.
        return [
            self::RevokeMessage,
            self::ReadMessage,
            self::SeenMessages,
        ];
    }

    public function allowCallUserFlow(): bool
    {
        return match ($this) {
            self::AddFriendSuccess,
            self::OpenConversation => true,
            default => false,
        };
    }
}
