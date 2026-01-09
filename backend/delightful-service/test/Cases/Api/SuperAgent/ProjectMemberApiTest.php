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
use Mockery;

/**
 * @internal
 * projectmember管理APItest
 */
class ProjectMemberApiTest extends AbstractApiTest
{
    private const BASE_URI = '/api/v1/be-agent/projects';

    private string $fileId = '816640336984018944';

    private string $projectId = '816065897791012866';

    private string $workspaceId = '798545276362801698';

    protected function setUp(): void
    {
        // 清理projectmemberdata，避免唯一键conflict
        $this->cleanupProjectMembers($this->projectId);
        parent::setUp();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testUpdateFile()
    {
        $projectId = $this->projectId;
        $fileId = (int) $this->fileId;

        $this->switchUserTest1();
        $this->updateEmptyMembers($projectId);
        $this->updateFileContent($fileId, 'test1', 51154);

        // 没permission
        $this->switchUserTest2();
        $this->updateFileContent($fileId, 'test2', 51202);

        // 添加team协作
        $this->switchUserTest1();
        $this->updateMembers($projectId);

        // 有permission
        $this->switchUserTest2();
        $this->updateFileContent($fileId, 'test2', 51154);
    }

    public function testFile()
    {
        // use现有的project和fileID进行test
        $fileId = $this->fileId; // testfileID
        $projectId = $this->projectId;

        $this->switchUserTest1();
        $this->updateEmptyMembers($projectId);

        // test没permission
        $this->fileEditingPermissionControl($fileId);

        $this->switchUserTest1();

        $this->updateMembers($projectId);

        // 10. testfileeditstatus管理feature
        $this->fileEditingStatusManagement($fileId);

        $this->fileEditingEdgeCases($fileId);
    }

    /**
     * testproject置顶permission控制.
     */
    public function testProjectPinPermission(): void
    {
        $projectId = $this->projectId;

        // 1. 先settingprojectmember，ensuretest2user有permission
        $this->switchUserTest1();
        $this->updateMembers($projectId);

        // 2. 切换到有permission的usertest置顶success
        $this->switchUserTest2();
        $this->pinProject($projectId, true);

        // 3. validate置顶success
        $response = $this->collaborationProjectsWithPinCheck();
        $this->verifyProjectPinStatus($response, $projectId, true);

        // 4. 清nullprojectmember，使currentuser没有permission
        $this->switchUserTest1();
        $this->updateEmptyMembers($projectId);

        // 5. 切换到没有permission的usertestpermission控制
        $this->switchUserTest2();
        // test非projectmember不能置顶 - shouldreturnpermissionerror
        $this->pinProject($projectId, true, 51202); // 假设51202是permissionerror码
    }

    /**
     * test置顶feature边界情况.
     */
    public function testProjectPinEdgeCases(): void
    {
        $projectId = $this->projectId;

        // ensureuser有permission
        $this->switchUserTest1();
        $this->updateMembers($projectId);
        $this->switchUserTest2();

        // 1. 重复置顶同一个project - should正常handle
        $this->pinProject($projectId, true);
        $this->pinProject($projectId, true); // 重复置顶

        // validateproject仍然是置顶status
        $response = $this->collaborationProjectsWithPinCheck();
        $this->verifyProjectPinStatus($response, $projectId, true);

        // 2. 重复cancel置顶 - should正常handle
        $this->pinProject($projectId, false);
        $this->pinProject($projectId, false); // 重复cancel置顶

        // validateproject不是置顶status
        $response = $this->collaborationProjectsWithPinCheck();
        $this->verifyProjectPinStatus($response, $projectId, false);
    }

    /**
     * testupdateprojectmember - success场景.
     */
    public function testUpdateProjectMembersSuccess(): void
    {
        $this->projectDetail((int) $this->projectId);

        $this->switchUserTest1();

        /*$requestData = [
            'workspace_name' => date('Y-m-d')
        ];

        // 1. create工作区
        $response = $this->post('/api/v1/be-agent/workspaces', $requestData, $this->getCommonHeaders());
        $this->assertSame(1000, $response['code']);
        $workspaceId = $response['data']['id'];

        $requestData = [
            'project_description' => '',
            'project_mode' => '',
            'project_name' => date('Y-m-d').time(),
            'workspace_id' => $workspaceId,
        ];

        // 2. create工作区
        $response = $this->post('/api/v1/be-agent/projects', $requestData, $this->getCommonHeaders());
        $this->assertSame(1000, $response['code']);
        $projectId = $response['data']['project']['id'];*/

        // 话题列表
        $workspaceId = $this->workspaceId;
        $projectId = $this->projectId;

        $this->updateProject($workspaceId, $projectId);
        $this->updateProject($workspaceId, $projectId);

        // ensure不will对原有feature造成影响
        // create话题
        $topicId = $this->createTopic($workspaceId, $projectId);
        // 话题列表
        $this->topicList($workspaceId, $projectId);
        // update话题
        $this->renameTopic($workspaceId, $projectId, $topicId);
        // share话题
        $this->createTopicShare($workspaceId, $projectId, $topicId);
        // projectfile
        $this->attachments($workspaceId, $projectId, $topicId);
        // delete话题
        $this->deleteTopic($workspaceId, $projectId, $topicId);

        $this->updateEmptyMembers($projectId);

        // 3. 没有permission
        $this->switchUserTest2();
        $this->updateEmptyMembers($projectId, 51202);
        $this->updateProject($workspaceId, $projectId, 51202);
        $this->deleteProject($workspaceId, $projectId, 51202);

        $this->switchUserTest1();

        // 4. 添加nullmember
        $this->updateEmptyMembers($projectId);

        // 5. 添加projectmember
        $this->updateMembers($projectId);
        // 6. 查看projectmember
        $this->projectMember($projectId);

        $this->collaborationProjects('test', 0);
        $this->shareCollaborationProjects('test', 1);

        $this->switchUserTest2();

        // 7. 查看projectmember
        $this->projectMember($projectId);
        // 8. 查看协作project列表
        $this->collaborationProjects();
        $this->collaborationProjects('test');

        // create话题
        $topicId = $this->createTopic($workspaceId, $projectId);
        // 话题列表
        $this->topicList($workspaceId, $projectId);
        // update话题
        $this->renameTopic($workspaceId, $projectId, $topicId);
        // share话题
        $this->createTopicShare($workspaceId, $projectId, $topicId);
        // sendmessage
        //        $this->sendMessage($workspaceId, $projectId, $topicId);
        // projectfile
        $file = $this->attachments($workspaceId, $projectId, $topicId);
        // 重命名projectfile
        //        $this->renameAttachments((string) $file['file_id']);

        // delete话题
        $this->deleteTopic($workspaceId, $projectId, $topicId);

        // 9. testproject置顶feature
        $this->projectPinFeature($projectId);

        // 10. test协作projectcreate者列表feature
        //        $this->collaborationProjectCreatorFeature();

        // 11. 清nullnullmember
        $requestData = ['members' => []];

        // sendPUTrequest
        $response = $this->put(self::BASE_URI . "/{$projectId}/members", $requestData, $this->getCommonHeaders());
        $this->assertEquals(1000, $response['code']);
    }

    public function updateMembers(string $projectId): void
    {
        $requestData = [
            'members' => [
                [
                    'target_type' => 'User',
                    'target_id' => 'usi_27229966f39dd1b62c9d1449e3f7a90d',
                ],
                [
                    'target_type' => 'User',
                    'target_id' => 'usi_d131724ae038b5a94f7fd6637f11ef2f',
                ],
                [
                    'target_type' => 'Department',
                    'target_id' => '727236421093691395',
                ],
                [
                    'target_type' => 'Department',
                    'target_id' => '727236421089497089',
                ],
                [
                    'target_type' => 'User',
                    'target_id' => 'usi_e9d64db5b986d062a342793013f682e8',
                ],
            ],
        ];
        // sendPUTrequest
        $response = $this->put(self::BASE_URI . "/{$projectId}/members", $requestData, $this->getCommonHeaders());
        $this->assertNotNull($response, 'response不should为null');
        $this->assertEquals(1000, $response['code']);
    }

    public function updateEmptyMembers(string $projectId, int $code = 1000): void
    {
        $requestData = [
            'members' => [],
        ];
        // sendPUTrequest
        $response = $this->put(self::BASE_URI . "/{$projectId}/members", $requestData, $this->getCommonHeaders());
        $this->assertEquals($code, $response['code']);
    }

    public function projectMember(string $projectId): void
    {
        $response = $this->get(self::BASE_URI . "/{$projectId}/members", [], $this->getCommonHeaders());
        $this->assertNotNull($response, 'response不should为null');
        $this->assertEquals(1000, $response['code']);
        $this->assertGreaterThan(4, count($response['data']['members']));
        $this->assertEquals('usi_27229966f39dd1b62c9d1449e3f7a90d', $response['data']['members'][0]['user_id']);
        $this->assertEquals('usi_d131724ae038b5a94f7fd6637f11ef2f', $response['data']['members'][1]['user_id']);
        $this->assertArrayHasKey('path_nodes', $response['data']['members'][0]);
    }

    public function collaborationProjects(string $name = '', ?int $count = null): void
    {
        $params = [];
        if ($name) {
            $params['name'] = $name;
        }

        $response = $this->client->get('/api/v1/be-agent/collaboration-projects', $params, $this->getCommonHeaders());

        $this->assertNotNull($response, 'response不should为null');
        $this->assertEquals(1000, $response['code'], $response['message'] ?? '');
        $this->assertEquals('ok', $response['message']);
        $this->assertIsArray($response['data']);

        // validateresponse结构
        $this->assertArrayHasKey('list', $response['data'], 'response应containlistfield');
        $this->assertArrayHasKey('total', $response['data'], 'response应containtotalfield');
        if (! is_null($count)) {
            $this->assertEquals(0, count($response['data']['list']));
        } else {
            $this->assertIsArray($response['data']['list'], 'listshould是array');
            $this->assertIsInt($response['data']['total'], 'totalshould是整数');
            $project = $response['data']['list'][0];
            $this->assertArrayHasKey('id', $project);
            $this->assertArrayHasKey('project_name', $project);
            $this->assertArrayHasKey('workspace_name', $project);
            $this->assertArrayHasKey('tag', $project);
            $this->assertEquals('collaboration', $project['tag']);
            $this->assertGreaterThan(3, $project['member_count']);
            $this->assertGreaterThan(3, count($project['members']));
        }

        //        $this->assertEquals('usi_27229966f39dd1b62c9d1449e3f7a90d', $project['members'][0]['user_id']);
        //        $this->assertEquals('usi_d131724ae038b5a94f7fd6637f11ef2f', $project['members'][1]['user_id']);
        //        $this->assertEquals('727236421093691395', $project['members'][2]['department_id']);
    }

    public function shareCollaborationProjects(string $name = '', ?int $count = null): void
    {
        $params = [];
        if ($name) {
            $params['name'] = $name;
        }

        $response = $this->client->get('/api/v1/be-agent/collaboration-projects?type=shared', $params, $this->getCommonHeaders());
        $this->assertNotNull($response, 'response不should为null');
        $this->assertEquals(1000, $response['code'], $response['message'] ?? '');
        $this->assertEquals('ok', $response['message']);
        $this->assertIsArray($response['data']);

        // validateresponse结构
        $this->assertArrayHasKey('list', $response['data'], 'response应containlistfield');
        $this->assertArrayHasKey('total', $response['data'], 'response应containtotalfield');
        if (! is_null($count)) {
            $this->assertEquals($count, count($response['data']['list']));
        } else {
            $this->assertIsArray($response['data']['list'], 'listshould是array');
            $this->assertIsInt($response['data']['total'], 'totalshould是整数');
            $project = $response['data']['list'][0];
            $this->assertArrayHasKey('id', $project);
            $this->assertArrayHasKey('project_name', $project);
            $this->assertArrayHasKey('workspace_name', $project);
            $this->assertArrayHasKey('tag', $project);
            $this->assertEquals('collaboration', $project['tag']);
            $this->assertGreaterThan(3, $project['member_count']);
            $this->assertGreaterThan(3, count($project['members']));
        }

        //        $this->assertEquals('usi_27229966f39dd1b62c9d1449e3f7a90d', $project['members'][0]['user_id']);
        //        $this->assertEquals('usi_d131724ae038b5a94f7fd6637f11ef2f', $project['members'][1]['user_id']);
        //        $this->assertEquals('727236421093691395', $project['members'][2]['department_id']);
    }

    public function createTopic(string $workspaceId, string $projectId): string
    {
        $requestData = [
            'project_id' => $projectId,
            'topic_name' => '',
        ];

        $response = $this->post('/api/v1/be-agent/topics', $requestData, $this->getCommonHeaders());
        $this->assertEquals(1000, $response['code']);
        $this->assertArrayHasKey('id', $response['data']);
        return $response['data']['id'];
    }

    public function topicList(string $workspaceId, string $projectId): void
    {
        $response = $this->get(self::BASE_URI . "/{$projectId}/topics?page=1&page_size=20", [], $this->getCommonHeaders());
        $this->assertEquals(1000, $response['code']);
        $this->assertGreaterThan(0, count($response['data']['list']));
    }

    public function renameTopic(string $workspaceId, string $projectId, string $topicId): string
    {
        $requestData = [
            'project_id' => $projectId,
            'workspace_id' => $workspaceId,
            'topic_name' => '4324234',
        ];
        $response = $this->put('/api/v1/be-agent/topics/' . $topicId, $requestData, $this->getCommonHeaders());
        $this->assertEquals(1000, $response['code']);
        $this->assertArrayHasKey('id', $response['data']);
        return $response['data']['id'];
    }

    public function createTopicShare(string $workspaceId, string $projectId, string $topicId): void
    {
        $requestData = [
            'pwd' => '123123',
            'resource_id' => $topicId,
            'resource_type' => 5,
            'share_type' => 4,
        ];
        $response = $this->post('/api/v1/share/resources/create', $requestData, $this->getCommonHeaders());
        $this->assertEquals(1000, $response['code']);
        $this->assertArrayHasKey('id', $response['data']);
    }

    public function deleteTopic(string $workspaceId, string $projectId, string $topicId): void
    {
        $requestData = [
            'id' => $topicId,
            'workspace_id' => $workspaceId,
        ];
        $response = $this->post('/api/v1/be-agent/topics/delete', $requestData, $this->getCommonHeaders());
        $this->assertEquals(1000, $response['code']);
        $this->assertArrayHasKey('id', $response['data']);
    }

    public function sendMessage(string $workspaceId, string $projectId, string $topicId): void
    {
        $requestData = [
            'conversation_id' => time(),
            'message' => '123123123',
            'topic_id' => $topicId,
        ];
        $response = $this->post('/api/v1/im/typing/completions', $requestData, $this->getCommonHeaders());
        $this->assertEquals(1000, $response['code']);
        $this->assertArrayHasKey('id', $response['data']);
    }

    public function attachments(string $workspaceId, string $projectId, string $topicId): array
    {
        $requestData = [
            'file_type' => [
                'user_upload', 'process', 'system_auto_upload', 'directory',
            ],
            'page' => 1,
            'page_size' => 999,
            'token' => '',
        ];
        $response = $this->post('/api/v1/be-agent/projects/' . $projectId . '/attachments', $requestData, $this->getCommonHeaders());
        $this->assertEquals(1000, $response['code']);
        $this->assertGreaterThan('1', $response['data']['total']);
        return $response['data']['tree'][0];
    }

    public function renameAttachments(string $fileId): void
    {
        $requestData = [
            'target_name' => 'dsadvfsdfs',
        ];
        $response = $this->post('/api/v1/be-agent/file/' . $fileId . '/rename', $requestData, $this->getCommonHeaders());
        $this->assertEquals(1000, $response['code']);
        $this->assertArrayHasKey('file_id', $response['data']);
    }

    public function updateProject(string $workspaceId, string $projectId, int $code = 1000): void
    {
        $requestData = [
            'workspace_id' => $workspaceId,
            'project_name' => 'test',
            'project_description' => 'test',
        ];
        $response = $this->put('/api/v1/be-agent/projects/' . $projectId, $requestData, $this->getCommonHeaders());
        $this->assertEquals($code, $response['code']);
    }

    public function deleteProject(string $workspaceId, string $projectId, int $code = 1000): void
    {
        $response = $this->delete('/api/v1/be-agent/projects/' . $projectId, [], $this->getCommonHeaders());
        $this->assertEquals($code, $response['code']);
    }

    /**
     * testfileeditstatus管理 - 完整processtest.
     */
    public function fileEditingStatusManagement(string $fileId): void
    {
        $this->switchUserTest1();

        // 1. test加入edit
        $this->joinFileEditing($fileId);

        // 2. testgetedituserquantity - should有1个user在edit
        $editingCount = $this->getEditingUsers($fileId);
        $this->assertEquals(1, $editingCount);

        // 3. 切换到另一个user，test多useredit
        $this->switchUserTest2();
        $this->joinFileEditing($fileId);

        // 4. 再次getedituserquantity - should有2个user在edit
        $editingCount = $this->getEditingUsers($fileId);
        $this->assertEquals(2, $editingCount);

        // 5. test离开edit
        $this->leaveFileEditing($fileId);

        // 6. getedituserquantity - should只剩1个user
        $editingCount = $this->getEditingUsers($fileId);
        $this->assertEquals(1, $editingCount);

        // 7. 切换回firstuser，testpermission
        $this->switchUserTest1();
        $this->leaveFileEditing($fileId);

        // 8. finalvalidate没有user在edit
        $editingCount = $this->getEditingUsers($fileId);
        $this->assertEquals(0, $editingCount);
    }

    /**
     * test加入fileedit.
     */
    public function joinFileEditing(string $fileId, int $expectedCode = 1000): array
    {
        $response = $this->post("/api/v1/be-agent/file/{$fileId}/join-editing", [], $this->getCommonHeaders());

        $this->assertNotNull($response, 'response不should为null');
        $this->assertEquals($expectedCode, $response['code'], $response['message'] ?? '');

        if ($expectedCode === 1000) {
            $this->assertEquals('ok', $response['message']);
            $this->assertIsArray($response['data']);
            $this->assertEmpty($response['data']); // join-editingreturnnullarray
        }

        return $response;
    }

    /**
     * test离开fileedit.
     */
    public function leaveFileEditing(string $fileId, int $expectedCode = 1000): array
    {
        $response = $this->post("/api/v1/be-agent/file/{$fileId}/leave-editing", [], $this->getCommonHeaders());

        $this->assertNotNull($response, 'response不should为null');
        $this->assertEquals($expectedCode, $response['code'], $response['message'] ?? '');

        if ($expectedCode === 1000) {
            $this->assertEquals('ok', $response['message']);
            $this->assertIsArray($response['data']);
            $this->assertEmpty($response['data']); // leave-editingreturnnullarray
        }

        return $response;
    }

    /**
     * testgetedituserquantity.
     */
    public function getEditingUsers(string $fileId, int $expectedCode = 1000): int
    {
        $response = $this->get("/api/v1/be-agent/file/{$fileId}/editing-users", [], $this->getCommonHeaders());

        $this->assertNotNull($response, 'response不should为null');
        $this->assertEquals($expectedCode, $response['code'], $response['message'] ?? '');

        if ($expectedCode === 1000) {
            $this->assertEquals('ok', $response['message']);
            $this->assertIsArray($response['data']);
            $this->assertArrayHasKey('editing_user_count', $response['data']);
            $this->assertIsInt($response['data']['editing_user_count']);
            return $response['data']['editing_user_count'];
        }

        return 0;
    }

    /**
     * testfileeditpermission控制.
     */
    public function fileEditingPermissionControl(string $unauthorizedFileId): void
    {
        $this->switchUserTest2();

        // test无permission加入edit - shouldreturnerror
        $this->joinFileEditing($unauthorizedFileId, 51202); // 假设51200是无permissionerror码

        // test无permission离开edit - shouldreturnerror
        $this->leaveFileEditing($unauthorizedFileId, 51202);

        // test无permissionqueryedituser - shouldreturnerror
        $this->getEditingUsers($unauthorizedFileId, 51202);
    }

    /**
     * testfileedit边界情况.
     */
    public function fileEditingEdgeCases(string $fileId): void
    {
        $this->switchUserTest1();

        // 1. 重复加入edit - should正常handle
        $this->joinFileEditing($fileId);
        $this->joinFileEditing($fileId); // 重复加入

        // validateuserquantity仍然是1
        $editingCount = $this->getEditingUsers($fileId);
        $this->assertEquals(1, $editingCount);

        // 2. 重复离开edit - should正常handle
        $this->leaveFileEditing($fileId);
        $this->leaveFileEditing($fileId); // 重复离开

        // validateuserquantity是0
        $editingCount = $this->getEditingUsers($fileId);
        $this->assertEquals(0, $editingCount);

        // 3. testinvalidfileIDformat
        $invalidFileId = 'invalid_file_id';
        $this->joinFileEditing($invalidFileId, 51202); // 假设400是parametererror
    }

    public function updateFileContent(int $fileId, string $content, int $expectedCode): void
    {
        $response = $this->post('/api/v1/be-agent/file/save', [
            [
                'file_id' => $fileId,
                'content' => $content,
                'enable_shadow' => false,
            ],
        ], $this->getCommonHeaders());

        $this->assertEquals(1000, $response['code'], $response['message'] ?? '');

        $this->assertEquals($expectedCode, $response['data']['error_files'][0]['error_code'], $response['data']['error_files'][0]['error']);
    }

    public function projectDetail(int $projectId): void
    {
        $response = $this->get('/api/v1/open-api/be-delightful/projects/' . $projectId, [], []);

        $this->assertEquals(1000, $response['code'], $response['message'] ?? '');

        $this->assertEquals('test', $response['data']['project_name']);
    }

    /**
     * testproject置顶feature - 完整processtest.
     */
    public function projectPinFeature(string $projectId): void
    {
        // ensurecurrentuser是projectmember
        $this->switchUserTest2();

        // 1. test置顶project
        $this->pinProject($projectId, true);

        // 2. validate协作project列表中project被置顶
        $response = $this->collaborationProjectsWithPinCheck();
        $this->verifyProjectPinStatus($response, $projectId, true);

        // 3. testcancel置顶
        $this->pinProject($projectId, false);

        // 4. validate协作project列表中project不再置顶
        $response = $this->collaborationProjectsWithPinCheck();
        $this->verifyProjectPinStatus($response, $projectId, false);

        // 5. 重新置顶project以testsort
        $this->pinProject($projectId, true);

        // 6. validate置顶project排在前面
        $response = $this->collaborationProjectsWithPinCheck();
        $this->verifyPinnedProjectsAtTop($response);
    }

    /**
     * 置顶或cancel置顶project.
     */
    public function pinProject(string $projectId, bool $isPinned, int $expectedCode = 1000): array
    {
        $requestData = [
            'is_pin' => $isPinned,
        ];

        $response = $this->put("/api/v1/be-agent/collaboration-projects/{$projectId}/pin", $requestData, $this->getCommonHeaders());

        $this->assertNotNull($response, 'response不should为null');
        $this->assertEquals($expectedCode, $response['code'], $response['message'] ?? '');

        if ($expectedCode === 1000) {
            $this->assertEquals('ok', $response['message']);
            $this->assertIsArray($response['data']);
            $this->assertEmpty($response['data']); // 置顶操作returnnullarray
        }

        return $response;
    }

    /**
     * get协作project列表并return完整响application于置顶validate.
     */
    public function collaborationProjectsWithPinCheck(): array
    {
        $response = $this->client->get('/api/v1/be-agent/collaboration-projects', [], $this->getCommonHeaders());

        $this->assertNotNull($response, 'response不should为null');
        $this->assertEquals(1000, $response['code'], $response['message'] ?? '');
        $this->assertEquals('ok', $response['message']);
        $this->assertIsArray($response['data']);

        // validateresponse结构contain置顶相关field
        $this->assertArrayHasKey('list', $response['data'], 'response应containlistfield');
        $this->assertArrayHasKey('total', $response['data'], 'response应containtotalfield');

        if (! empty($response['data']['list'])) {
            $project = $response['data']['list'][0];
            $this->assertArrayHasKey('is_pinned', $project, 'project应containis_pinnedfield');
            $this->assertIsBool($project['is_pinned'], 'is_pinnedshould是booleanvalue');
        }

        return $response;
    }

    /**
     * validateproject的置顶status.
     */
    public function verifyProjectPinStatus(array $response, string $projectId, bool $expectedPinned): void
    {
        $projects = $response['data']['list'];
        $targetProject = null;

        foreach ($projects as $project) {
            if ($project['id'] === $projectId) {
                $targetProject = $project;
                break;
            }
        }

        $this->assertNotNull($targetProject, "project {$projectId} should在协作project列表中");
        $this->assertEquals(
            $expectedPinned,
            $targetProject['is_pinned'],
            "project {$projectId} 的置顶statusshould为 " . ($expectedPinned ? 'true' : 'false')
        );
    }

    /**
     * validate置顶project排在列表前面.
     */
    public function verifyPinnedProjectsAtTop(array $response): void
    {
        $projects = $response['data']['list'];
        $pinnedProjectsEnded = false;

        foreach ($projects as $project) {
            if ($project['is_pinned']) {
                $this->assertFalse($pinnedProjectsEnded, '置顶projectshould排在非置顶project前面');
            } else {
                $pinnedProjectsEnded = true;
            }
        }
    }

    /**
     * test协作projectcreate者列表feature - 完整processtest.
     */
    public function collaborationProjectCreatorFeature(): void
    {
        // 1. test有permissionusergetcreate者列表
        $this->switchUserTest2(); // ensure是有permission的协作user
        $response = $this->getCollaborationProjectCreators();
        $this->verifyCreatorListResponse($response);

        // 2. testpermission控制 - 清nullmember后无permission
        $this->switchUserTest1(); // 切换到project所有者
        $this->updateEmptyMembers($this->projectId); // 清nullprojectmember

        $this->switchUserTest2(); // 切换到无permissionuser
        $emptyResponse = $this->getCollaborationProjectCreators();
        $this->verifyEmptyCreatorListResponse($emptyResponse);

        // 3. restoreprojectmemberstatus，以免影响后续test
        $this->switchUserTest1();
        $this->updateMembers($this->projectId);
    }

    /**
     * test协作projectcreate者列表permission控制.
     */
    public function testCollaborationProjectCreatorsPermission(): void
    {
        $projectId = $this->projectId;

        // 1. 先settingprojectmember，ensuretest2user有permission
        $this->switchUserTest1();
        $this->updateMembers($projectId);

        // 2. 切换到有permission的usertestgetcreate者列表success
        $this->switchUserTest2();
        $response = $this->getCollaborationProjectCreators();
        $this->verifyCreatorListResponse($response);

        // 3. 清nullprojectmember，使currentuser没有permission
        $this->switchUserTest1();
        $this->updateEmptyMembers($projectId);

        // 4. 切换到没有permission的usertestpermission控制
        $this->switchUserTest2();
        $emptyResponse = $this->getCollaborationProjectCreators();
        //        $this->verifyEmptyCreatorListResponse($emptyResponse);
    }

    /**
     * test协作projectcreate者列表边界情况.
     */
    public function testCollaborationProjectCreatorsEdgeCases(): void
    {
        // ensureuser有permission
        $this->switchUserTest1();
        $this->updateMembers($this->projectId);
        $this->switchUserTest2();

        // 1. 多次callAPI - shouldreturn一致result
        $response1 = $this->getCollaborationProjectCreators();
        $response2 = $this->getCollaborationProjectCreators();

        $this->assertEquals($response1['code'], $response2['code']);
        $this->assertEquals(count($response1['data']), count($response2['data']));

        // 2. validatecreate者去重 - 同一create者只should出现一次
        $response = $this->getCollaborationProjectCreators();
        $this->verifyCreatorListDeduplication($response);
    }

    /**
     * get协作projectcreate者列表.
     */
    public function getCollaborationProjectCreators(int $expectedCode = 1000): array
    {
        $response = $this->client->get('/api/v1/be-agent/collaboration-projects/creators', [], $this->getCommonHeaders());

        $this->assertNotNull($response, 'response不should为null');
        $this->assertEquals($expectedCode, $response['code'], $response['message'] ?? '');

        if ($expectedCode === 1000) {
            $this->assertEquals('ok', $response['message']);
            $this->assertIsArray($response['data']);
        }

        return $response;
    }

    /**
     * validatecreate者列表response结构.
     */
    public function verifyCreatorListResponse(array $response): void
    {
        $this->assertEquals(1000, $response['code']);
        $this->assertEquals('ok', $response['message']);
        $this->assertIsArray($response['data'], 'responsedatashould是array');

        // validateat least有一个create者
        $this->assertGreaterThan(0, count($response['data']), 'shouldat least有一个create者');

        // validatecreate者data结构
        $creator = $response['data'][0];
        $this->assertArrayHasKey('id', $creator, 'create者应containidfield');
        $this->assertArrayHasKey('name', $creator, 'create者应containnamefield');
        $this->assertArrayHasKey('user_id', $creator, 'create者应containuser_idfield');
        $this->assertArrayHasKey('avatar_url', $creator, 'create者应containavatar_urlfield');

        // validatefieldtype
        $this->assertIsString($creator['id'], 'idshould是string');
        $this->assertIsString($creator['name'], 'nameshould是string');
        $this->assertIsString($creator['user_id'], 'user_idshould是string');
        $this->assertIsString($creator['avatar_url'], 'avatar_urlshould是string');

        // validate必填field不为null
        $this->assertNotEmpty($creator['id'], 'id不should为null');
        $this->assertNotEmpty($creator['user_id'], 'user_id不should为null');
    }

    /**
     * validatenullcreate者列表response.
     */
    public function verifyEmptyCreatorListResponse(array $response): void
    {
        $this->assertEquals(1000, $response['code']);
        $this->assertEquals('ok', $response['message']);
        $this->assertIsArray($response['data'], 'responsedatashould是array');
        $this->assertEquals(0, count($response['data']), '无permission时shouldreturnnullarray');
    }

    /**
     * validatecreate者列表去重.
     */
    public function verifyCreatorListDeduplication(array $response): void
    {
        $creators = $response['data'];
        $userIds = array_column($creators, 'user_id');
        $uniqueUserIds = array_unique($userIds);

        $this->assertEquals(
            count($userIds),
            count($uniqueUserIds),
            'create者列表中不should有重复的user_id'
        );
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
