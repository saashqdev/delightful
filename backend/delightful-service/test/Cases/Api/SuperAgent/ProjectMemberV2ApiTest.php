<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace HyperfTest\Cases\Api\BeAgent;

use Delightful\BeDelightful\Domain\BeAgent\Entity\ProjectMemberEntity;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\MemberRole;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\MemberStatus;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\MemberType;
use Delightful\BeDelightful\Domain\BeAgent\Service\ProjectDomainService;
use Delightful\BeDelightful\Domain\BeAgent\Service\ProjectMemberDomainService;
use Exception;
use Mockery;

/**
 * @internal
 * Project team invitation API test
 */
class ProjectMemberV2ApiTest extends AbstractApiTest
{
    private const BASE_URI = '/api/v1/be-agent/projects';

    private string $projectId = '816065897791012866';

    // Test user IDs and department IDs
    private string $testUserId1 = 'usi_7839078ce6af2d3249b82e7aaed643b8';

    private string $testUserId2 = 'usi_e9d64db5b986d062a342793013f682e8';

    private string $testDepartmentId1 = '727236421093691395';

    private string $testDepartmentId2 = '727236421089497089';

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure clean test environment, reset collaboration status to disabled
        $this->switchUserTest1();
        $this->disableCollaboration($this->projectId);
        // Clean up project member data to avoid unique key conflicts
        $this->cleanupProjectMembers($this->projectId);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test complete team invitation workflow.
     */
    public function testTeamInvitationCompleteFlow(): void
    {
        $projectId = $this->projectId;

        // 0. Clean up test data, ensure clean environment
        $this->switchUserTest1();
        $this->cleanupProjectMembers($projectId);

        // 1. Project creator enables collaboration
        $this->enableCollaboration($projectId);

        // 2. Test getting collaboration settings
        $this->getCollaborationSettings($projectId);

        // 3. Add team members
        $this->addTeamMembers($projectId);

        // 4. Verify members added
        $this->verifyMembersAdded($projectId);

        // 5. Batch update member permissions
        $this->batchUpdateMemberPermissions($projectId);

        // 6. Verify permissions updated
        $this->verifyPermissionsUpdated($projectId);

        // 7. Batch delete some members
        $this->batchDeleteMembers($projectId);

        // 8. Verify members deleted
        $this->verifyMembersDeleted($projectId);

        // 9. Disable collaboration
        $this->disableCollaboration($projectId);
    }

    /**
     * Test permission control - only managers and owners can add members.
     */
    public function testCreateMembersPermissionControl(): void
    {
        $projectId = $this->projectId;

        // 1. Project creator enables collaboration
        $this->switchUserTest1();
        $this->enableCollaboration($projectId);

        // 2. Non-project member tries to add members - should fail
        $this->switchUserTest2();
        $this->addTeamMembers($projectId, 51202); // No permission error

        // 3. projectcreate者添加member - shouldsuccess
        $this->switchUserTest1();
        $this->addTeamMembers($projectId);

        // 4. 现在test2user成为member，但permission不足 - 添加membershouldfail
        $this->switchUserTest2();
        $this->addTeamMembers($projectId, 51202); // 仍然无permission，因为不是管理者

        // 5. 给test2user管理permission
        $this->switchUserTest1();
        $this->updateMemberToManager($projectId, $this->testUserId2);

        // 6. 现在test2usercan添加member
        $this->switchUserTest2();
        $this->addMoreTeamMembers($projectId);
    }

    /**
     * test协作setting管理.
     */
    public function testCollaborationSettings(): void
    {
        $projectId = $this->projectId;

        $this->switchUserTest1();

        // 1. testget协作setting - defaultclosestatus
        $response = $this->getCollaborationSettings($projectId);
        $this->assertFalse($response['data']['is_collaboration_enabled']);
        $this->assertEquals(true, in_array($response['data']['default_join_permission'], ['viewer', 'editor']));

        // 2. 开启协作feature
        $this->enableCollaboration($projectId);

        // 3. validate协作已开启
        $response = $this->getCollaborationSettings($projectId);
        $this->assertTrue($response['data']['is_collaboration_enabled']);

        // 4. close协作feature
        $this->disableCollaboration($projectId);

        // 5. validate协作已close
        $response = $this->getCollaborationSettings($projectId);
        $this->assertFalse($response['data']['is_collaboration_enabled']);
    }

    /**
     * test批量操作permission控制.
     */
    public function testBatchOperationsPermissionControl(): void
    {
        $projectId = $this->projectId;

        // 1. 准备test环境
        $this->switchUserTest1();
        $this->enableCollaboration($projectId);
        $this->addTeamMembers($projectId);

        // 2. 非管理者尝试批量updatepermission - shouldfail
        $this->switchUserTest2();
        $this->batchUpdateMemberPermissions($projectId, 51202);

        // 3. 非管理者尝试批量deletemember - shouldfail
        $this->batchDeleteMembers($projectId, 51202);

        // 4. 管理者can进行批量操作
        $this->switchUserTest1();
        $this->batchUpdateMemberPermissions($projectId);
        $this->batchDeleteMembers($projectId);
    }

    /**
     * testorganization边界控制.
     */
    public function testOrganizationBoundaryControl(): void
    {
        $projectId = $this->projectId;

        $this->switchUserTest1();
        $this->enableCollaboration($projectId);

        // 尝试添加其他organization的user - shouldfail
        $requestData = [
            'members' => [
                [
                    'target_type' => 'User',
                    'target_id' => 'usi_invalid_cross_org_user',
                    'role' => 'editor',
                ],
            ],
        ];

        $response = $this->post(
            self::BASE_URI . "/{$projectId}/members",
            $requestData,
            $this->getCommonHeaders()
        );

        // shouldreturnmember不存在error
        $this->assertNotEquals(1000, $response['code']);
    }

    /**
     * test边界情况.
     */
    public function testEdgeCases(): void
    {
        $projectId = $this->projectId;

        $this->switchUserTest1();
        $this->enableCollaboration($projectId);

        // 1. test添加nullmember列表
        $this->addEmptyMembersList($projectId, 5003);

        // 2. test重复添加samemember
        $this->addTeamMembers($projectId);
        //        $this->addTeamMembers($projectId); // 重复添加

        // 3. testinvalid的permission级别
        $this->addMembersWithInvalidPermission($projectId, 5003);

        // 4. test不能delete自己
        $this->switchUserTest2();
        $this->cannotDeleteSelf($projectId);

        $this->switchUserTest1();

        // 5. test协作close时不能添加member
        $this->disableCollaboration($projectId);
        $this->addTeamMembers($projectId, 51202); // 协作已closeerror
    }

    /**
     * test多语言errormessage.
     */
    public function testMultiLanguageErrorMessages(): void
    {
        $projectId = $this->projectId;

        // 1. test中文errormessage
        $this->switchUserTest2(); // 无permissionuser
        $response = $this->addTeamMembers($projectId, 51202);
        $this->assertStringContainsString('permission', $response['message']);

        // 2. test协作未开启error
        $this->switchUserTest1();
        $response = $this->addTeamMembers($projectId, 51202);
        $this->assertStringContainsString('协作', $response['message']);
    }

    // ========== 辅助testmethod ==========

    /**
     * 开启project协作.
     */
    public function enableCollaboration(string $projectId, int $expectedCode = 1000): array
    {
        $requestData = ['is_collaboration_enabled' => true];

        $response = $this->put(
            self::BASE_URI . "/{$projectId}",
            $requestData,
            $this->getCommonHeaders()
        );

        $this->assertNotNull($response, 'response不should为null');
        $this->assertEquals($expectedCode, $response['code'], $response['message'] ?? '');

        if ($expectedCode === 1000) {
            $this->assertEquals('ok', $response['message']);
            $this->assertIsArray($response['data']);
            $this->assertEquals('is_collaboration_enabled', $response['data']['is_collaboration_enabled']);
        }

        return $response;
    }

    /**
     * closeproject协作.
     */
    public function disableCollaboration(string $projectId, int $expectedCode = 1000): array
    {
        $requestData = ['is_collaboration_enabled' => false];

        $response = $this->put(
            self::BASE_URI . "/{$projectId}",
            $requestData,
            $this->getCommonHeaders()
        );

        $this->assertNotNull($response, 'response不should为null');
        $this->assertEquals($expectedCode, $response['code'], $response['message'] ?? '');

        return $response;
    }

    /**
     * get协作setting.
     */
    public function getCollaborationSettings(string $projectId, int $expectedCode = 1000): array
    {
        $response = $this->get(
            self::BASE_URI . "/{$projectId}",
            [],
            $this->getCommonHeaders()
        );

        $this->assertNotNull($response, 'response不should为null');
        $this->assertEquals($expectedCode, $response['code'], $response['message'] ?? '');

        if ($expectedCode === 1000) {
            $this->assertEquals('ok', $response['message']);
            $this->assertIsBool($response['data']['is_collaboration_enabled']);
            $this->assertIsString($response['data']['default_join_permission']);
        }

        return $response;
    }

    /**
     * 添加teammember.
     */
    public function addTeamMembers(string $projectId, int $expectedCode = 1000): array
    {
        $requestData = [
            'members' => [
                [
                    'target_type' => 'User',
                    'target_id' => $this->testUserId2,
                    'role' => 'editor',
                ],
                [
                    'target_type' => 'Department',
                    'target_id' => $this->testDepartmentId1,
                    'role' => 'viewer',
                ],
            ],
        ];

        $response = $this->post(
            self::BASE_URI . "/{$projectId}/members",
            $requestData,
            $this->getCommonHeaders()
        );

        $this->assertNotNull($response, 'response不should为null');
        $this->assertEquals($expectedCode, $response['code'], $response['message'] ?? '');

        if ($expectedCode === 1000) {
            $this->assertEquals('ok', $response['message']);
            $this->assertArrayHasKey('members', $response['data']);
        }

        return $response;
    }

    /**
     * 添加更多teammember（test管理者permission）.
     */
    public function addMoreTeamMembers(string $projectId, int $expectedCode = 1000): array
    {
        $requestData = [
            'members' => [
                [
                    'target_type' => 'Department',
                    'target_id' => $this->testDepartmentId2,
                    'role' => 'editor',
                ],
            ],
        ];

        $response = $this->post(
            self::BASE_URI . "/{$projectId}/members",
            $requestData,
            $this->getCommonHeaders()
        );

        $this->assertNotNull($response, 'response不should为null');
        $this->assertEquals($expectedCode, $response['code'], $response['message'] ?? '');

        return $response;
    }

    /**
     * 添加nullmember列表.
     */
    public function addEmptyMembersList(string $projectId, int $expectedCode = 1000): array
    {
        $requestData = ['members' => []];

        $response = $this->post(
            self::BASE_URI . "/{$projectId}/members",
            $requestData,
            $this->getCommonHeaders()
        );

        $this->assertNotNull($response, 'response不should为null');
        $this->assertEquals($expectedCode, $response['code'], $response['message'] ?? '');

        return $response;
    }

    /**
     * 添加invalidpermission的member.
     */
    public function addMembersWithInvalidPermission(string $projectId, int $expectedCode = 51221): array
    {
        $requestData = [
            'members' => [
                [
                    'target_type' => 'User',
                    'target_id' => $this->testUserId2,
                    'role' => 'invalid_permission',
                ],
            ],
        ];

        $response = $this->post(
            self::BASE_URI . "/{$projectId}/members",
            $requestData,
            $this->getCommonHeaders()
        );

        $this->assertNotNull($response, 'response不should为null');
        $this->assertEquals($expectedCode, $response['code'], $response['message'] ?? '');

        return $response;
    }

    /**
     * 批量updatememberpermission.
     */
    public function batchUpdateMemberPermissions(string $projectId, int $expectedCode = 1000): array
    {
        $requestData = [
            'members' => [
                [
                    'target_type' => 'User',
                    'target_id' => $this->testUserId2,
                    'role' => 'manage',
                ],
            ],
        ];

        $response = $this->put(
            self::BASE_URI . "/{$projectId}/members/roles",
            $requestData,
            $this->getCommonHeaders()
        );

        $this->assertNotNull($response, 'response不should为null');
        $this->assertEquals($expectedCode, $response['code'], $response['message'] ?? '');

        if ($expectedCode === 1000) {
            $this->assertEquals('ok', $response['message']);
            $this->assertIsArray($response['data']);
            $this->assertEmpty($response['data']);
        }

        return $response;
    }

    /**
     * 批量deletemember.
     */
    public function batchDeleteMembers(string $projectId, int $expectedCode = 1000): array
    {
        $requestData = [
            'members' => [
                [
                    'target_type' => 'User',
                    'target_id' => $this->testUserId2,
                ],
            ],
        ];

        $response = $this->delete(
            self::BASE_URI . "/{$projectId}/members",
            $requestData,
            $this->getCommonHeaders()
        );

        $this->assertNotNull($response, 'response不should为null');
        $this->assertEquals($expectedCode, $response['code'], $response['message'] ?? '');

        if ($expectedCode === 1000) {
            $this->assertEquals('ok', $response['message']);
            $this->assertIsArray($response['data']);
            $this->assertEmpty($response['data']);
        }

        return $response;
    }

    /**
     * updatemember为管理者.
     */
    public function updateMemberToManager(string $projectId, string $userId): array
    {
        $requestData = [
            'members' => [
                [
                    'target_type' => 'User',
                    'target_id' => $userId,
                    'role' => 'manage',
                ],
            ],
        ];

        $response = $this->put(
            self::BASE_URI . "/{$projectId}/members/roles",
            $requestData,
            $this->getCommonHeaders()
        );

        $this->assertEquals(1000, $response['code'], $response['message'] ?? '');
        return $response;
    }

    /**
     * test不能delete自己.
     */
    public function cannotDeleteSelf(string $projectId): void
    {
        // 先添加currentuser为member
        //        $this->addTeamMembers($projectId);

        // 尝试delete自己
        $currentUserId = $this->testUserId2; // test2user
        $requestData = [
            'members' => [
                [
                    'target_type' => 'User',
                    'target_id' => $currentUserId,
                ],
            ],
        ];

        $response = $this->delete(
            self::BASE_URI . "/{$projectId}/members",
            $requestData,
            $this->getCommonHeaders()
        );

        // shouldreturn不能delete自己的error
        $this->assertNotEquals(1000, $response['code']);
    }

    public function testGetProjectInfo()
    {
        $this->switchUserTest1();

        $response = $this->get(
            self::BASE_URI . "/{$this->projectId}",
            [],
            $this->getCommonHeaders()
        );

        $this->assertEquals(1000, $response['code']);
        $this->assertArrayHasKey('is_collaboration_enabled', $response['data']);
        $this->assertArrayHasKey('default_join_permission', $response['data']);
    }

    /**
     * validatemember已添加.
     */
    public function verifyMembersAdded(string $projectId): void
    {
        $response = $this->get(
            self::BASE_URI . "/{$projectId}/members",
            [],
            $this->getCommonHeaders()
        );

        $this->assertEquals(1000, $response['code']);
        $this->assertGreaterThan(0, count($response['data']['members']));

        // validate添加的member存在
        $memberIds = array_column($response['data']['members'], 'user_id');
        $departmentIds = array_column($response['data']['members'], 'department_id');

        $this->assertContains($this->testUserId2, array_filter($memberIds));
        $this->assertContains($this->testDepartmentId1, array_filter($departmentIds));
    }

    /**
     * validatepermission已update.
     */
    public function verifyPermissionsUpdated(string $projectId): void
    {
        $response = $this->get(
            self::BASE_URI . "/{$projectId}/members",
            [],
            $this->getCommonHeaders()
        );

        $this->assertEquals(1000, $response['code']);

        // 查找指定user的permission
        $members = $response['data']['members'];
        foreach ($members as $member) {
            if (isset($member['user_id']) && $member['user_id'] === $this->testUserId2) {
                $this->assertEquals('manage', $member['role']);
                break;
            }
        }
    }

    /**
     * validatemember已delete.
     */
    public function verifyMembersDeleted(string $projectId): void
    {
        $response = $this->get(
            self::BASE_URI . "/{$projectId}/members",
            [],
            $this->getCommonHeaders()
        );

        $this->assertEquals(1000, $response['code']);

        // validatedelete的member不存在
        $memberIds = array_column($response['data']['members'], 'user_id');
        $this->assertNotContains($this->testUserId2, array_filter($memberIds));
    }

    /**
     * 清理projectmemberdata（直接databasedelete）.
     */
    private function cleanupProjectMembers(string $projectId): void
    {
        try {
            $projectDomainService = di(ProjectDomainService::class);
            $project = $projectDomainService->getProjectNotUserId((int) $projectId);

            $projectMemberDomainService = di(ProjectMemberDomainService::class);
            $projectMemberDomainService->deleteByProjectId((int) $projectId);

            $memberEntity = new ProjectMemberEntity();
            $memberEntity->setProjectId((int) $projectId);
            $memberEntity->setTargetType(MemberType::USER);
            $memberEntity->setTargetId($project->getCreatedUid());
            $memberEntity->setRole(MemberRole::OWNER);
            $memberEntity->setOrganizationCode($this->getOrganizationCode());
            $memberEntity->setInvitedBy($project->getCreatedUid());
            $memberEntity->setStatus(MemberStatus::ACTIVE);

            $projectMemberDomainService->addInternalMembers([$memberEntity], $this->getOrganizationCode());
            echo "清理projectmemberdatacomplete: {$projectId}\n";
        } catch (Exception $e) {
            echo '清理projectmemberdatafail: ' . $e->getMessage() . "\n";
        }
    }
}
