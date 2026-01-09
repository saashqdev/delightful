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
 * project邀请linkAPItest
 */
class ProjectInvitationLinkApiTest extends AbstractApiTest
{
    private const BASE_URI = '/api/v1/be-agent/projects';

    private const INVITATION_BASE_URI = '/api/v1/be-agent/invitation';

    private string $projectId = '816065897791012866';

    // testproceduremiddlegenerate的邀请linkinfo
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
     * test邀请link完整process.
     */
    public function testInvitationLinkCompleteFlow(): void
    {
        $projectId = $this->projectId;

        // 0. cleanuptestdata - ensuretest2usernot是projectmember
        $this->cleanupTestData($projectId);

        // 1. project所have者start邀请link
        $this->switchUserTest1();
        $this->assertToggleInvitationLinkOn($projectId);

        // 2. get邀请linkinfo
        $linkInfo = $this->getInvitationLink($projectId);
        $this->invitationToken = $linkInfo['data']['token'];

        // 3. setting密码保护
        $this->assertSetPasswordProtection($projectId, true);

        // 4. outside部userpassTokenget邀请info
        $this->switchUserTest2();
        $invitationInfo = $this->getInvitationByToken($this->invitationToken);
        $this->assertTrue($invitationInfo['data']['requires_password']);

        // 5. outside部user尝试加入project（密码error）
        $this->joinProjectWithWrongPassword($this->invitationToken);

        // 6. project所have者reset密码
        $this->switchUserTest1();
        $passwordInfo = $this->resetInvitationPassword($projectId);
        $this->invitationPassword = $passwordInfo['data']['password'];

        // 7. outside部userusecorrect密码加入project
        $this->switchUserTest2();
        $this->joinProjectSuccess($this->invitationToken, $this->invitationPassword);

        // 8. validateuser已become为projectmember（againtime加入shouldfail）
        $this->joinProjectAlreadyMember($this->invitationToken, $this->invitationPassword);

        // 9. project所have者close邀请link
        $this->switchUserTest1();
        $this->assertToggleInvitationLinkOff($projectId);

        // 10. outside部user尝试access已close的邀请link
        $this->switchUserTest2();
        $this->getInvitationByTokenDisabled($this->invitationToken);
    }

    /**
     * test邀请linkpermission控制.
     */
    public function testInvitationLinkPermissions(): void
    {
        $projectId = $this->projectId;

        // 1. nonprojectmember尝试管理邀请link（shouldfail）
        $this->switchUserTest2();
        $this->getInvitationLink($projectId, 51202); // permissionnot足

        // 2. project所have者can管理邀请link
        $this->switchUserTest1();
        $this->getInvitationLink($projectId, 1000); // success
    }

    /**
     * testpermissionlevel别管理.
     */
    public function testPermissionLevelManagement(): void
    {
        $projectId = $this->projectId;

        $this->switchUserTest1();

        // 1. start邀请link
        $this->toggleInvitationLink($projectId, true);

        // 2. testmodifypermissionlevel别为管理permission
        $this->updateInvitationPermission($projectId, 'manage');

        // 3. testmodifypermissionlevel别为editpermission
        $this->updateInvitationPermission($projectId, 'editor');

        // 4. testmodifypermissionlevel别为viewpermission
        $this->updateInvitationPermission($projectId, 'viewer');
    }

    /**
     * get邀请linkinfo.
     */
    public function getInvitationLink(string $projectId, int $expectedCode = 1000): array
    {
        $response = $this->client->get(
            self::BASE_URI . "/{$projectId}/invitation-links",
            [],
            $this->getCommonHeaders()
        );

        $this->assertNotNull($response, 'responsenotshould为null');
        $this->assertEquals($expectedCode, $response['code'], $response['message'] ?? '');

        return $response;
    }

    /**
     * start/close邀请link.
     */
    public function toggleInvitationLink(string $projectId, bool $enabled, int $expectedCode = 1000): array
    {
        $response = $this->client->put(
            self::BASE_URI . "/{$projectId}/invitation-links/toggle",
            ['enabled' => $enabled],
            $this->getCommonHeaders()
        );

        $this->assertNotNull($response, 'responsenotshould为null');
        $this->assertEquals($expectedCode, $response['code'], $response['message'] ?? '');

        return $response;
    }

    /**
     * reset邀请link.
     */
    public function resetInvitationLink(string $projectId, int $expectedCode = 1000): array
    {
        $response = $this->client->post(
            self::BASE_URI . "/{$projectId}/invitation-links/reset",
            [],
            $this->getCommonHeaders()
        );

        $this->assertNotNull($response, 'responsenotshould为null');
        $this->assertEquals($expectedCode, $response['code'], $response['message'] ?? '');

        return $response;
    }

    /**
     * setting密码保护.
     */
    public function setInvitationPassword(string $projectId, bool $enabled, int $expectedCode = 1000): array
    {
        $response = $this->client->post(
            self::BASE_URI . "/{$projectId}/invitation-links/password",
            ['enabled' => $enabled],
            $this->getCommonHeaders()
        );

        $this->assertNotNull($response, 'responsenotshould为null');
        $this->assertEquals($expectedCode, $response['code'], $response['message'] ?? '');

        return $response;
    }

    /**
     * reset密码
     */
    public function resetInvitationPassword(string $projectId, int $expectedCode = 1000): array
    {
        $response = $this->client->post(
            self::BASE_URI . "/{$projectId}/invitation-links/reset-password",
            [],
            $this->getCommonHeaders()
        );

        $this->assertNotNull($response, 'responsenotshould为null');
        $this->assertEquals($expectedCode, $response['code'], $response['message'] ?? '');

        return $response;
    }

    /**
     * modifypermissionlevel别.
     */
    public function updateInvitationPermission(string $projectId, string $permission, int $expectedCode = 1000): array
    {
        $response = $this->client->put(
            self::BASE_URI . "/{$projectId}/invitation-links/permission",
            ['default_join_permission' => $permission],
            $this->getCommonHeaders()
        );

        $this->assertNotNull($response, 'responsenotshould为null');
        $this->assertEquals($expectedCode, $response['code'], $response['message'] ?? '');

        if ($expectedCode === 1000) {
            $this->assertEquals($permission, $response['data']['default_join_permission']);
        }

        return $response;
    }

    /**
     * passTokenget邀请info.
     */
    public function getInvitationByToken(string $token, int $expectedCode = 1000): array
    {
        $response = $this->client->get(
            self::INVITATION_BASE_URI . "/links/{$token}",
            [],
            $this->getCommonHeaders()
        );

        $this->assertNotNull($response, 'responsenotshould为null');
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
     * getdisabled的邀请link（shouldfail）.
     */
    public function getInvitationByTokenDisabled(string $token): void
    {
        $response = $this->getInvitationByToken($token, 51222); // 邀请linkdisabled
    }

    /**
     * 加入project（密码error）.
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
     * success加入project.
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
     * 尝试重复加入project（shouldfail）.
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

        $this->assertEquals(51225, $response['code']); // 已经是projectmember
    }

    // =================== side界conditiontest ===================

    /**
     * testinvalidTokenaccess.
     */
    public function testInvalidTokenAccess(): void
    {
        $this->switchUserTest2();
        $invalidToken = 'invalid_token_123456789';

        $response = $this->getInvitationByToken($invalidToken, 51217); // Tokeninvalid
    }

    /**
     * testpermissionside界.
     */
    public function testPermissionBoundaries(): void
    {
        $projectId = $this->projectId;
        $this->switchUserTest1();

        // testinvalidpermissionlevel别
        $response = $this->client->put(
            self::BASE_URI . "/{$projectId}/invitation-links/permission",
            ['default_join_permission' => 'invalid_permission'],
            $this->getCommonHeaders()
        );

        $this->assertNotNull($response, 'responsenotshould为null');
        $this->assertEquals(51215, $response['code']); // invalidpermissionlevel别
    }

    /**
     * test并hair操作.
     */
    public function testConcurrentOperations(): void
    {
        $projectId = $this->projectId;
        $this->switchUserTest1();

        // cleanuptestdata
        $this->getResourceShareDomainService()->deleteShareByResource($projectId, ResourceType::ProjectInvitation->value);

        // 连续快speedstart/close邀请link
        $this->toggleInvitationLink($projectId, true);
        $this->toggleInvitationLink($projectId, false);
        $this->toggleInvitationLink($projectId, true);

        // validatefinalstatus
        $response = $this->getInvitationLink($projectId);
        $this->assertEquals(1000, $response['code']);

        // validatelink是enablestatus（compatiblenumber和booleanvalue）
        $isEnabled = $response['data']['is_enabled'];
        $this->assertTrue($isEnabled === true || $isEnabled === 1, '邀请linkshould是enablestatus');
    }

    /**
     * test密码securityproperty.
     */
    public function testPasswordSecurity(): void
    {
        $projectId = $this->projectId;
        $this->switchUserTest1();

        // 1. start邀请link
        $this->toggleInvitationLink($projectId, true);

        // 2. 多timesetting密码保护，validate密码generate
        $password1 = $this->setInvitationPassword($projectId, true);
        $password2 = $this->resetInvitationPassword($projectId);
        $password3 = $this->resetInvitationPassword($projectId);

        // validateeachtimegenerate的密码alldifferent
        $this->assertNotEquals($password1['data']['password'] ?? '', $password2['data']['password']);
        $this->assertNotEquals($password2['data']['password'], $password3['data']['password']);

        // validate密码length和format
        $password = $password3['data']['password'];
        $this->assertEquals(5, strlen($password)); // 密码lengthshould是5位
        $this->assertMatchesRegularExpression('/^\d{5}$/', $password); // 只contain5位number
    }

    /**
     * test密码保护switchfeature - closebackagainstart密码notwillmore改.
     */
    public function testPasswordTogglePreservation(): void
    {
        $projectId = $this->projectId;
        $this->switchUserTest1();

        // 0. cleanuptestdata
        $this->cleanupTestData($projectId);

        // 1. start邀请link
        $this->toggleInvitationLink($projectId, true);

        // 2. setting密码保护
        $initialPasswordResponse = $this->setInvitationPassword($projectId, true);
        $this->assertEquals(1000, $initialPasswordResponse['code']);

        $originalPassword = $initialPasswordResponse['data']['password'];
        $this->assertNotEmpty($originalPassword);
        $this->assertEquals(5, strlen($originalPassword));

        // 3. close密码保护
        $disableResponse = $this->setInvitationPassword($projectId, false);
        $this->assertEquals(1000, $disableResponse['code']);

        // 4. validateclosestatusdownaccesslinknotneed密码
        $linkResponse = $this->getInvitationLink($projectId);
        $this->assertEquals(1000, $linkResponse['code']);
        $token = $linkResponse['data']['token'];

        $linkInfo = $this->getInvitationByToken($token);
        $this->assertEquals(1000, $linkInfo['code']);
        $this->assertFalse($linkInfo['data']['requires_password']);

        // 5. 重新start密码保护
        $enableResponse = $this->setInvitationPassword($projectId, true);
        $this->assertEquals(1000, $enableResponse['code']);

        // 6. validate密码保持not变
        $restoredPassword = $enableResponse['data']['password'];
        $this->assertEquals($originalPassword, $restoredPassword, '重新start密码保护back，密码should保持not变');

        // 7. validatestartstatusdownaccesslinkneed密码
        $linkInfo = $this->getInvitationByToken($token);
        $this->assertEquals(1000, $linkInfo['code']);
        $this->assertTrue($linkInfo['data']['requires_password']);

        // 8. validateuse原密码cansuccess加入project
        $this->switchUserTest2();
        $joinResult = $this->joinProjectSuccess($token, $originalPassword);
        $this->assertEquals(1000, $joinResult['code']);
        $this->assertEquals('viewer', $joinResult['data']['user_role']);
    }

    /**
     * test密码modifyfeature.
     */
    public function testChangePassword(): void
    {
        $projectId = $this->projectId;
        $this->switchUserTest1();

        // 0. cleanuptestdata并reset邀请link
        $this->cleanupTestData($projectId);

        // pass领域servicedelete现have的邀请link
        $this->getResourceShareDomainService()->deleteShareByResource($projectId, ResourceType::ProjectInvitation->value);

        // 1. start邀请link
        $this->toggleInvitationLink($projectId, true);

        // 2. settinginitial密码保护
        $initialPasswordResponse = $this->setInvitationPassword($projectId, true);
        $this->assertEquals(1000, $initialPasswordResponse['code']);

        $originalPassword = $initialPasswordResponse['data']['password'];
        $this->assertEquals(5, strlen($originalPassword)); // validate密码length为5位
        $this->assertMatchesRegularExpression('/^\d{5}$/', $originalPassword); // validate是5位number

        // 3. modify密码为customize密码
        $customPassword = 'mypass123';
        $changePasswordResponse = $this->changeInvitationPassword($projectId, $customPassword);
        $this->assertEquals(1000, $changePasswordResponse['code']);
        $this->assertEquals($customPassword, $changePasswordResponse['data']['password']);

        // 4. validate原密码not能use
        $linkResponse = $this->getInvitationLink($projectId);
        $token = $linkResponse['data']['token'];

        $this->switchUserTest2();
        // use原密码shouldfail
        $data = ['token' => $token, 'password' => $originalPassword];
        $response = $this->client->post(
            self::INVITATION_BASE_URI . '/join',
            $data,
            $this->getCommonHeaders()
        );
        $this->assertEquals(51220, $response['code'], 'error密码shouldreturn51220error码');

        // 5. validate新密码cannormaluse
        $joinResult = $this->joinProjectSuccess($token, $customPassword);
        $this->assertEquals(1000, $joinResult['code']);
        $this->assertEquals('viewer', $joinResult['data']['user_role']);

        // 6. cleanuptestdata
        $this->cleanupTestData($projectId);

        // 7. testinvalid密码format（nullstring和超长密码）
        $this->switchUserTest1();
        $this->toggleInvitationLink($projectId, true);

        // testnull密码
        $response = $this->changeInvitationPassword($projectId, '', 51220);
        $this->assertEquals(51220, $response['code']);

        // test超长密码（19位）
        $response = $this->changeInvitationPassword($projectId, str_repeat('1', 19), 51220);
        $this->assertEquals(51220, $response['code']);

        // 8. testvalid的eachtype密码format
        $validPasswords = ['123', '12345', 'abcde', '12a34', str_repeat('x', 18)];
        foreach ($validPasswords as $validPassword) {
            $response = $this->changeInvitationPassword($projectId, $validPassword, 1000);
            $this->assertEquals(1000, $response['code']);
            $this->assertEquals($validPassword, $response['data']['password']);
        }
    }

    /**
     * test邀请linkuserstatus和create者info.
     */
    public function testInvitationLinkUserStatusAndCreatorInfo(): void
    {
        $projectId = $this->projectId;

        // 0. ensure切换totest1user并cleanuptestdata
        $this->switchUserTest1();
        $this->cleanupTestData($projectId);
        $this->getResourceShareDomainService()->deleteShareByResource($projectId, ResourceType::ProjectInvitation->value, '', true);

        // 1. projectcreate者（test1）start邀请link
        $this->toggleInvitationLink($projectId, true);

        // get邀请linkinfo
        $linkResponse = $this->getInvitationLink($projectId);
        $token = $linkResponse['data']['token'];

        // 2. testcreate者access邀请link - should show has_joined = true
        $this->switchUserTest1();
        $invitationInfo = $this->getInvitationByToken($token);

        // check基本response结构
        $this->assertEquals(1000, $invitationInfo['code'], 'get邀请infoshouldsuccess');
        $this->assertIsArray($invitationInfo['data'], 'responsedatashould是array');

        // checknewfieldwhether存in
        $this->assertArrayHasKey('has_joined', $invitationInfo['data'], 'responseshouldcontainhas_joinedfield');
        $this->assertArrayHasKey('creator_name', $invitationInfo['data'], 'responseshouldcontaincreator_namefield');
        $this->assertArrayHasKey('creator_avatar', $invitationInfo['data'], 'responseshouldcontaincreator_avatarfield');
        $this->assertArrayHasKey('creator_id', $invitationInfo['data'], 'responseshouldcontaincreator_idfield');

        // checkfieldvalue
        $this->assertTrue($invitationInfo['data']['has_joined'], 'create者shoulddisplay已加入project');
        $this->assertNotEmpty($invitationInfo['data']['creator_id'], 'creator_idnotshould为null');

        // validatefieldtype
        $this->assertIsBool($invitationInfo['data']['has_joined'], 'has_joinedshould是booleantype');
        $this->assertIsString($invitationInfo['data']['creator_name'], 'creator_nameshould是stringtype');
        $this->assertIsString($invitationInfo['data']['creator_avatar'], 'creator_avatarshould是stringtype');

        // 3. test未加入useraccess邀请link - should show has_joined = false
        $this->switchUserTest2();
        $invitationInfo = $this->getInvitationByToken($token);

        // check基本response
        $this->assertEquals(1000, $invitationInfo['code'], '未加入userget邀请infoshouldsuccess');
        $this->assertArrayHasKey('has_joined', $invitationInfo['data'], 'responseshouldcontainhas_joinedfield');
        $this->assertFalse($invitationInfo['data']['has_joined'], '未加入usershoulddisplay未加入project');

        // validatecreate者info依然存in（not管谁access，create者infoallshoulddisplay）
        $this->assertArrayHasKey('creator_name', $invitationInfo['data'], 'responseshouldcontaincreator_namefield');
        $this->assertArrayHasKey('creator_avatar', $invitationInfo['data'], 'responseshouldcontaincreator_avatarfield');

        // 4. test2user加入project - need提供密码
        $password = $linkResponse['data']['password'] ?? null;
        $joinResult = $this->joinProjectSuccess($token, $password);
        $this->assertEquals(1000, $joinResult['code']);

        // 5. test已加入memberaccess邀请link - should show has_joined = true
        $invitationInfo = $this->getInvitationByToken($token);

        $this->assertEquals(1000, $invitationInfo['code'], '已加入memberget邀请infoshouldsuccess');
        $this->assertArrayHasKey('has_joined', $invitationInfo['data'], 'responseshouldcontainhas_joinedfield');
        $this->assertTrue($invitationInfo['data']['has_joined'], '已加入membershoulddisplay已加入project');

        // 6. validateresponsedata完整property
        $data = $invitationInfo['data'];
        $requiredFields = [
            'project_id', 'project_name', 'project_description',
            'organization_code', 'creator_id', 'creator_name', 'creator_avatar',
            'default_join_permission', 'requires_password', 'token', 'has_joined',
        ];

        foreach ($requiredFields as $field) {
            $this->assertArrayHasKey($field, $data, "responsedata应containfield: {$field}");
        }

        // 7. cleanuptestdata并切换回test1user
        $this->switchUserTest1();
        $this->cleanupTestData($projectId);
        $this->getResourceShareDomainService()->deleteShareByResource($projectId, ResourceType::ProjectInvitation->value);
    }

    /**
     * start邀请link (私have辅助method).
     */
    private function assertToggleInvitationLinkOn(string $projectId): void
    {
        $response = $this->toggleInvitationLink($projectId, true);

        $this->assertEquals(1000, $response['code']);

        // validatelink是enablestatus（compatiblenumber和booleanvalue）
        $isEnabled = $response['data']['is_enabled'];
        $this->assertTrue($isEnabled === true || $isEnabled === 1, '邀请linkshould是enablestatus');

        $this->assertNotEmpty($response['data']['token']);
        $this->assertEquals('viewer', $response['data']['default_join_permission']);
    }

    /**
     * close邀请link (私have辅助method).
     */
    private function assertToggleInvitationLinkOff(string $projectId): void
    {
        $response = $this->toggleInvitationLink($projectId, false);

        $this->assertEquals(1000, $response['code']);

        // validatelink是disablestatus（compatiblenumber和booleanvalue）
        $isEnabled = $response['data']['is_enabled'];
        $this->assertTrue($isEnabled === false || $isEnabled === 0, '邀请linkshould是disablestatus');
    }

    /**
     * setting密码保护 (私have辅助method).
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
     * modify邀请link密码 (私have辅助method).
     */
    private function changeInvitationPassword(string $projectId, string $password, int $expectedCode = 1000): array
    {
        $response = $this->client->put(
            self::BASE_URI . "/{$projectId}/invitation-links/change-password",
            ['password' => $password],
            $this->getCommonHeaders()
        );

        $this->assertNotNull($response, 'responsenotshould为null');
        $this->assertEquals($expectedCode, $response['code'], $response['message'] ?? '');

        return $response;
    }

    /**
     * cleanuptestdata.
     */
    private function cleanupTestData(string $projectId): void
    {
        // pass领域servicedeletetest2user的projectmember关系（if存in）
        $this->getProjectMemberDomainService()->removeMemberByUser((int) $projectId, 'usi_e9d64db5b986d062a342793013f682e8');
    }

    /**
     * getresourceshare领域service.
     */
    private function getResourceShareDomainService(): ResourceShareDomainService
    {
        return ApplicationContext::getContainer()
            ->get(ResourceShareDomainService::class);
    }

    /**
     * getprojectmember领域service.
     */
    private function getProjectMemberDomainService(): ProjectMemberDomainService
    {
        return ApplicationContext::getContainer()
            ->get(ProjectMemberDomainService::class);
    }
}
