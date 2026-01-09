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
    // queueetcscenariocorejump
    case Ping = 'ping';

    // createsessionwindow
    case CreateConversation = 'create_conversation';

    // moveexceptsessionwindow(listnotdisplay)
    case HideConversation = 'hide_conversation';

    // settopsessionwindow
    case TopConversation = 'top_conversation';

    // sessiondo not disturb
    case MuteConversation = 'mute_conversation';

    // alreadyread
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

    // setsessiontopic(setforemptytableshowleavetopic)
    case SetConversationTopic = 'set_conversation_topic';

    // creategroup chat
    case GroupCreate = 'group_create';

    // updategroup chat
    case GroupUpdate = 'group_update';

    // systemnotify(xxaddinput/leavegroup chat,group warmreminderetc)
    case SystemNotice = 'system_notice';

    // groupmemberchangemore
    case GroupUsersAdd = 'group_users_add';

    // groupmemberchangemore
    case GroupUsersRemove = 'group_users_remove';

    // dissolvegroup chat
    case GroupDisband = 'group_disband';

    // groupmemberrolechangemore(batchquantitysetadministrator/normalmember)
    case GroupUserRoleChange = 'group_user_role_change';

    // transferletgroup owner
    case GroupOwnerChange = 'group_owner_change';

    // assistantinteractionfingercommand
    case AgentInstruct = 'bot_instruct';

    // translateconfigurationitem
    case TranslateConfig = 'translate_config';

    // translate
    case Translate = 'translate';

    // addgoodfriendsuccess
    case AddFriendSuccess = 'add_friend_success';

    // addgoodfriendapply
    case AddFriendApply = 'add_friend_apply';

    /**
     * unknownmessage.
     * byatversioniteration,hairversiontimediffetcreason,maybeproduceunknowntypemessage.
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
        // notcontaineditmessagestatuschangemore!
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
