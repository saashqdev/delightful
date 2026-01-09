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
    // queueetc场景的core跳
    case Ping = 'ping';

    // createsessionwindow
    case CreateConversation = 'create_conversation';

    // 移exceptsessionwindow（listnotdisplay）
    case HideConversation = 'hide_conversation';

    // 置topsessionwindow
    case TopConversation = 'top_conversation';

    // session免打扰
    case MuteConversation = 'mute_conversation';

    // 已读
    case SeenMessages = 'seen_messages';

    // 已viewmessage
    case ReadMessage = 'read_message';

    // withdrawmessage
    case RevokeMessage = 'revoke_message';

    // editmessage
    case EditMessage = 'edit_message';

    // startinsessionwindowinput
    case StartConversationInput = 'start_conversation_input';

    // endinsessionwindowinput
    case EndConversationInput = 'end_conversation_input';

    // opensessionwindow
    case OpenConversation = 'open_conversation';

    // create话题
    case CreateTopic = 'create_topic';

    // update话题
    case UpdateTopic = 'update_topic';

    // delete话题
    case DeleteTopic = 'delete_topic';

    // setsession的话题(set为空table示leave话题)
    case SetConversationTopic = 'set_conversation_topic';

    // creategroup chat
    case GroupCreate = 'group_create';

    // updategroup chat
    case GroupUpdate = 'group_update';

    // systemnotify(xx加入/leavegroup chat,群温馨reminderetc)
    case SystemNotice = 'system_notice';

    // 群member变more
    case GroupUsersAdd = 'group_users_add';

    // 群member变more
    case GroupUsersRemove = 'group_users_remove';

    // 解散group chat
    case GroupDisband = 'group_disband';

    // 群memberrole变more(批quantitysetadministrator/普通member)
    case GroupUserRoleChange = 'group_user_role_change';

    // 转让群主
    case GroupOwnerChange = 'group_owner_change';

    // 助理的交互finger令
    case AgentInstruct = 'bot_instruct';

    // 翻译configurationitem
    case TranslateConfig = 'translate_config';

    // 翻译
    case Translate = 'translate';

    // add好友success
    case AddFriendSuccess = 'add_friend_success';

    // add好友申请
    case AddFriendApply = 'add_friend_apply';

    /**
     * 未知message。
     * 由atversion迭代，hair版timediffetcreason，可能产生未知type的message。
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
        // notcontaineditmessage的status变more!
        // editmessagenotwill改变message的status,只will改变message的content.
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
