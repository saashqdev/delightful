<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace HyperfTest\Cases\Api\BeAgent;

use Delightful\BeDelightful\Domain\Share\Constant\ResourceType;
use Delightful\BeDelightful\Domain\Share\Service\ResourceShareDomainService;
use Delightful\BeDelightful\Domain\BeAgent\Service\ProjectMemberDomainService;
use Hyperf\Context\ApplicationContext;
use Mockery;

/**
 * @internal
 * 项目邀请链接API测试
 */
class ProjectInvitationLinkApiTest extends AbstractApiTest
{
    private const BASE_URI = '/api/v1/be-agent/projects';

    private const INVITATION_BASE_URI = '/api/v1/be-agent/invitation';

    private string $projectId = '816065897791012866';

    // 测试过程中generate的邀请链接信息
    private ?string $invitationToken = null;

    private ?string $invitationPassword = null;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * 测试邀请链接完整流程.
     */
    public function testInvitationLinkCompleteFlow(): void
    {
        $projectId = $this->projectId;

        // 0. 清理测试数据 - 确保test2user不是项目成员
        $this->cleanupTestData($projectId);

        // 1. 项目所有者开启邀请链接
        $this->switchUserTest1();
        $this->assertToggleInvitationLinkOn($projectId);

        // 2. 获取邀请链接信息
        $linkInfo = $this->getInvitationLink($projectId);
        $this->invitationToken = $linkInfo['data']['token'];

        // 3. 设置密码保护
        $this->assertSetPasswordProtection($projectId, true);

        // 4. 外部user通过Token获取邀请信息
        $this->switchUserTest2();
        $invitationInfo = $this->getInvitationByToken($this->invitationToken);
        $this->assertTrue($invitationInfo['data']['requires_password']);

        // 5. 外部user尝试加入项目（密码error）
        $this->joinProjectWithWrongPassword($this->invitationToken);

        // 6. 项目所有者重置密码
        $this->switchUserTest1();
        $passwordInfo = $this->resetInvitationPassword($projectId);
        $this->invitationPassword = $passwordInfo['data']['password'];

        // 7. 外部user使用正确密码加入项目
        $this->switchUserTest2();
        $this->joinProjectSuccess($this->invitationToken, $this->invitationPassword);

        // 8. validateuser已成为项目成员（再次加入应该fail）
        $this->joinProjectAlreadyMember($this->invitationToken, $this->invitationPassword);

        // 9. 项目所有者关闭邀请链接
        $this->switchUserTest1();
        $this->assertToggleInvitationLinkOff($projectId);

        // 10. 外部user尝试访问已关闭的邀请链接
        $this->switchUserTest2();
        $this->getInvitationByTokenDisabled($this->invitationToken);
    }

    /**
     * 测试邀请链接权限控制.
     */
    public function testInvitationLinkPermissions(): void
    {
        $projectId = $this->projectId;

        // 1. 非项目成员尝试管理邀请链接（应该fail）
        $this->switchUserTest2();
        $this->getInvitationLink($projectId, 51202); // 权限不足

        // 2. 项目所有者可以管理邀请链接
        $this->switchUserTest1();
        $this->getInvitationLink($projectId, 1000); // success
    }

    /**
     * 测试权限级别管理.
     */
    public function testPermissionLevelManagement(): void
    {
        $projectId = $this->projectId;

        $this->switchUserTest1();

        // 1. 开启邀请链接
        $this->toggleInvitationLink($projectId, true);

        // 2. 测试修改权限级别为管理权限
        $this->updateInvitationPermission($projectId, 'manage');

        // 3. 测试修改权限级别为编辑权限
        $this->updateInvitationPermission($projectId, 'editor');

        // 4. 测试修改权限级别为查看权限
        $this->updateInvitationPermission($projectId, 'viewer');
    }

    /**
     * 获取邀请链接信息.
     */
    public function getInvitationLink(string $projectId, int $expectedCode = 1000): array
    {
        $response = $this->client->get(
            self::BASE_URI . "/{$projectId}/invitation-links",
            [],
            $this->getCommonHeaders()
        );

        $this->assertNotNull($response, '响应不应该为null');
        $this->assertEquals($expectedCode, $response['code'], $response['message'] ?? '');

        return $response;
    }

    /**
     * 开启/关闭邀请链接.
     */
    public function toggleInvitationLink(string $projectId, bool $enabled, int $expectedCode = 1000): array
    {
        $response = $this->client->put(
            self::BASE_URI . "/{$projectId}/invitation-links/toggle",
            ['enabled' => $enabled],
            $this->getCommonHeaders()
        );

        $this->assertNotNull($response, '响应不应该为null');
        $this->assertEquals($expectedCode, $response['code'], $response['message'] ?? '');

        return $response;
    }

    /**
     * 重置邀请链接.
     */
    public function resetInvitationLink(string $projectId, int $expectedCode = 1000): array
    {
        $response = $this->client->post(
            self::BASE_URI . "/{$projectId}/invitation-links/reset",
            [],
            $this->getCommonHeaders()
        );

        $this->assertNotNull($response, '响应不应该为null');
        $this->assertEquals($expectedCode, $response['code'], $response['message'] ?? '');

        return $response;
    }

    /**
     * 设置密码保护.
     */
    public function setInvitationPassword(string $projectId, bool $enabled, int $expectedCode = 1000): array
    {
        $response = $this->client->post(
            self::BASE_URI . "/{$projectId}/invitation-links/password",
            ['enabled' => $enabled],
            $this->getCommonHeaders()
        );

        $this->assertNotNull($response, '响应不应该为null');
        $this->assertEquals($expectedCode, $response['code'], $response['message'] ?? '');

        return $response;
    }

    /**
     * 重置密码
     */
    public function resetInvitationPassword(string $projectId, int $expectedCode = 1000): array
    {
        $response = $this->client->post(
            self::BASE_URI . "/{$projectId}/invitation-links/reset-password",
            [],
            $this->getCommonHeaders()
        );

        $this->assertNotNull($response, '响应不应该为null');
        $this->assertEquals($expectedCode, $response['code'], $response['message'] ?? '');

        return $response;
    }

    /**
     * 修改权限级别.
     */
    public function updateInvitationPermission(string $projectId, string $permission, int $expectedCode = 1000): array
    {
        $response = $this->client->put(
            self::BASE_URI . "/{$projectId}/invitation-links/permission",
            ['default_join_permission' => $permission],
            $this->getCommonHeaders()
        );

        $this->assertNotNull($response, '响应不应该为null');
        $this->assertEquals($expectedCode, $response['code'], $response['message'] ?? '');

        if ($expectedCode === 1000) {
            $this->assertEquals($permission, $response['data']['default_join_permission']);
        }

        return $response;
    }

    /**
     * 通过Token获取邀请信息.
     */
    public function getInvitationByToken(string $token, int $expectedCode = 1000): array
    {
        $response = $this->client->get(
            self::INVITATION_BASE_URI . "/links/{$token}",
            [],
            $this->getCommonHeaders()
        );

        $this->assertNotNull($response, '响应不应该为null');
        $this->assertEquals($expectedCode, $response['code'], $response['message'] ?? '');

        if ($expectedCode === 1000) {
            $this->assertArrayHasKey('project_name', $response['data']);
            $this->assertArrayHasKey('project_description', $response['data']);
            $this->assertArrayHasKey('requires_password', $response['data']);
            $this->assertArrayHasKey('default_join_permission', $response['data']);
            $this->assertArrayHasKey('has_joined', $response['data']);
            $this->assertArrayHasKey('creator_name', $response['data']);
            $this->assertArrayHasKey('creator_avatar', $response['data']);
            $this->assertIsBool($response['data']['has_joined']);
        }

        return $response;
    }

    /**
     * 获取已禁用的邀请链接（应该fail）.
     */
    public function getInvitationByTokenDisabled(string $token): void
    {
        $response = $this->getInvitationByToken($token, 51222); // 邀请链接已禁用
    }

    /**
     * 加入项目（密码error）.
     */
    public function joinProjectWithWrongPassword(string $token): void
    {
        $response = $this->client->post(
            self::INVITATION_BASE_URI . '/join',
            [
                'token' => $token,
                'password' => 'wrong_password',
            ],
            $this->getCommonHeaders()
        );

        $this->assertEquals(51220, $response['code']); // 密码error
    }

    /**
     * success加入项目.
     */
    public function joinProjectSuccess(string $token, ?string $password = null): array
    {
        $data = ['token' => $token];
        if ($password) {
            $data['password'] = $password;
        }

        $response = $this->client->post(
            self::INVITATION_BASE_URI . '/join',
            $data,
            $this->getCommonHeaders()
        );

        $this->assertEquals(1000, $response['code']);
        $this->assertArrayHasKey('project_id', $response['data']);
        $this->assertArrayHasKey('user_role', $response['data']);
        $this->assertArrayHasKey('join_method', $response['data']);
        $this->assertEquals('link', $response['data']['join_method']);

        return $response;
    }

    /**
     * 尝试重复加入项目（应该fail）.
     */
    public function joinProjectAlreadyMember(string $token, ?string $password = null): void
    {
        $data = ['token' => $token];
        if ($password) {
            $data['password'] = $password;
        }

        $response = $this->client->post(
            self::INVITATION_BASE_URI . '/join',
            $data,
            $this->getCommonHeaders()
        );

        $this->assertEquals(51225, $response['code']); // 已经是项目成员
    }

    // =================== 边界condition测试 ===================

    /**
     * 测试无效Token访问.
     */
    public function testInvalidTokenAccess(): void
    {
        $this->switchUserTest2();
        $invalidToken = 'invalid_token_123456789';

        $response = $this->getInvitationByToken($invalidToken, 51217); // Token无效
    }

    /**
     * 测试权限边界.
     */
    public function testPermissionBoundaries(): void
    {
        $projectId = $this->projectId;
        $this->switchUserTest1();

        // 测试无效权限级别
        $response = $this->client->put(
            self::BASE_URI . "/{$projectId}/invitation-links/permission",
            ['default_join_permission' => 'invalid_permission'],
            $this->getCommonHeaders()
        );

        $this->assertNotNull($response, '响应不应该为null');
        $this->assertEquals(51215, $response['code']); // 无效权限级别
    }

    /**
     * 测试并发操作.
     */
    public function testConcurrentOperations(): void
    {
        $projectId = $this->projectId;
        $this->switchUserTest1();

        // 清理测试数据
        $this->getResourceShareDomainService()->deleteShareByResource($projectId, ResourceType::ProjectInvitation->value);

        // 连续快速开启/关闭邀请链接
        $this->toggleInvitationLink($projectId, true);
        $this->toggleInvitationLink($projectId, false);
        $this->toggleInvitationLink($projectId, true);

        // validate最终status
        $response = $this->getInvitationLink($projectId);
        $this->assertEquals(1000, $response['code']);

        // validate链接是启用status（兼容number和booleanvalue）
        $isEnabled = $response['data']['is_enabled'];
        $this->assertTrue($isEnabled === true || $isEnabled === 1, '邀请链接应该是启用status');
    }

    /**
     * 测试密码安全性.
     */
    public function testPasswordSecurity(): void
    {
        $projectId = $this->projectId;
        $this->switchUserTest1();

        // 1. 开启邀请链接
        $this->toggleInvitationLink($projectId, true);

        // 2. 多次设置密码保护，validate密码generate
        $password1 = $this->setInvitationPassword($projectId, true);
        $password2 = $this->resetInvitationPassword($projectId);
        $password3 = $this->resetInvitationPassword($projectId);

        // validate每次generate的密码都不同
        $this->assertNotEquals($password1['data']['password'] ?? '', $password2['data']['password']);
        $this->assertNotEquals($password2['data']['password'], $password3['data']['password']);

        // validate密码长度和格式
        $password = $password3['data']['password'];
        $this->assertEquals(5, strlen($password)); // 密码长度应该是5位
        $this->assertMatchesRegularExpression('/^\d{5}$/', $password); // 只contain5位number
    }

    /**
     * 测试密码保护开关功能 - 关闭后再开启密码不会更改.
     */
    public function testPasswordTogglePreservation(): void
    {
        $projectId = $this->projectId;
        $this->switchUserTest1();

        // 0. 清理测试数据
        $this->cleanupTestData($projectId);

        // 1. 开启邀请链接
        $this->toggleInvitationLink($projectId, true);

        // 2. 设置密码保护
        $initialPasswordResponse = $this->setInvitationPassword($projectId, true);
        $this->assertEquals(1000, $initialPasswordResponse['code']);

        $originalPassword = $initialPasswordResponse['data']['password'];
        $this->assertNotEmpty($originalPassword);
        $this->assertEquals(5, strlen($originalPassword));

        // 3. 关闭密码保护
        $disableResponse = $this->setInvitationPassword($projectId, false);
        $this->assertEquals(1000, $disableResponse['code']);

        // 4. validate关闭status下访问链接不需要密码
        $linkResponse = $this->getInvitationLink($projectId);
        $this->assertEquals(1000, $linkResponse['code']);
        $token = $linkResponse['data']['token'];

        $linkInfo = $this->getInvitationByToken($token);
        $this->assertEquals(1000, $linkInfo['code']);
        $this->assertFalse($linkInfo['data']['requires_password']);

        // 5. 重新开启密码保护
        $enableResponse = $this->setInvitationPassword($projectId, true);
        $this->assertEquals(1000, $enableResponse['code']);

        // 6. validate密码保持不变
        $restoredPassword = $enableResponse['data']['password'];
        $this->assertEquals($originalPassword, $restoredPassword, '重新开启密码保护后，密码应该保持不变');

        // 7. validate开启status下访问链接需要密码
        $linkInfo = $this->getInvitationByToken($token);
        $this->assertEquals(1000, $linkInfo['code']);
        $this->assertTrue($linkInfo['data']['requires_password']);

        // 8. validate使用原密码可以success加入项目
        $this->switchUserTest2();
        $joinResult = $this->joinProjectSuccess($token, $originalPassword);
        $this->assertEquals(1000, $joinResult['code']);
        $this->assertEquals('viewer', $joinResult['data']['user_role']);
    }

    /**
     * 测试密码修改功能.
     */
    public function testChangePassword(): void
    {
        $projectId = $this->projectId;
        $this->switchUserTest1();

        // 0. 清理测试数据并重置邀请链接
        $this->cleanupTestData($projectId);

        // 通过领域服务删除现有的邀请链接
        $this->getResourceShareDomainService()->deleteShareByResource($projectId, ResourceType::ProjectInvitation->value);

        // 1. 开启邀请链接
        $this->toggleInvitationLink($projectId, true);

        // 2. 设置初始密码保护
        $initialPasswordResponse = $this->setInvitationPassword($projectId, true);
        $this->assertEquals(1000, $initialPasswordResponse['code']);

        $originalPassword = $initialPasswordResponse['data']['password'];
        $this->assertEquals(5, strlen($originalPassword)); // validate密码长度为5位
        $this->assertMatchesRegularExpression('/^\d{5}$/', $originalPassword); // validate是5位number

        // 3. 修改密码为自定义密码
        $customPassword = 'mypass123';
        $changePasswordResponse = $this->changeInvitationPassword($projectId, $customPassword);
        $this->assertEquals(1000, $changePasswordResponse['code']);
        $this->assertEquals($customPassword, $changePasswordResponse['data']['password']);

        // 4. validate原密码不能使用
        $linkResponse = $this->getInvitationLink($projectId);
        $token = $linkResponse['data']['token'];

        $this->switchUserTest2();
        // 使用原密码应该fail
        $data = ['token' => $token, 'password' => $originalPassword];
        $response = $this->client->post(
            self::INVITATION_BASE_URI . '/join',
            $data,
            $this->getCommonHeaders()
        );
        $this->assertEquals(51220, $response['code'], 'error密码应该return51220error码');

        // 5. validate新密码可以正常使用
        $joinResult = $this->joinProjectSuccess($token, $customPassword);
        $this->assertEquals(1000, $joinResult['code']);
        $this->assertEquals('viewer', $joinResult['data']['user_role']);

        // 6. 清理测试数据
        $this->cleanupTestData($projectId);

        // 7. 测试无效密码格式（nullstring和超长密码）
        $this->switchUserTest1();
        $this->toggleInvitationLink($projectId, true);

        // 测试null密码
        $response = $this->changeInvitationPassword($projectId, '', 51220);
        $this->assertEquals(51220, $response['code']);

        // 测试超长密码（19位）
        $response = $this->changeInvitationPassword($projectId, str_repeat('1', 19), 51220);
        $this->assertEquals(51220, $response['code']);

        // 8. 测试有效的各种密码格式
        $validPasswords = ['123', '12345', 'abcde', '12a34', str_repeat('x', 18)];
        foreach ($validPasswords as $validPassword) {
            $response = $this->changeInvitationPassword($projectId, $validPassword, 1000);
            $this->assertEquals(1000, $response['code']);
            $this->assertEquals($validPassword, $response['data']['password']);
        }
    }

    /**
     * 测试邀请链接userstatus和创建者信息.
     */
    public function testInvitationLinkUserStatusAndCreatorInfo(): void
    {
        $projectId = $this->projectId;

        // 0. 确保切换到test1user并清理测试数据
        $this->switchUserTest1();
        $this->cleanupTestData($projectId);
        $this->getResourceShareDomainService()->deleteShareByResource($projectId, ResourceType::ProjectInvitation->value, '', true);

        // 1. 项目创建者（test1）开启邀请链接
        $this->toggleInvitationLink($projectId, true);

        // 获取邀请链接信息
        $linkResponse = $this->getInvitationLink($projectId);
        $token = $linkResponse['data']['token'];

        // 2. 测试创建者访问邀请链接 - should show has_joined = true
        $this->switchUserTest1();
        $invitationInfo = $this->getInvitationByToken($token);

        // 检查基本响应结构
        $this->assertEquals(1000, $invitationInfo['code'], '获取邀请信息应该success');
        $this->assertIsArray($invitationInfo['data'], '响应数据应该是array');

        // 检查新增字段是否存在
        $this->assertArrayHasKey('has_joined', $invitationInfo['data'], '响应应该containhas_joined字段');
        $this->assertArrayHasKey('creator_name', $invitationInfo['data'], '响应应该containcreator_name字段');
        $this->assertArrayHasKey('creator_avatar', $invitationInfo['data'], '响应应该containcreator_avatar字段');
        $this->assertArrayHasKey('creator_id', $invitationInfo['data'], '响应应该containcreator_id字段');

        // 检查字段value
        $this->assertTrue($invitationInfo['data']['has_joined'], '创建者应该显示已加入项目');
        $this->assertNotEmpty($invitationInfo['data']['creator_id'], 'creator_id不应该为null');

        // validate字段type
        $this->assertIsBool($invitationInfo['data']['has_joined'], 'has_joined应该是booleantype');
        $this->assertIsString($invitationInfo['data']['creator_name'], 'creator_name应该是stringtype');
        $this->assertIsString($invitationInfo['data']['creator_avatar'], 'creator_avatar应该是stringtype');

        // 3. 测试未加入user访问邀请链接 - should show has_joined = false
        $this->switchUserTest2();
        $invitationInfo = $this->getInvitationByToken($token);

        // 检查基本响应
        $this->assertEquals(1000, $invitationInfo['code'], '未加入user获取邀请信息应该success');
        $this->assertArrayHasKey('has_joined', $invitationInfo['data'], '响应应该containhas_joined字段');
        $this->assertFalse($invitationInfo['data']['has_joined'], '未加入user应该显示未加入项目');

        // validate创建者信息依然存在（不管谁访问，创建者信息都应该显示）
        $this->assertArrayHasKey('creator_name', $invitationInfo['data'], '响应应该containcreator_name字段');
        $this->assertArrayHasKey('creator_avatar', $invitationInfo['data'], '响应应该containcreator_avatar字段');

        // 4. test2user加入项目 - 需要提供密码
        $password = $linkResponse['data']['password'] ?? null;
        $joinResult = $this->joinProjectSuccess($token, $password);
        $this->assertEquals(1000, $joinResult['code']);

        // 5. 测试已加入成员访问邀请链接 - should show has_joined = true
        $invitationInfo = $this->getInvitationByToken($token);

        $this->assertEquals(1000, $invitationInfo['code'], '已加入成员获取邀请信息应该success');
        $this->assertArrayHasKey('has_joined', $invitationInfo['data'], '响应应该containhas_joined字段');
        $this->assertTrue($invitationInfo['data']['has_joined'], '已加入成员应该显示已加入项目');

        // 6. validate响应数据完整性
        $data = $invitationInfo['data'];
        $requiredFields = [
            'project_id', 'project_name', 'project_description',
            'organization_code', 'creator_id', 'creator_name', 'creator_avatar',
            'default_join_permission', 'requires_password', 'token', 'has_joined',
        ];

        foreach ($requiredFields as $field) {
            $this->assertArrayHasKey($field, $data, "响应数据应contain字段: {$field}");
        }

        // 7. 清理测试数据并切换回test1user
        $this->switchUserTest1();
        $this->cleanupTestData($projectId);
        $this->getResourceShareDomainService()->deleteShareByResource($projectId, ResourceType::ProjectInvitation->value);
    }

    /**
     * 开启邀请链接 (私有辅助method).
     */
    private function assertToggleInvitationLinkOn(string $projectId): void
    {
        $response = $this->toggleInvitationLink($projectId, true);

        $this->assertEquals(1000, $response['code']);

        // validate链接是启用status（兼容number和booleanvalue）
        $isEnabled = $response['data']['is_enabled'];
        $this->assertTrue($isEnabled === true || $isEnabled === 1, '邀请链接应该是启用status');

        $this->assertNotEmpty($response['data']['token']);
        $this->assertEquals('viewer', $response['data']['default_join_permission']);
    }

    /**
     * 关闭邀请链接 (私有辅助method).
     */
    private function assertToggleInvitationLinkOff(string $projectId): void
    {
        $response = $this->toggleInvitationLink($projectId, false);

        $this->assertEquals(1000, $response['code']);

        // validate链接是禁用status（兼容number和booleanvalue）
        $isEnabled = $response['data']['is_enabled'];
        $this->assertTrue($isEnabled === false || $isEnabled === 0, '邀请链接应该是禁用status');
    }

    /**
     * 设置密码保护 (私有辅助method).
     */
    private function assertSetPasswordProtection(string $projectId, bool $enabled): void
    {
        $response = $this->setInvitationPassword($projectId, $enabled);

        $this->assertEquals(1000, $response['code']);

        if ($enabled) {
            $this->assertArrayHasKey('password', $response['data']);
            $this->assertNotEmpty($response['data']['password']);
            $this->invitationPassword = $response['data']['password'];
        }
    }

    /**
     * 修改邀请链接密码 (私有辅助method).
     */
    private function changeInvitationPassword(string $projectId, string $password, int $expectedCode = 1000): array
    {
        $response = $this->client->put(
            self::BASE_URI . "/{$projectId}/invitation-links/change-password",
            ['password' => $password],
            $this->getCommonHeaders()
        );

        $this->assertNotNull($response, '响应不应该为null');
        $this->assertEquals($expectedCode, $response['code'], $response['message'] ?? '');

        return $response;
    }

    /**
     * 清理测试数据.
     */
    private function cleanupTestData(string $projectId): void
    {
        // 通过领域服务删除test2user的项目成员关系（如果存在）
        $this->getProjectMemberDomainService()->removeMemberByUser((int) $projectId, 'usi_e9d64db5b986d062a342793013f682e8');
    }

    /**
     * 获取资源分享领域服务.
     */
    private function getResourceShareDomainService(): ResourceShareDomainService
    {
        return ApplicationContext::getContainer()
            ->get(ResourceShareDomainService::class);
    }

    /**
     * 获取项目成员领域服务.
     */
    private function getProjectMemberDomainService(): ProjectMemberDomainService
    {
        return ApplicationContext::getContainer()
            ->get(ProjectMemberDomainService::class);
    }
}
