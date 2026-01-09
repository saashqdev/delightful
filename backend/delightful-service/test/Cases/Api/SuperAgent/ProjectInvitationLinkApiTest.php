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
 * projectinvitationlinkAPItest
 */
class ProjectInvitationLinkApiTest extends AbstractApiTest
{
    private const BASE_URI = '/api/v1/be-agent/projects';

    private const INVITATION_BASE_URI = '/api/v1/be-agent/invitation';

    private string $projectId = '816065897791012866';

    // testproceduremiddlegenerateinvitationlinkinfo
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
     * testinvitationlinkcompleteprocess.
     */
    public function testInvitationLinkCompleteFlow(): void
    {
        $projectId = $this->projectId;

        // 0. cleanuptestdata - ensuretest2usernotisprojectmember
        $this->cleanupTestData($projectId);

        // 1. project所have者startinvitationlink
        $this->switchUserTest1();
        $this->assertToggleInvitationLinkOn($projectId);

        // 2. getinvitationlinkinfo
        $linkInfo = $this->getInvitationLink($projectId);
        $this->invitationToken = $linkInfo['data']['token'];

        // 3. settingpasswordprotected
        $this->assertSetPasswordProtection($projectId, true);

        // 4. outside部userpassTokengetinvitationinfo
        $this->switchUserTest2();
        $invitationInfo = $this->getInvitationByToken($this->invitationToken);
        $this->assertTrue($invitationInfo['data']['requires_password']);

        // 5. outside部usertryadd入project(passworderror)
        $this->joinProjectWithWrongPassword($this->invitationToken);

        // 6. project所have者resetpassword
        $this->switchUserTest1();
        $passwordInfo = $this->resetInvitationPassword($projectId);
        $this->invitationPassword = $passwordInfo['data']['password'];

        // 7. outside部userusecorrectpasswordadd入project
        $this->switchUserTest2();
        $this->joinProjectSuccess($this->invitationToken, $this->invitationPassword);

        // 8. validateuseralreadybecomeforprojectmember(againtimeadd入shouldfail)
        $this->joinProjectAlreadyMember($this->invitationToken, $this->invitationPassword);

        // 9. project所have者closeinvitationlink
        $this->switchUserTest1();
        $this->assertToggleInvitationLinkOff($projectId);

        // 10. outside部usertryaccessalreadycloseinvitationlink
        $this->switchUserTest2();
        $this->getInvitationByTokenDisabled($this->invitationToken);
    }

    /**
     * testinvitationlinkpermissioncontrol.
     */
    public function testInvitationLinkPermissions(): void
    {
        $projectId = $this->projectId;

        // 1. nonprojectmembertrymanageinvitationlink(shouldfail)
        $this->switchUserTest2();
        $this->getInvitationLink($projectId, 51202); // permissionnot足

        // 2. project所have者canmanageinvitationlink
        $this->switchUserTest1();
        $this->getInvitationLink($projectId, 1000); // success
    }

    /**
     * testpermissionlevel别manage.
     */
    public function testPermissionLevelManagement(): void
    {
        $projectId = $this->projectId;

        $this->switchUserTest1();

        // 1. startinvitationlink
        $this->toggleInvitationLink($projectId, true);

        // 2. testmodifypermissionlevel别formanagepermission
        $this->updateInvitationPermission($projectId, 'manage');

        // 3. testmodifypermissionlevel别foreditpermission
        $this->updateInvitationPermission($projectId, 'editor');

        // 4. testmodifypermissionlevel别forviewpermission
        $this->updateInvitationPermission($projectId, 'viewer');
    }

    /**
     * getinvitationlinkinfo.
     */
    public function getInvitationLink(string $projectId, int $expectedCode = 1000): array
    {
        $response = $this->client->get(
            self::BASE_URI . "/{$projectId}/invitation-links",
            [],
            $this->getCommonHeaders()
        );

        $this->assertNotNull($response, 'responsenotshouldfornull');
        $this->assertEquals($expectedCode, $response['code'], $response['message'] ?? '');

        return $response;
    }

    /**
     * start/closeinvitationlink.
     */
    public function toggleInvitationLink(string $projectId, bool $enabled, int $expectedCode = 1000): array
    {
        $response = $this->client->put(
            self::BASE_URI . "/{$projectId}/invitation-links/toggle",
            ['enabled' => $enabled],
            $this->getCommonHeaders()
        );

        $this->assertNotNull($response, 'responsenotshouldfornull');
        $this->assertEquals($expectedCode, $response['code'], $response['message'] ?? '');

        return $response;
    }

    /**
     * resetinvitationlink.
     */
    public function resetInvitationLink(string $projectId, int $expectedCode = 1000): array
    {
        $response = $this->client->post(
            self::BASE_URI . "/{$projectId}/invitation-links/reset",
            [],
            $this->getCommonHeaders()
        );

        $this->assertNotNull($response, 'responsenotshouldfornull');
        $this->assertEquals($expectedCode, $response['code'], $response['message'] ?? '');

        return $response;
    }

    /**
     * settingpasswordprotected.
     */
    public function setInvitationPassword(string $projectId, bool $enabled, int $expectedCode = 1000): array
    {
        $response = $this->client->post(
            self::BASE_URI . "/{$projectId}/invitation-links/password",
            ['enabled' => $enabled],
            $this->getCommonHeaders()
        );

        $this->assertNotNull($response, 'responsenotshouldfornull');
        $this->assertEquals($expectedCode, $response['code'], $response['message'] ?? '');

        return $response;
    }

    /**
     * resetpassword
     */
    public function resetInvitationPassword(string $projectId, int $expectedCode = 1000): array
    {
        $response = $this->client->post(
            self::BASE_URI . "/{$projectId}/invitation-links/reset-password",
            [],
            $this->getCommonHeaders()
        );

        $this->assertNotNull($response, 'responsenotshouldfornull');
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

        $this->assertNotNull($response, 'responsenotshouldfornull');
        $this->assertEquals($expectedCode, $response['code'], $response['message'] ?? '');

        if ($expectedCode === 1000) {
            $this->assertEquals($permission, $response['data']['default_join_permission']);
        }

        return $response;
    }

    /**
     * passTokengetinvitationinfo.
     */
    public function getInvitationByToken(string $token, int $expectedCode = 1000): array
    {
        $response = $this->client->get(
            self::INVITATION_BASE_URI . "/links/{$token}",
            [],
            $this->getCommonHeaders()
        );

        $this->assertNotNull($response, 'responsenotshouldfornull');
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
     * getdisabledinvitationlink(shouldfail).
     */
    public function getInvitationByTokenDisabled(string $token): void
    {
        $response = $this->getInvitationByToken($token, 51222); // invitationlinkdisabled
    }

    /**
     * add入project(passworderror).
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

        $this->assertEquals(51220, $response['code']); // passworderror
    }

    /**
     * successadd入project.
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
     * tryduplicateadd入project(shouldfail).
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

        $this->assertEquals(51225, $response['code']); // already经isprojectmember
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

        $this->assertNotNull($response, 'responsenotshouldfornull');
        $this->assertEquals(51215, $response['code']); // invalidpermissionlevel别
    }

    /**
     * testandhair操as.
     */
    public function testConcurrentOperations(): void
    {
        $projectId = $this->projectId;
        $this->switchUserTest1();

        // cleanuptestdata
        $this->getResourceShareDomainService()->deleteShareByResource($projectId, ResourceType::ProjectInvitation->value);

        // continuousfastspeedstart/closeinvitationlink
        $this->toggleInvitationLink($projectId, true);
        $this->toggleInvitationLink($projectId, false);
        $this->toggleInvitationLink($projectId, true);

        // validatefinalstatus
        $response = $this->getInvitationLink($projectId);
        $this->assertEquals(1000, $response['code']);

        // validatelinkisenablestatus(compatiblenumberandbooleanvalue)
        $isEnabled = $response['data']['is_enabled'];
        $this->assertTrue($isEnabled === true || $isEnabled === 1, 'invitationlinkshouldisenablestatus');
    }

    /**
     * testpasswordsecurityproperty.
     */
    public function testPasswordSecurity(): void
    {
        $projectId = $this->projectId;
        $this->switchUserTest1();

        // 1. startinvitationlink
        $this->toggleInvitationLink($projectId, true);

        // 2. 多timesettingpasswordprotected,validatepasswordgenerate
        $password1 = $this->setInvitationPassword($projectId, true);
        $password2 = $this->resetInvitationPassword($projectId);
        $password3 = $this->resetInvitationPassword($projectId);

        // validateeachtimegeneratepasswordalldifferent
        $this->assertNotEquals($password1['data']['password'] ?? '', $password2['data']['password']);
        $this->assertNotEquals($password2['data']['password'], $password3['data']['password']);

        // validatepasswordlengthandformat
        $password = $password3['data']['password'];
        $this->assertEquals(5, strlen($password)); // passwordlengthshouldis5位
        $this->assertMatchesRegularExpression('/^\d{5}$/', $password); // onlycontain5位number
    }

    /**
     * testpasswordprotectedswitchfeature - closebackagainstartpasswordnotwillmore改.
     */
    public function testPasswordTogglePreservation(): void
    {
        $projectId = $this->projectId;
        $this->switchUserTest1();

        // 0. cleanuptestdata
        $this->cleanupTestData($projectId);

        // 1. startinvitationlink
        $this->toggleInvitationLink($projectId, true);

        // 2. settingpasswordprotected
        $initialPasswordResponse = $this->setInvitationPassword($projectId, true);
        $this->assertEquals(1000, $initialPasswordResponse['code']);

        $originalPassword = $initialPasswordResponse['data']['password'];
        $this->assertNotEmpty($originalPassword);
        $this->assertEquals(5, strlen($originalPassword));

        // 3. closepasswordprotected
        $disableResponse = $this->setInvitationPassword($projectId, false);
        $this->assertEquals(1000, $disableResponse['code']);

        // 4. validateclosestatusdownaccesslinknotneedpassword
        $linkResponse = $this->getInvitationLink($projectId);
        $this->assertEquals(1000, $linkResponse['code']);
        $token = $linkResponse['data']['token'];

        $linkInfo = $this->getInvitationByToken($token);
        $this->assertEquals(1000, $linkInfo['code']);
        $this->assertFalse($linkInfo['data']['requires_password']);

        // 5. 重newstartpasswordprotected
        $enableResponse = $this->setInvitationPassword($projectId, true);
        $this->assertEquals(1000, $enableResponse['code']);

        // 6. validatepasswordmaintainnot变
        $restoredPassword = $enableResponse['data']['password'];
        $this->assertEquals($originalPassword, $restoredPassword, '重newstartpasswordprotectedback,passwordshouldmaintainnot变');

        // 7. validatestartstatusdownaccesslinkneedpassword
        $linkInfo = $this->getInvitationByToken($token);
        $this->assertEquals(1000, $linkInfo['code']);
        $this->assertTrue($linkInfo['data']['requires_password']);

        // 8. validateuse原passwordcansuccessadd入project
        $this->switchUserTest2();
        $joinResult = $this->joinProjectSuccess($token, $originalPassword);
        $this->assertEquals(1000, $joinResult['code']);
        $this->assertEquals('viewer', $joinResult['data']['user_role']);
    }

    /**
     * testpasswordmodifyfeature.
     */
    public function testChangePassword(): void
    {
        $projectId = $this->projectId;
        $this->switchUserTest1();

        // 0. cleanuptestdataandresetinvitationlink
        $this->cleanupTestData($projectId);

        // passdomainservicedelete现haveinvitationlink
        $this->getResourceShareDomainService()->deleteShareByResource($projectId, ResourceType::ProjectInvitation->value);

        // 1. startinvitationlink
        $this->toggleInvitationLink($projectId, true);

        // 2. settinginitialpasswordprotected
        $initialPasswordResponse = $this->setInvitationPassword($projectId, true);
        $this->assertEquals(1000, $initialPasswordResponse['code']);

        $originalPassword = $initialPasswordResponse['data']['password'];
        $this->assertEquals(5, strlen($originalPassword)); // validatepasswordlengthfor5位
        $this->assertMatchesRegularExpression('/^\d{5}$/', $originalPassword); // validateis5位number

        // 3. modifypasswordforcustomizepassword
        $customPassword = 'mypass123';
        $changePasswordResponse = $this->changeInvitationPassword($projectId, $customPassword);
        $this->assertEquals(1000, $changePasswordResponse['code']);
        $this->assertEquals($customPassword, $changePasswordResponse['data']['password']);

        // 4. validate原passwordnotcanuse
        $linkResponse = $this->getInvitationLink($projectId);
        $token = $linkResponse['data']['token'];

        $this->switchUserTest2();
        // use原passwordshouldfail
        $data = ['token' => $token, 'password' => $originalPassword];
        $response = $this->client->post(
            self::INVITATION_BASE_URI . '/join',
            $data,
            $this->getCommonHeaders()
        );
        $this->assertEquals(51220, $response['code'], 'errorpasswordshouldreturn51220error码');

        // 5. validatenewpasswordcannormaluse
        $joinResult = $this->joinProjectSuccess($token, $customPassword);
        $this->assertEquals(1000, $joinResult['code']);
        $this->assertEquals('viewer', $joinResult['data']['user_role']);

        // 6. cleanuptestdata
        $this->cleanupTestData($projectId);

        // 7. testinvalidpasswordformat(nullstringand超longpassword)
        $this->switchUserTest1();
        $this->toggleInvitationLink($projectId, true);

        // testnullpassword
        $response = $this->changeInvitationPassword($projectId, '', 51220);
        $this->assertEquals(51220, $response['code']);

        // test超longpassword(19位)
        $response = $this->changeInvitationPassword($projectId, str_repeat('1', 19), 51220);
        $this->assertEquals(51220, $response['code']);

        // 8. testvalideachtypepasswordformat
        $validPasswords = ['123', '12345', 'abcde', '12a34', str_repeat('x', 18)];
        foreach ($validPasswords as $validPassword) {
            $response = $this->changeInvitationPassword($projectId, $validPassword, 1000);
            $this->assertEquals(1000, $response['code']);
            $this->assertEquals($validPassword, $response['data']['password']);
        }
    }

    /**
     * testinvitationlinkuserstatusandcreate者info.
     */
    public function testInvitationLinkUserStatusAndCreatorInfo(): void
    {
        $projectId = $this->projectId;

        // 0. ensureswitchtotest1userandcleanuptestdata
        $this->switchUserTest1();
        $this->cleanupTestData($projectId);
        $this->getResourceShareDomainService()->deleteShareByResource($projectId, ResourceType::ProjectInvitation->value, '', true);

        // 1. projectcreate者(test1)startinvitationlink
        $this->toggleInvitationLink($projectId, true);

        // getinvitationlinkinfo
        $linkResponse = $this->getInvitationLink($projectId);
        $token = $linkResponse['data']['token'];

        // 2. testcreate者accessinvitationlink - should show has_joined = true
        $this->switchUserTest1();
        $invitationInfo = $this->getInvitationByToken($token);

        // checkbasicresponsestructure
        $this->assertEquals(1000, $invitationInfo['code'], 'getinvitationinfoshouldsuccess');
        $this->assertIsArray($invitationInfo['data'], 'responsedatashouldisarray');

        // checknewfieldwhether存in
        $this->assertArrayHasKey('has_joined', $invitationInfo['data'], 'responseshouldcontainhas_joinedfield');
        $this->assertArrayHasKey('creator_name', $invitationInfo['data'], 'responseshouldcontaincreator_namefield');
        $this->assertArrayHasKey('creator_avatar', $invitationInfo['data'], 'responseshouldcontaincreator_avatarfield');
        $this->assertArrayHasKey('creator_id', $invitationInfo['data'], 'responseshouldcontaincreator_idfield');

        // checkfieldvalue
        $this->assertTrue($invitationInfo['data']['has_joined'], 'create者shoulddisplayalreadyadd入project');
        $this->assertNotEmpty($invitationInfo['data']['creator_id'], 'creator_idnotshouldfornull');

        // validatefieldtype
        $this->assertIsBool($invitationInfo['data']['has_joined'], 'has_joinedshouldisbooleantype');
        $this->assertIsString($invitationInfo['data']['creator_name'], 'creator_nameshouldisstringtype');
        $this->assertIsString($invitationInfo['data']['creator_avatar'], 'creator_avatarshouldisstringtype');

        // 3. testnotadd入useraccessinvitationlink - should show has_joined = false
        $this->switchUserTest2();
        $invitationInfo = $this->getInvitationByToken($token);

        // checkbasicresponse
        $this->assertEquals(1000, $invitationInfo['code'], 'notadd入usergetinvitationinfoshouldsuccess');
        $this->assertArrayHasKey('has_joined', $invitationInfo['data'], 'responseshouldcontainhas_joinedfield');
        $this->assertFalse($invitationInfo['data']['has_joined'], 'notadd入usershoulddisplaynotadd入project');

        // validatecreate者info依然存in(notno matter whoaccess,create者infoallshoulddisplay)
        $this->assertArrayHasKey('creator_name', $invitationInfo['data'], 'responseshouldcontaincreator_namefield');
        $this->assertArrayHasKey('creator_avatar', $invitationInfo['data'], 'responseshouldcontaincreator_avatarfield');

        // 4. test2useradd入project - needprovidepassword
        $password = $linkResponse['data']['password'] ?? null;
        $joinResult = $this->joinProjectSuccess($token, $password);
        $this->assertEquals(1000, $joinResult['code']);

        // 5. testalreadyadd入memberaccessinvitationlink - should show has_joined = true
        $invitationInfo = $this->getInvitationByToken($token);

        $this->assertEquals(1000, $invitationInfo['code'], 'alreadyadd入membergetinvitationinfoshouldsuccess');
        $this->assertArrayHasKey('has_joined', $invitationInfo['data'], 'responseshouldcontainhas_joinedfield');
        $this->assertTrue($invitationInfo['data']['has_joined'], 'alreadyadd入membershoulddisplayalreadyadd入project');

        // 6. validateresponsedatacompleteproperty
        $data = $invitationInfo['data'];
        $requiredFields = [
            'project_id', 'project_name', 'project_description',
            'organization_code', 'creator_id', 'creator_name', 'creator_avatar',
            'default_join_permission', 'requires_password', 'token', 'has_joined',
        ];

        foreach ($requiredFields as $field) {
            $this->assertArrayHasKey($field, $data, "responsedata应containfield: {$field}");
        }

        // 7. cleanuptestdataandswitchreturntest1user
        $this->switchUserTest1();
        $this->cleanupTestData($projectId);
        $this->getResourceShareDomainService()->deleteShareByResource($projectId, ResourceType::ProjectInvitation->value);
    }

    /**
     * startinvitationlink (私haveassistmethod).
     */
    private function assertToggleInvitationLinkOn(string $projectId): void
    {
        $response = $this->toggleInvitationLink($projectId, true);

        $this->assertEquals(1000, $response['code']);

        // validatelinkisenablestatus(compatiblenumberandbooleanvalue)
        $isEnabled = $response['data']['is_enabled'];
        $this->assertTrue($isEnabled === true || $isEnabled === 1, 'invitationlinkshouldisenablestatus');

        $this->assertNotEmpty($response['data']['token']);
        $this->assertEquals('viewer', $response['data']['default_join_permission']);
    }

    /**
     * closeinvitationlink (私haveassistmethod).
     */
    private function assertToggleInvitationLinkOff(string $projectId): void
    {
        $response = $this->toggleInvitationLink($projectId, false);

        $this->assertEquals(1000, $response['code']);

        // validatelinkisdisablestatus(compatiblenumberandbooleanvalue)
        $isEnabled = $response['data']['is_enabled'];
        $this->assertTrue($isEnabled === false || $isEnabled === 0, 'invitationlinkshouldisdisablestatus');
    }

    /**
     * settingpasswordprotected (私haveassistmethod).
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
     * modifyinvitationlinkpassword (私haveassistmethod).
     */
    private function changeInvitationPassword(string $projectId, string $password, int $expectedCode = 1000): array
    {
        $response = $this->client->put(
            self::BASE_URI . "/{$projectId}/invitation-links/change-password",
            ['password' => $password],
            $this->getCommonHeaders()
        );

        $this->assertNotNull($response, 'responsenotshouldfornull');
        $this->assertEquals($expectedCode, $response['code'], $response['message'] ?? '');

        return $response;
    }

    /**
     * cleanuptestdata.
     */
    private function cleanupTestData(string $projectId): void
    {
        // passdomainservicedeletetest2userprojectmemberclose系(if存in)
        $this->getProjectMemberDomainService()->removeMemberByUser((int) $projectId, 'usi_e9d64db5b986d062a342793013f682e8');
    }

    /**
     * getresourcesharedomainservice.
     */
    private function getResourceShareDomainService(): ResourceShareDomainService
    {
        return ApplicationContext::getContainer()
            ->get(ResourceShareDomainService::class);
    }

    /**
     * getprojectmemberdomainservice.
     */
    private function getProjectMemberDomainService(): ProjectMemberDomainService
    {
        return ApplicationContext::getContainer()
            ->get(ProjectMemberDomainService::class);
    }
}
