<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Entity\ValueObject\MessageType;

/**
 * chatmessagecontenttype.
 */
enum ControlMessageType: string
{
    // queueetcscenariocore跳
    case Ping = 'ping';

    // createsessionwindow
    case CreateConversation = 'create_conversation';

    // 移exceptsessionwindow(listnotdisplay)
    case HideConversation = 'hide_conversation';

    // 置topsessionwindow
    case TopConversation = 'top_conversation';

    // session免打扰
    case MuteConversation = 'mute_conversation';

    // already读
    case SeenMessages = 'seen_messages';

    // alreadyviewmessage
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

    // createtopic
    case CreateTopic = 'create_topic';

    // updatetopic
    case UpdateTopic = 'update_topic';

    // deletetopic
    case DeleteTopic = 'delete_topic';

    // setsessiontopic(setforemptytable示leavetopic)
    case SetConversationTopic = 'set_conversation_topic';

    // creategroup chat
    case GroupCreate = 'group_create';

    // updategroup chat
    case GroupUpdate = 'group_update';

    // systemnotify(xxadd入/leavegroup chat,群温馨reminderetc)
    case SystemNotice = 'system_notice';

    // 群member变more
    case GroupUsersAdd = 'group_users_add';

    // 群member变more
    case GroupUsersRemove = 'group_users_remove';

    // dissolvegroup chat
    case GroupDisband = 'group_disband';

    // 群memberrole变more(batchquantitysetadministrator/normalmember)
    case GroupUserRoleChange = 'group_user_role_change';

    // 转letgroup owner
    case GroupOwnerChange = 'group_owner_change';

    // assistant交互finger令
    case AgentInstruct = 'bot_instruct';

    // translateconfigurationitem
    case TranslateConfig = 'translate_config';

    // translate
    case Translate = 'translate';

    // addgood友success
    case AddFriendSuccess = 'add_friend_success';

    // addgood友apply
    case AddFriendApply = 'add_friend_apply';

    /**
     * unknownmessage.
     * byatversioniteration,hair版timediffetcreason,maybeproduceunknowntypemessage.
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
        // notcontaineditmessagestatus变more!
        // editmessagenotwillaltermessagestatus,onlywillaltermessagecontent.
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
