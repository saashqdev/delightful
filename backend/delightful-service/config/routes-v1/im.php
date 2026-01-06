<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use App\Infrastructure\Util\Middleware\RequestContextMiddleware;
use App\Interfaces\Chat\Facade\DelightfulChatHttpApi;
use Hyperf\HttpServer\Router\Router;

Router::addGroup('/api/v1/im', static function () {
    // Typing completions (conversationId and topicId are not required)
    Router::post('/typing/completions', [DelightfulChatHttpApi::class, 'typingCompletions']);

    // conversation
    Router::addGroup('/conversations', static function () {
        // Topic list query interface
        Router::post('/{conversationId}/topics/queries', [DelightfulChatHttpApi::class, 'getTopicList']);
        // Intelligent topic renaming
        Router::put('/{conversationId}/topics/{topicId}/name', [DelightfulChatHttpApi::class, 'intelligenceGetTopicName']);
        // Conversation list query interface
        Router::post('/queries', [DelightfulChatHttpApi::class, 'conversationQueries']);
        // Typing completions when chatting in the window
        Router::post('/{conversationId}/completions', [DelightfulChatHttpApi::class, 'conversationChatCompletions']);
        // Save interaction instructions
        Router::post('/{conversationId}/instructs', [DelightfulChatHttpApi::class, 'saveInstruct']);

        // Conversation history message scrolling load
        Router::post('/{conversationId}/messages/queries', [DelightfulChatHttpApi::class, 'messageQueries']);
        // (Temporary solution for frontend performance issues) Get the latest messages of several groups by conversation id.
        Router::post('/messages/queries', [DelightfulChatHttpApi::class, 'conversationsMessagesGroupQueries']);
    });

    // Message
    Router::addGroup('/messages', static function () {
        // (New device login) Pull the latest messages of the account
        Router::get('', [DelightfulChatHttpApi::class, 'pullRecentMessage']);
        // Pull all organization messages of the account (supports full sliding window pull)
        Router::get('/page', [DelightfulChatHttpApi::class, 'pullByPageToken']);
        // Message recipient list
        Router::get('/{messageId}/recipients', [DelightfulChatHttpApi::class, 'getMessageReceiveList']);
        // Pull message by app_message_id
        Router::post('/app-message-ids/{appMessageId}/queries', [DelightfulChatHttpApi::class, 'pullByAppMessageId']);
    });

    // File
    Router::addGroup('/files', static function () {
        Router::post('', [DelightfulChatHttpApi::class, 'fileUpload']);
        Router::post('/download-urls/queries', [DelightfulChatHttpApi::class, 'getFileDownUrl']);
    });
}, ['middleware' => [RequestContextMiddleware::class]]);
