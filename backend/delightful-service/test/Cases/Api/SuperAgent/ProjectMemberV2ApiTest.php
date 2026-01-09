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

        // 3. projectcreate者addmember - shouldsuccess
        $this->switchUserTest1();
        $this->addTeamMembers($projectId);

        // 4. 现intest2userbecomeformember,butpermissionnot足 - addmembershouldfail
        $this->switchUserTest2();
        $this->addTeamMembers($projectId, 51202); // 仍然nopermission,因fornotismanage者

        // 5. givetest2usermanagepermission
        $this->switchUserTest1();
        $this->updateMemberToManager($projectId, $this->testUserId2);

        // 6. 现intest2usercanaddmember
        $this->switchUserTest2();
        $this->addMoreTeamMembers($projectId);
    }

    /**
     * test协assettingmanage.
     */
    public function testCollaborationSettings(): void
    {
        $projectId = $this->projectId;

        $this->switchUserTest1();

        // 1. testget协assetting - defaultclosestatus
        $response = $this->getCollaborationSettings($projectId);
        $this->assertFalse($response['data']['is_collaboration_enabled']);
        $this->assertEquals(true, in_array($response['data']['default_join_permission'], ['viewer', 'editor']));

        // 2. start协asfeature
        $this->enableCollaboration($projectId);

        // 3. validate协asalreadystart
        $response = $this->getCollaborationSettings($projectId);
        $this->assertTrue($response['data']['is_collaboration_enabled']);

        // 4. close协asfeature
        $this->disableCollaboration($projectId);

        // 5. validate协asalreadyclose
        $response = $this->getCollaborationSettings($projectId);
        $this->assertFalse($response['data']['is_collaboration_enabled']);
    }

    /**
     * testbatchquantity操aspermissioncontrol.
     */
    public function testBatchOperationsPermissionControl(): void
    {
        $projectId = $this->projectId;

        // 1. preparetestenvironment
        $this->switchUserTest1();
        $this->enableCollaboration($projectId);
        $this->addTeamMembers($projectId);

        // 2. nonmanage者trybatchquantityupdatepermission - shouldfail
        $this->switchUserTest2();
        $this->batchUpdateMemberPermissions($projectId, 51202);

        // 3. nonmanage者trybatchquantitydeletemember - shouldfail
        $this->batchDeleteMembers($projectId, 51202);

        // 4. manage者canconductbatchquantity操as
        $this->switchUserTest1();
        $this->batchUpdateMemberPermissions($projectId);
        $this->batchDeleteMembers($projectId);
    }

    /**
     * testorganizationside界control.
     */
    public function testOrganizationBoundaryControl(): void
    {
        $projectId = $this->projectId;

        $this->switchUserTest1();
        $this->enableCollaboration($projectId);

        // tryaddotherorganizationuser - shouldfail
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

        // shouldreturnmembernot存inerror
        $this->assertNotEquals(1000, $response['code']);
    }

    /**
     * testside界情况.
     */
    public function testEdgeCases(): void
    {
        $projectId = $this->projectId;

        $this->switchUserTest1();
        $this->enableCollaboration($projectId);

        // 1. testaddnullmembercolumntable
        $this->addEmptyMembersList($projectId, 5003);

        // 2. testduplicateaddsamemember
        $this->addTeamMembers($projectId);
        //        $this->addTeamMembers($projectId); // duplicateadd

        // 3. testinvalidpermissionlevel别
        $this->addMembersWithInvalidPermission($projectId, 5003);

        // 4. testnotcandeletefrom己
        $this->switchUserTest2();
        $this->cannotDeleteSelf($projectId);

        $this->switchUserTest1();

        // 5. test协ascloseo clocknotcanaddmember
        $this->disableCollaboration($projectId);
        $this->addTeamMembers($projectId, 51202); // 协asalreadycloseerror
    }

    /**
     * test多languageerrormessage.
     */
    public function testMultiLanguageErrorMessages(): void
    {
        $projectId = $this->projectId;

        // 1. testmiddle文errormessage
        $this->switchUserTest2(); // nopermissionuser
        $response = $this->addTeamMembers($projectId, 51202);
        $this->assertStringContainsString('permission', $response['message']);

        // 2. test协asnotstarterror
        $this->switchUserTest1();
        $response = $this->addTeamMembers($projectId, 51202);
        $this->assertStringContainsString('协as', $response['message']);
    }

    // ========== 辅助testmethod ==========

    /**
     * startproject协as.
     */
    public function enableCollaboration(string $projectId, int $expectedCode = 1000): array
    {
        $requestData = ['is_collaboration_enabled' => true];

        $response = $this->put(
            self::BASE_URI . "/{$projectId}",
            $requestData,
            $this->getCommonHeaders()
        );

        $this->assertNotNull($response, 'responsenotshouldfornull');
        $this->assertEquals($expectedCode, $response['code'], $response['message'] ?? '');

        if ($expectedCode === 1000) {
            $this->assertEquals('ok', $response['message']);
            $this->assertIsArray($response['data']);
            $this->assertEquals('is_collaboration_enabled', $response['data']['is_collaboration_enabled']);
        }

        return $response;
    }

    /**
     * closeproject协as.
     */
    public function disableCollaboration(string $projectId, int $expectedCode = 1000): array
    {
        $requestData = ['is_collaboration_enabled' => false];

        $response = $this->put(
            self::BASE_URI . "/{$projectId}",
            $requestData,
            $this->getCommonHeaders()
        );

        $this->assertNotNull($response, 'responsenotshouldfornull');
        $this->assertEquals($expectedCode, $response['code'], $response['message'] ?? '');

        return $response;
    }

    /**
     * get协assetting.
     */
    public function getCollaborationSettings(string $projectId, int $expectedCode = 1000): array
    {
        $response = $this->get(
            self::BASE_URI . "/{$projectId}",
            [],
            $this->getCommonHeaders()
        );

        $this->assertNotNull($response, 'responsenotshouldfornull');
        $this->assertEquals($expectedCode, $response['code'], $response['message'] ?? '');

        if ($expectedCode === 1000) {
            $this->assertEquals('ok', $response['message']);
            $this->assertIsBool($response['data']['is_collaboration_enabled']);
            $this->assertIsString($response['data']['default_join_permission']);
        }

        return $response;
    }

    /**
     * addteammember.
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

        $this->assertNotNull($response, 'responsenotshouldfornull');
        $this->assertEquals($expectedCode, $response['code'], $response['message'] ?? '');

        if ($expectedCode === 1000) {
            $this->assertEquals('ok', $response['message']);
            $this->assertArrayHasKey('members', $response['data']);
        }

        return $response;
    }

    /**
     * addmore多teammember(testmanage者permission).
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

        $this->assertNotNull($response, 'responsenotshouldfornull');
        $this->assertEquals($expectedCode, $response['code'], $response['message'] ?? '');

        return $response;
    }

    /**
     * addnullmembercolumntable.
     */
    public function addEmptyMembersList(string $projectId, int $expectedCode = 1000): array
    {
        $requestData = ['members' => []];

        $response = $this->post(
            self::BASE_URI . "/{$projectId}/members",
            $requestData,
            $this->getCommonHeaders()
        );

        $this->assertNotNull($response, 'responsenotshouldfornull');
        $this->assertEquals($expectedCode, $response['code'], $response['message'] ?? '');

        return $response;
    }

    /**
     * addinvalidpermissionmember.
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

        $this->assertNotNull($response, 'responsenotshouldfornull');
        $this->assertEquals($expectedCode, $response['code'], $response['message'] ?? '');

        return $response;
    }

    /**
     * batchquantityupdatememberpermission.
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

        $this->assertNotNull($response, 'responsenotshouldfornull');
        $this->assertEquals($expectedCode, $response['code'], $response['message'] ?? '');

        if ($expectedCode === 1000) {
            $this->assertEquals('ok', $response['message']);
            $this->assertIsArray($response['data']);
            $this->assertEmpty($response['data']);
        }

        return $response;
    }

    /**
     * batchquantitydeletemember.
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

        $this->assertNotNull($response, 'responsenotshouldfornull');
        $this->assertEquals($expectedCode, $response['code'], $response['message'] ?? '');

        if ($expectedCode === 1000) {
            $this->assertEquals('ok', $response['message']);
            $this->assertIsArray($response['data']);
            $this->assertEmpty($response['data']);
        }

        return $response;
    }

    /**
     * updatememberformanage者.
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
     * testnotcandeletefrom己.
     */
    public function cannotDeleteSelf(string $projectId): void
    {
        // 先addcurrentuserformember
        //        $this->addTeamMembers($projectId);

        // trydeletefrom己
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

        // shouldreturnnotcandeletefrom己error
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
     * validatememberalreadyadd.
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

        // validateaddmember存in
        $memberIds = array_column($response['data']['members'], 'user_id');
        $departmentIds = array_column($response['data']['members'], 'department_id');

        $this->assertContains($this->testUserId2, array_filter($memberIds));
        $this->assertContains($this->testDepartmentId1, array_filter($departmentIds));
    }

    /**
     * validatepermissionalreadyupdate.
     */
    public function verifyPermissionsUpdated(string $projectId): void
    {
        $response = $this->get(
            self::BASE_URI . "/{$projectId}/members",
            [],
            $this->getCommonHeaders()
        );

        $this->assertEquals(1000, $response['code']);

        // findfinger定userpermission
        $members = $response['data']['members'];
        foreach ($members as $member) {
            if (isset($member['user_id']) && $member['user_id'] === $this->testUserId2) {
                $this->assertEquals('manage', $member['role']);
                break;
            }
        }
    }

    /**
     * validatememberalreadydelete.
     */
    public function verifyMembersDeleted(string $projectId): void
    {
        $response = $this->get(
            self::BASE_URI . "/{$projectId}/members",
            [],
            $this->getCommonHeaders()
        );

        $this->assertEquals(1000, $response['code']);

        // validatedeletemembernot存in
        $memberIds = array_column($response['data']['members'], 'user_id');
        $this->assertNotContains($this->testUserId2, array_filter($memberIds));
    }

    /**
     * cleanupprojectmemberdata(directlydatabasedelete).
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
            echo "cleanupprojectmemberdatacomplete: {$projectId}\n";
        } catch (Exception $e) {
            echo 'cleanupprojectmemberdatafail: ' . $e->getMessage() . "\n";
        }
    }
}
