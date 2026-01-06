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
    // 获取我的当前组织
    Router::get('/me/organization-code', [DelightfulUserOrganizationApi::class, 'getCurrentOrganizationCode']);
    // 修改我的当前组织
    Router::put('/me/organization-code', [DelightfulUserOrganizationApi::class, 'setCurrentOrganizationCode']);
    // 获取账号下可切换的组织列表
    Router::get('/me/organizations', [DelightfulUserOrganizationApi::class, 'listOrganizations']);
});

// 通讯录（需要组织上下文）
Router::addGroup('/api/v1/contact', static function () {
    // 用户相关
    Router::addGroup('/users', static function () {
        // 用户所在群组列表
        Router::get('/self/groups', [DelightfulChatHttpApi::class, 'getUserGroupList']);
        // 更新用户信息
        Router::patch('/me', [DelightfulChatUserApi::class, 'updateUserInfo']);
        // 是否允许更新用户信息
        Router::get('/me/update-permission', [DelightfulChatUserApi::class, 'getUserUpdatePermission']);
        // 按用户 id 批量查询
        Router::post('/queries', [DelightfulChatAdminContactApi::class, 'userGetByIds']);
        // 按手机号/昵称等查询
        Router::get('/search', [DelightfulChatAdminContactApi::class, 'searchForSelect']);
        // 设置隐藏用户
        Router::put('/visibility', [DelightfulChatAdminContactApi::class, 'updateUsersOptionByIds']);

        // 用户设置相关
        Router::addGroup('/setting', static function () {
            Router::post('', [DelightfulUserSettingApi::class, 'save']);
            Router::get('/{key}', [DelightfulUserSettingApi::class, 'get']);
            Router::post('/queries', [DelightfulUserSettingApi::class, 'queries']);

            // 超级麦吉话题模型配置
            Router::put('/be-delightful/topic-model/{topicId}', [DelightfulUserSettingApi::class, 'saveProjectTopicModelConfig']);
            Router::get('/be-delightful/topic-model/{topicId}', [DelightfulUserSettingApi::class, 'getProjectTopicModelConfig']);
        });
    });

    // 部门相关
    Router::addGroup('/departments', static function () {
        Router::get('/{id}/children', [DelightfulChatAdminContactApi::class, 'getSubList']);
        Router::get('/search', [DelightfulChatAdminContactApi::class, 'departmentSearch']);
        Router::get('/{id}', [DelightfulChatAdminContactApi::class, 'getDepartmentInfoById']);
        // 部门下的用户
        Router::get('/{id}/users', [DelightfulChatAdminContactApi::class, 'departmentUserList']);
        // 设置隐藏部门
        Router::put('/visibility', [DelightfulChatAdminContactApi::class, 'updateDepartmentsOptionByIds']);
    });

    // 群组
    Router::addGroup('/groups', static function () {
        // 批量获取群信息（名称、公告等）
        Router::post('/queries', [DelightfulChatHttpApi::class, 'getDelightfulGroupList']);
        Router::post('', [DelightfulChatHttpApi::class, 'createChatGroup']);
        Router::put('/{id}', [DelightfulChatHttpApi::class, 'GroupUpdateInfo']);
        // 群成员管理
        Router::get('/{id}/members', [DelightfulChatHttpApi::class, 'getGroupUserList']);
        // 批量添加群成员
        Router::post('/{id}/members', [DelightfulChatHttpApi::class, 'groupAddUsers']);
        // 批量移除群成员
        Router::delete('/{id}/members', [DelightfulChatHttpApi::class, 'groupKickUsers']);
        // 主动退群
        Router::delete('/{id}/members/self', [DelightfulChatHttpApi::class, 'leaveGroupConversation']);
        // 群主转让
        Router::put('/{id}/owner', [DelightfulChatHttpApi::class, 'groupTransferOwner']);
        Router::delete('/{id}', [DelightfulChatHttpApi::class, 'groupDelete']);
    });

    // 好友
    Router::addGroup('/friends', static function () {
        Router::post('/{friendId}', [DelightfulChatUserApi::class, 'addFriend']);
        Router::get('', [DelightfulChatUserApi::class, 'getUserFriendList']);
    });
}, ['middleware' => [RequestContextMiddleware::class]]);
