<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
return [
    'agent' => [
        'user_call_agent_fail_notice' => 'Sorry, there was an error processing your request. Please rephrase your question so I can provide an accurate answer',
    ],
    'message' => [
        'not_found' => 'Message not found',
        'send_failed' => 'Message send failed',
        'type_error' => 'Message type error',
        'delivery_failed' => 'Message delivery failed',
        'stream' => [
            'type_not_support' => 'Stream message type not supported',
        ],
        'voice' => [
            'attachment_required' => 'Voice message must contain one audio attachment',
            'single_attachment_only' => 'Voice message can only contain one attachment, current count: :count',
            'attachment_empty' => 'Voice message attachment cannot be empty',
            'audio_format_required' => 'Voice message attachment must be audio format, current type: :type',
            'duration_positive' => 'Voice duration must be greater than 0 seconds, current duration: :duration seconds',
            'duration_exceeds_limit' => 'Voice duration cannot exceed :max_duration seconds, current duration: :duration seconds',
        ],
        'rollback' => [
            'seq_id_not_found' => 'Message sequence ID not found',
            'delightful_message_id_not_found' => 'Associated message ID not found',
        ],
    ],
    'already_exist' => 'Already exists',
    'not_found' => 'Not found',
    'ai' => [
        'not_found' => 'Agent not found',
    ],
    'conversation' => [
        'type_error' => 'Conversation type error',
        'not_found' => 'Conversation does not exist',
        'deleted' => 'Conversation has been deleted',
        'organization_code_empty' => 'Conversation organization code is empty',
    ],
    'common' => [
        'param_error' => 'Parameter :param error',
    ],
    'seq' => [
        'id_error' => 'Message sequence ID error',
        'not_found' => 'Message sequence does not exist',
    ],
    'user' => [
        'no_organization' => 'User has no organization',
        'receive_not_found' => 'Recipient not found',
        'not_found' => 'User does not exist',
        'not_create_account' => 'User has not created an account yet',
        'sync_failed' => 'User synchronization failed',
    ],
    'data' => [
        'write_failed' => 'Data write failed',
    ],
    'context' => [
        'lost' => 'Request context lost',
    ],
    'refer_message' => [
        'not_found' => 'Referenced message not found',
    ],
    'topic' => [
        'not_found' => 'Topic not found',
        'message' => [
            'not_found' => 'Topic message not found',
        ],
        'send_message_and_rename_topic' => 'Please send a message before attempting to smart rename topic',
        'id_not_found' => 'Topic ID not found',
        'system_default_topic' => 'System default topic',
    ],
    'group' => [
        'user_select_error' => 'Group member selection error',
        'user_num_limit_error' => 'Group member count exceeds limit',
        'create_error' => 'Group creation failed',
        'not_found' => 'Group does not exist',
        'user_already_in_group' => 'All users are already in the group',
        'update_error' => 'Group information update failed',
        'no_user_to_remove' => 'No users to remove from group',
        'cannot_kick_owner' => 'Cannot remove group owner',
        'transfer_owner_before_leave' => 'Please transfer group ownership before leaving',
        'only_owner_can_disband' => 'Only group owner can disband group',
        'only_owner_can_transfer' => 'Only group owner can transfer group ownership',
    ],
    'department' => [
        'not_found' => 'Department does not exist',
        'sync_not_support' => 'Synchronization of this third-party platform department data not supported',
        'sync_failed' => 'Department synchronization failed',
    ],
    'login' => [
        'failed' => 'Login failed',
    ],
    'operation' => [
        'failed' => 'Operation failed',
    ],
    'file' => [
        'not_found' => 'File in message not found',
    ],
    'platform' => [
        'organization_code_not_found' => 'Platform organization code not found',
        'organization_env_not_found' => 'Platform organization environment not found',
    ],
    'delightful' => [
        'environment_config_error' => 'Delightful environment configuration error',
        'environment_not_found' => 'Delightful environment not found',
        'ticket_not_found' => 'Delightful appTicket not found',
    ],
    'authorization' => [
        'invalid' => 'Authorization invalid',
    ],
    'stream' => [
        'sequence_id_not_found' => 'Stream message sequence not found',
        'message_not_found' => 'Stream message not found',
        'receive_message_id_not_found' => 'Stream message receiver message ID not found',
    ],
];
