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
 * projectmembermanageAPItest
 */
class ProjectMemberApiTest extends AbstractApiTest
{
    private const BASE_URI = '/api/v1/be-agent/projects';

    private string $fileId = '816640336984018944';

    private string $projectId = '816065897791012866';

    private string $workspaceId = '798545276362801698';

    protected function setUp(): void
    {
        // cleanupprojectmemberdata,avoiduniqueonekeyconflict
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

        // notpermission
        $this->switchUserTest2();
        $this->updateFileContent($fileId, 'test2', 51202);

        // addteamcoordinateas
        $this->switchUserTest1();
        $this->updateMembers($projectId);

        // havepermission
        $this->switchUserTest2();
        $this->updateFileContent($fileId, 'test2', 51154);
    }

    public function testFile()
    {
        // useshowhaveprojectandfileIDconducttest
        $fileId = $this->fileId; // testfileID
        $projectId = $this->projectId;

        $this->switchUserTest1();
        $this->updateEmptyMembers($projectId);

        // testnotpermission
        $this->fileEditingPermissionControl($fileId);

        $this->switchUserTest1();

        $this->updateMembers($projectId);

        // 10. testfileeditstatusmanagefeature
        $this->fileEditingStatusManagement($fileId);

        $this->fileEditingEdgeCases($fileId);
    }

    /**
     * testprojectsettoppermissioncontrol.
     */
    public function testProjectPinPermission(): void
    {
        $projectId = $this->projectId;

        // 1. firstsettingprojectmember,ensuretest2userhavepermission
        $this->switchUserTest1();
        $this->updateMembers($projectId);

        // 2. switchtohavepermissionusertestsettopsuccess
        $this->switchUserTest2();
        $this->pinProject($projectId, true);

        // 3. validatesettopsuccess
        $response = $this->collaborationProjectsWithPinCheck();
        $this->verifyProjectPinStatus($response, $projectId, true);

        // 4. clearnullprojectmember,makecurrentusernothavepermission
        $this->switchUserTest1();
        $this->updateEmptyMembers($projectId);

        // 5. switchtonothavepermissionusertestpermissioncontrol
        $this->switchUserTest2();
        // testnonprojectmembernotcansettop - shouldreturnpermissionerror
        $this->pinProject($projectId, true, 51202); // falseset51202ispermissionerrorcode
    }

    /**
     * testsettopfeaturesideboundarysituation.
     */
    public function testProjectPinEdgeCases(): void
    {
        $projectId = $this->projectId;

        // ensureuserhavepermission
        $this->switchUserTest1();
        $this->updateMembers($projectId);
        $this->switchUserTest2();

        // 1. duplicatesettopsameoneproject - shouldnormalhandle
        $this->pinProject($projectId, true);
        $this->pinProject($projectId, true); // duplicatesettop

        // validateprojectstillissettopstatus
        $response = $this->collaborationProjectsWithPinCheck();
        $this->verifyProjectPinStatus($response, $projectId, true);

        // 2. duplicatecancelsettop - shouldnormalhandle
        $this->pinProject($projectId, false);
        $this->pinProject($projectId, false); // duplicatecancelsettop

        // validateprojectnotissettopstatus
        $response = $this->collaborationProjectsWithPinCheck();
        $this->verifyProjectPinStatus($response, $projectId, false);
    }

    /**
     * testupdateprojectmember - successscenario.
     */
    public function testUpdateProjectMembersSuccess(): void
    {
        $this->projectDetail((int) $this->projectId);

        $this->switchUserTest1();

        /*$requestData = [
            'workspace_name' => date('Y-m-d')
        ];

        // 1. createworkregion
        $response = $this->post('/api/v1/be-agent/workspaces', $requestData, $this->getCommonHeaders());
        $this->assertSame(1000, $response['code']);
        $workspaceId = $response['data']['id'];

        $requestData = [
            'project_description' => '',
            'project_mode' => '',
            'project_name' => date('Y-m-d').time(),
            'workspace_id' => $workspaceId,
        ];

        // 2. createworkregion
        $response = $this->post('/api/v1/be-agent/projects', $requestData, $this->getCommonHeaders());
        $this->assertSame(1000, $response['code']);
        $projectId = $response['data']['project']['id'];*/

        // topiccolumntable
        $workspaceId = $this->workspaceId;
        $projectId = $this->projectId;

        $this->updateProject($workspaceId, $projectId);
        $this->updateProject($workspaceId, $projectId);

        // ensurenotwilltooriginalhavefeaturecreatebecomeimpact
        // createtopic
        $topicId = $this->createTopic($workspaceId, $projectId);
        // topiccolumntable
        $this->topicList($workspaceId, $projectId);
        // updatetopic
        $this->renameTopic($workspaceId, $projectId, $topicId);
        // sharetopic
        $this->createTopicShare($workspaceId, $projectId, $topicId);
        // projectfile
        $this->attachments($workspaceId, $projectId, $topicId);
        // deletetopic
        $this->deleteTopic($workspaceId, $projectId, $topicId);

        $this->updateEmptyMembers($projectId);

        // 3. nothavepermission
        $this->switchUserTest2();
        $this->updateEmptyMembers($projectId, 51202);
        $this->updateProject($workspaceId, $projectId, 51202);
        $this->deleteProject($workspaceId, $projectId, 51202);

        $this->switchUserTest1();

        // 4. addnullmember
        $this->updateEmptyMembers($projectId);

        // 5. addprojectmember
        $this->updateMembers($projectId);
        // 6. viewprojectmember
        $this->projectMember($projectId);

        $this->collaborationProjects('test', 0);
        $this->shareCollaborationProjects('test', 1);

        $this->switchUserTest2();

        // 7. viewprojectmember
        $this->projectMember($projectId);
        // 8. viewcoordinateasprojectcolumntable
        $this->collaborationProjects();
        $this->collaborationProjects('test');

        // createtopic
        $topicId = $this->createTopic($workspaceId, $projectId);
        // topiccolumntable
        $this->topicList($workspaceId, $projectId);
        // updatetopic
        $this->renameTopic($workspaceId, $projectId, $topicId);
        // sharetopic
        $this->createTopicShare($workspaceId, $projectId, $topicId);
        // sendmessage
        //        $this->sendMessage($workspaceId, $projectId, $topicId);
        // projectfile
        $file = $this->attachments($workspaceId, $projectId, $topicId);
        // renameprojectfile
        //        $this->renameAttachments((string) $file['file_id']);

        // deletetopic
        $this->deleteTopic($workspaceId, $projectId, $topicId);

        // 9. testprojectsettopfeature
        $this->projectPinFeature($projectId);

        // 10. testcoordinateasprojectcreatepersoncolumntablefeature
        //        $this->collaborationProjectCreatorFeature();

        // 11. clearnullnullmember
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
        $this->assertNotNull($response, 'responsenotshouldfornull');
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
        $this->assertNotNull($response, 'responsenotshouldfornull');
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

        $this->assertNotNull($response, 'responsenotshouldfornull');
        $this->assertEquals(1000, $response['code'], $response['message'] ?? '');
        $this->assertEquals('ok', $response['message']);
        $this->assertIsArray($response['data']);

        // validateresponsestructure
        $this->assertArrayHasKey('list', $response['data'], 'responseshouldcontainlistfield');
        $this->assertArrayHasKey('total', $response['data'], 'responseshouldcontaintotalfield');
        if (! is_null($count)) {
            $this->assertEquals(0, count($response['data']['list']));
        } else {
            $this->assertIsArray($response['data']['list'], 'listshouldisarray');
            $this->assertIsInt($response['data']['total'], 'totalshouldisinteger');
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
        $this->assertNotNull($response, 'responsenotshouldfornull');
        $this->assertEquals(1000, $response['code'], $response['message'] ?? '');
        $this->assertEquals('ok', $response['message']);
        $this->assertIsArray($response['data']);

        // validateresponsestructure
        $this->assertArrayHasKey('list', $response['data'], 'responseshouldcontainlistfield');
        $this->assertArrayHasKey('total', $response['data'], 'responseshouldcontaintotalfield');
        if (! is_null($count)) {
            $this->assertEquals($count, count($response['data']['list']));
        } else {
            $this->assertIsArray($response['data']['list'], 'listshouldisarray');
            $this->assertIsInt($response['data']['total'], 'totalshouldisinteger');
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
     * testfileeditstatusmanage - completeprocesstest.
     */
    public function fileEditingStatusManagement(string $fileId): void
    {
        $this->switchUserTest1();

        // 1. testaddinputedit
        $this->joinFileEditing($fileId);

        // 2. testgetedituserquantity - shouldhave1userinedit
        $editingCount = $this->getEditingUsers($fileId);
        $this->assertEquals(1, $editingCount);

        // 3. switchtoanotheroneuser,testmultipleuseredit
        $this->switchUserTest2();
        $this->joinFileEditing($fileId);

        // 4. againtimegetedituserquantity - shouldhave2userinedit
        $editingCount = $this->getEditingUsers($fileId);
        $this->assertEquals(2, $editingCount);

        // 5. testleaveedit
        $this->leaveFileEditing($fileId);

        // 6. getedituserquantity - shouldonlyremaining1user
        $editingCount = $this->getEditingUsers($fileId);
        $this->assertEquals(1, $editingCount);

        // 7. switchreturnfirstuser,testpermission
        $this->switchUserTest1();
        $this->leaveFileEditing($fileId);

        // 8. finalvalidatenothaveuserinedit
        $editingCount = $this->getEditingUsers($fileId);
        $this->assertEquals(0, $editingCount);
    }

    /**
     * testaddinputfileedit.
     */
    public function joinFileEditing(string $fileId, int $expectedCode = 1000): array
    {
        $response = $this->post("/api/v1/be-agent/file/{$fileId}/join-editing", [], $this->getCommonHeaders());

        $this->assertNotNull($response, 'responsenotshouldfornull');
        $this->assertEquals($expectedCode, $response['code'], $response['message'] ?? '');

        if ($expectedCode === 1000) {
            $this->assertEquals('ok', $response['message']);
            $this->assertIsArray($response['data']);
            $this->assertEmpty($response['data']); // join-editingreturnnullarray
        }

        return $response;
    }

    /**
     * testleavefileedit.
     */
    public function leaveFileEditing(string $fileId, int $expectedCode = 1000): array
    {
        $response = $this->post("/api/v1/be-agent/file/{$fileId}/leave-editing", [], $this->getCommonHeaders());

        $this->assertNotNull($response, 'responsenotshouldfornull');
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

        $this->assertNotNull($response, 'responsenotshouldfornull');
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
     * testfileeditpermissioncontrol.
     */
    public function fileEditingPermissionControl(string $unauthorizedFileId): void
    {
        $this->switchUserTest2();

        // testnopermissionaddinputedit - shouldreturnerror
        $this->joinFileEditing($unauthorizedFileId, 51202); // falseset51200isnopermissionerrorcode

        // testnopermissionleaveedit - shouldreturnerror
        $this->leaveFileEditing($unauthorizedFileId, 51202);

        // testnopermissionqueryedituser - shouldreturnerror
        $this->getEditingUsers($unauthorizedFileId, 51202);
    }

    /**
     * testfileeditsideboundarysituation.
     */
    public function fileEditingEdgeCases(string $fileId): void
    {
        $this->switchUserTest1();

        // 1. duplicateaddinputedit - shouldnormalhandle
        $this->joinFileEditing($fileId);
        $this->joinFileEditing($fileId); // duplicateaddinput

        // validateuserquantitystillis1
        $editingCount = $this->getEditingUsers($fileId);
        $this->assertEquals(1, $editingCount);

        // 2. duplicateleaveedit - shouldnormalhandle
        $this->leaveFileEditing($fileId);
        $this->leaveFileEditing($fileId); // duplicateleave

        // validateuserquantityis0
        $editingCount = $this->getEditingUsers($fileId);
        $this->assertEquals(0, $editingCount);

        // 3. testinvalidfileIDformat
        $invalidFileId = 'invalid_file_id';
        $this->joinFileEditing($invalidFileId, 51202); // falseset400isparametererror
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
     * testprojectsettopfeature - completeprocesstest.
     */
    public function projectPinFeature(string $projectId): void
    {
        // ensurecurrentuserisprojectmember
        $this->switchUserTest2();

        // 1. testsettopproject
        $this->pinProject($projectId, true);

        // 2. validatecoordinateasprojectcolumntablemiddleprojectbesettop
        $response = $this->collaborationProjectsWithPinCheck();
        $this->verifyProjectPinStatus($response, $projectId, true);

        // 3. testcancelsettop
        $this->pinProject($projectId, false);

        // 4. validatecoordinateasprojectcolumntablemiddleprojectnotagainsettop
        $response = $this->collaborationProjectsWithPinCheck();
        $this->verifyProjectPinStatus($response, $projectId, false);

        // 5. reloadnewsettopprojectbytestsort
        $this->pinProject($projectId, true);

        // 6. validatesettopprojectrowinfrontsurface
        $response = $this->collaborationProjectsWithPinCheck();
        $this->verifyPinnedProjectsAtTop($response);
    }

    /**
     * settoporcancelsettopproject.
     */
    public function pinProject(string $projectId, bool $isPinned, int $expectedCode = 1000): array
    {
        $requestData = [
            'is_pin' => $isPinned,
        ];

        $response = $this->put("/api/v1/be-agent/collaboration-projects/{$projectId}/pin", $requestData, $this->getCommonHeaders());

        $this->assertNotNull($response, 'responsenotshouldfornull');
        $this->assertEquals($expectedCode, $response['code'], $response['message'] ?? '');

        if ($expectedCode === 1000) {
            $this->assertEquals('ok', $response['message']);
            $this->assertIsArray($response['data']);
            $this->assertEmpty($response['data']); // settopoperationasreturnnullarray
        }

        return $response;
    }

    /**
     * getcoordinateasprojectcolumntableandreturncomplete响applicationatsettopvalidate.
     */
    public function collaborationProjectsWithPinCheck(): array
    {
        $response = $this->client->get('/api/v1/be-agent/collaboration-projects', [], $this->getCommonHeaders());

        $this->assertNotNull($response, 'responsenotshouldfornull');
        $this->assertEquals(1000, $response['code'], $response['message'] ?? '');
        $this->assertEquals('ok', $response['message']);
        $this->assertIsArray($response['data']);

        // validateresponsestructurecontainsettoprelatedclosefield
        $this->assertArrayHasKey('list', $response['data'], 'responseshouldcontainlistfield');
        $this->assertArrayHasKey('total', $response['data'], 'responseshouldcontaintotalfield');

        if (! empty($response['data']['list'])) {
            $project = $response['data']['list'][0];
            $this->assertArrayHasKey('is_pinned', $project, 'projectshouldcontainis_pinnedfield');
            $this->assertIsBool($project['is_pinned'], 'is_pinnedshouldisbooleanvalue');
        }

        return $response;
    }

    /**
     * validateprojectsettopstatus.
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

        $this->assertNotNull($targetProject, "project {$projectId} shouldincoordinateasprojectcolumntablemiddle");
        $this->assertEquals(
            $expectedPinned,
            $targetProject['is_pinned'],
            "project {$projectId} settopstatusshouldfor " . ($expectedPinned ? 'true' : 'false')
        );
    }

    /**
     * validatesettopprojectrowincolumntablefrontsurface.
     */
    public function verifyPinnedProjectsAtTop(array $response): void
    {
        $projects = $response['data']['list'];
        $pinnedProjectsEnded = false;

        foreach ($projects as $project) {
            if ($project['is_pinned']) {
                $this->assertFalse($pinnedProjectsEnded, 'settopprojectshouldrowinnonsettopprojectfrontsurface');
            } else {
                $pinnedProjectsEnded = true;
            }
        }
    }

    /**
     * testcoordinateasprojectcreatepersoncolumntablefeature - completeprocesstest.
     */
    public function collaborationProjectCreatorFeature(): void
    {
        // 1. testhavepermissionusergetcreatepersoncolumntable
        $this->switchUserTest2(); // ensureishavepermissioncoordinateasuser
        $response = $this->getCollaborationProjectCreators();
        $this->verifyCreatorListResponse($response);

        // 2. testpermissioncontrol - clearnullmemberbacknopermission
        $this->switchUserTest1(); // switchtoproject haveperson
        $this->updateEmptyMembers($this->projectId); // clearnullprojectmember

        $this->switchUserTest2(); // switchtonopermissionuser
        $emptyResponse = $this->getCollaborationProjectCreators();
        $this->verifyEmptyCreatorListResponse($emptyResponse);

        // 3. restoreprojectmemberstatus,by免impactbackcontinuetest
        $this->switchUserTest1();
        $this->updateMembers($this->projectId);
    }

    /**
     * testcoordinateasprojectcreatepersoncolumntablepermissioncontrol.
     */
    public function testCollaborationProjectCreatorsPermission(): void
    {
        $projectId = $this->projectId;

        // 1. firstsettingprojectmember,ensuretest2userhavepermission
        $this->switchUserTest1();
        $this->updateMembers($projectId);

        // 2. switchtohavepermissionusertestgetcreatepersoncolumntablesuccess
        $this->switchUserTest2();
        $response = $this->getCollaborationProjectCreators();
        $this->verifyCreatorListResponse($response);

        // 3. clearnullprojectmember,makecurrentusernothavepermission
        $this->switchUserTest1();
        $this->updateEmptyMembers($projectId);

        // 4. switchtonothavepermissionusertestpermissioncontrol
        $this->switchUserTest2();
        $emptyResponse = $this->getCollaborationProjectCreators();
        //        $this->verifyEmptyCreatorListResponse($emptyResponse);
    }

    /**
     * testcoordinateasprojectcreatepersoncolumntablesideboundarysituation.
     */
    public function testCollaborationProjectCreatorsEdgeCases(): void
    {
        // ensureuserhavepermission
        $this->switchUserTest1();
        $this->updateMembers($this->projectId);
        $this->switchUserTest2();

        // 1. multipletimecallAPI - shouldreturnonetoresult
        $response1 = $this->getCollaborationProjectCreators();
        $response2 = $this->getCollaborationProjectCreators();

        $this->assertEquals($response1['code'], $response2['code']);
        $this->assertEquals(count($response1['data']), count($response2['data']));

        // 2. validatecreatepersongoreload - sameonecreatepersononlyshouldoutshowonetime
        $response = $this->getCollaborationProjectCreators();
        $this->verifyCreatorListDeduplication($response);
    }

    /**
     * getcoordinateasprojectcreatepersoncolumntable.
     */
    public function getCollaborationProjectCreators(int $expectedCode = 1000): array
    {
        $response = $this->client->get('/api/v1/be-agent/collaboration-projects/creators', [], $this->getCommonHeaders());

        $this->assertNotNull($response, 'responsenotshouldfornull');
        $this->assertEquals($expectedCode, $response['code'], $response['message'] ?? '');

        if ($expectedCode === 1000) {
            $this->assertEquals('ok', $response['message']);
            $this->assertIsArray($response['data']);
        }

        return $response;
    }

    /**
     * validatecreatepersoncolumntableresponsestructure.
     */
    public function verifyCreatorListResponse(array $response): void
    {
        $this->assertEquals(1000, $response['code']);
        $this->assertEquals('ok', $response['message']);
        $this->assertIsArray($response['data'], 'responsedatashouldisarray');

        // validateat leasthaveonecreateperson
        $this->assertGreaterThan(0, count($response['data']), 'shouldat leasthaveonecreateperson');

        // validatecreatepersondatastructure
        $creator = $response['data'][0];
        $this->assertArrayHasKey('id', $creator, 'createshouldcontainidfield');
        $this->assertArrayHasKey('name', $creator, 'createshouldcontainnamefield');
        $this->assertArrayHasKey('user_id', $creator, 'createshouldcontainuser_idfield');
        $this->assertArrayHasKey('avatar_url', $creator, 'createshouldcontainavatar_urlfield');

        // validatefieldtype
        $this->assertIsString($creator['id'], 'idshouldisstring');
        $this->assertIsString($creator['name'], 'nameshouldisstring');
        $this->assertIsString($creator['user_id'], 'user_idshouldisstring');
        $this->assertIsString($creator['avatar_url'], 'avatar_urlshouldisstring');

        // validaterequiredfieldnotfornull
        $this->assertNotEmpty($creator['id'], 'idnotshouldfornull');
        $this->assertNotEmpty($creator['user_id'], 'user_idnotshouldfornull');
    }

    /**
     * validatenullcreatepersoncolumntableresponse.
     */
    public function verifyEmptyCreatorListResponse(array $response): void
    {
        $this->assertEquals(1000, $response['code']);
        $this->assertEquals('ok', $response['message']);
        $this->assertIsArray($response['data'], 'responsedatashouldisarray');
        $this->assertEquals(0, count($response['data']), 'nopermissiono clockshouldreturnnullarray');
    }

    /**
     * validatecreatepersoncolumntablegoreload.
     */
    public function verifyCreatorListDeduplication(array $response): void
    {
        $creators = $response['data'];
        $userIds = array_column($creators, 'user_id');
        $uniqueUserIds = array_unique($userIds);

        $this->assertEquals(
            count($userIds),
            count($uniqueUserIds),
            'createpersoncolumntablemiddlenotshouldhaveduplicateuser_id'
        );
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
