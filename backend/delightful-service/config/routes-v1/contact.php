<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use App\Infrastructure\Util\Middleware\RequestContextMiddleware;
use App\Interfaces\Chat\Facade\DelightfulChatAdminContactApi;
use App\Interfaces\Chat\Facade\DelightfulChatHttpApi;
use App\Interfaces\Chat\Facade\DelightfulChatUserApi;
use App\Interfaces\Contact\Facade\DelightfulUserOrganizationApi;
use App\Interfaces\Contact\Facade\DelightfulUserSettingApi;
use Hyperf\HttpServer\Router\Router;

// Account-related routes (independent of RequestContextMiddleware to support cross-organization queries)
Router::addGroup('/api/v1/contact/accounts', function () {
    // Get user details for all organizations under the current account
    Router::get('/me/users', [DelightfulChatUserApi::class, 'getAccountUsersDetail']);
    // Get my current organization
    Router::get('/me/organization-code', [DelightfulUserOrganizationApi::class, 'getCurrentOrganizationCode']);
    // Modify my current organization
    Router::put('/me/organization-code', [DelightfulUserOrganizationApi::class, 'setCurrentOrganizationCode']);
    // Get list of organizations available for switching under account
    Router::get('/me/organizations', [DelightfulUserOrganizationApi::class, 'listOrganizations']);
});

// Contact directory (requires organization context)
Router::addGroup('/api/v1/contact', static function () {
    // User-related
    Router::addGroup('/users', static function () {
        // User's group list
        Router::get('/self/groups', [DelightfulChatHttpApi::class, 'getUserGroupList']);
        // Update user info
        Router::patch('/me', [DelightfulChatUserApi::class, 'updateUserInfo']);
        // Check if user info update is allowed
        Router::get('/me/update-permission', [DelightfulChatUserApi::class, 'getUserUpdatePermission']);
        // Batch query by user ID
        Router::post('/queries', [DelightfulChatAdminContactApi::class, 'userGetByIds']);
        // Search by phone/nickname etc.
        Router::get('/search', [DelightfulChatAdminContactApi::class, 'searchForSelect']);
        // Set user visibility
        Router::put('/visibility', [DelightfulChatAdminContactApi::class, 'updateUsersOptionByIds']);

        // User settings related
        Router::addGroup('/setting', static function () {
            Router::post('', [DelightfulUserSettingApi::class, 'save']);
            Router::get('/{key}', [DelightfulUserSettingApi::class, 'get']);
            Router::post('/queries', [DelightfulUserSettingApi::class, 'queries']);

            // Super Delightful topic model configuration
            Router::put('/be-delightful/topic-model/{topicId}', [DelightfulUserSettingApi::class, 'saveProjectTopicModelConfig']);
            Router::get('/be-delightful/topic-model/{topicId}', [DelightfulUserSettingApi::class, 'getProjectTopicModelConfig']);
        });
    });

    // Department-related
    Router::addGroup('/departments', static function () {
        Router::get('/{id}/children', [DelightfulChatAdminContactApi::class, 'getSubList']);
        Router::get('/search', [DelightfulChatAdminContactApi::class, 'departmentSearch']);
        Router::get('/{id}', [DelightfulChatAdminContactApi::class, 'getDepartmentInfoById']);
        // Users in department
        Router::get('/{id}/users', [DelightfulChatAdminContactApi::class, 'departmentUserList']);
        // Set department visibility
        Router::put('/visibility', [DelightfulChatAdminContactApi::class, 'updateDepartmentsOptionByIds']);
    });

    // Groups
    Router::addGroup('/groups', static function () {
        // Batch get group info (name, announcement, etc.)
        Router::post('/queries', [DelightfulChatHttpApi::class, 'getDelightfulGroupList']);
        Router::post('', [DelightfulChatHttpApi::class, 'createChatGroup']);
        Router::put('/{id}', [DelightfulChatHttpApi::class, 'GroupUpdateInfo']);
        // Group member management
        Router::get('/{id}/members', [DelightfulChatHttpApi::class, 'getGroupUserList']);
        // Batch add group members
        Router::post('/{id}/members', [DelightfulChatHttpApi::class, 'groupAddUsers']);
        // Batch remove group members
        Router::delete('/{id}/members', [DelightfulChatHttpApi::class, 'groupKickUsers']);
        // Leave group voluntarily
        Router::delete('/{id}/members/self', [DelightfulChatHttpApi::class, 'leaveGroupConversation']);
        // Transfer group ownership
        Router::put('/{id}/owner', [DelightfulChatHttpApi::class, 'groupTransferOwner']);
        Router::delete('/{id}', [DelightfulChatHttpApi::class, 'groupDelete']);
    });

    // Friends
    Router::addGroup('/friends', static function () {
        Router::post('/{friendId}', [DelightfulChatUserApi::class, 'addFriend']);
        Router::get('', [DelightfulChatUserApi::class, 'getUserFriendList']);
    });
}, ['middleware' => [RequestContextMiddleware::class]]);
