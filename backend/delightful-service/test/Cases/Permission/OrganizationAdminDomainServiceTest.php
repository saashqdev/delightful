<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace HyperfTest\Cases\Permission;

use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Domain\Permission\Entity\OrganizationAdminEntity;
use App\Domain\Permission\Service\OrganizationAdminDomainService;
use Exception;
use HyperfTest\HttpTestCase;

/**
 * @internal
 */
class OrganizationAdminDomainServiceTest extends HttpTestCase
{
    private OrganizationAdminDomainService $organizationAdminDomainService;

    private string $testOrganizationCode = 'test_domain_org_code';

    private string $anotherOrganizationCode = 'another_org_code';

    private array $testUserIds = [];

    private string $testGrantorUserId = 'test_grantor_domain_user_id';

    protected function setUp(): void
    {
        parent::setUp();
        $this->organizationAdminDomainService = $this->getContainer()->get(OrganizationAdminDomainService::class);

        // foreachtestgenerate唯oneuserID,avoidtestbetweendataconflict
        $this->testUserIds = [
            'test_domain_user_' . uniqid(),
            'test_domain_user_' . uniqid(),
            'test_domain_user_' . uniqid(),
        ];

        // cleanupmaybe存intestdata
        $this->cleanUpTestData();
    }

    protected function tearDown(): void
    {
        // cleanuptestdata
        $this->cleanUpTestData();

        parent::tearDown();
    }

    public function testGetAllOrganizationAdminsWithNoAdminsReturnsEmptyArray(): void
    {
        // ensurenothaveorganizationadministratordata
        $this->cleanUpTestData();

        // callmethod
        $result = $this->organizationAdminDomainService->getAllOrganizationAdmins($this->createDataIsolation($this->testOrganizationCode));

        // verifyresult
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetAllOrganizationAdminsWithSingleAdminReturnsOneEntity(): void
    {
        // createoneorganizationadministrator
        $organizationAdmin = $this->organizationAdminDomainService->grant(
            $this->createDataIsolation($this->testOrganizationCode),
            $this->testUserIds[0],
            $this->testGrantorUserId,
            'Test single admin'
        );

        // callmethod
        $result = $this->organizationAdminDomainService->getAllOrganizationAdmins($this->createDataIsolation($this->testOrganizationCode));

        // verifyresult
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(OrganizationAdminEntity::class, $result[0]);
        $this->assertEquals($this->testUserIds[0], $result[0]->getUserId());
        $this->assertEquals($this->testOrganizationCode, $result[0]->getOrganizationCode());
        $this->assertEquals($this->testGrantorUserId, $result[0]->getGrantorUserId());
        $this->assertEquals('Test single admin', $result[0]->getRemarks());
        $this->assertTrue($result[0]->isEnabled());
    }

    public function testGetAllOrganizationAdminsWithMultipleAdminsReturnsAllEntities(): void
    {
        // create多organizationadministrator
        $admins = [];
        foreach ($this->testUserIds as $index => $userId) {
            $admins[] = $this->organizationAdminDomainService->grant(
                $this->createDataIsolation($this->testOrganizationCode),
                $userId,
                $this->testGrantorUserId,
                "Test admin #{$index}"
            );
        }

        // callmethod
        $result = $this->organizationAdminDomainService->getAllOrganizationAdmins($this->createDataIsolation($this->testOrganizationCode));

        // verifyresult
        $this->assertIsArray($result);
        $this->assertCount(3, $result);

        // verifyeachreturn实body
        $userIds = array_map(fn ($entity) => $entity->getUserId(), $result);
        foreach ($this->testUserIds as $testUserId) {
            $this->assertContains($testUserId, $userIds);
        }

        // verify所have实bodyalliscorrecttypeandorganizationcode
        foreach ($result as $entity) {
            $this->assertInstanceOf(OrganizationAdminEntity::class, $entity);
            $this->assertEquals($this->testOrganizationCode, $entity->getOrganizationCode());
            $this->assertEquals($this->testGrantorUserId, $entity->getGrantorUserId());
            $this->assertTrue($entity->isEnabled());
        }
    }

    public function testGetAllOrganizationAdminsOnlyReturnsAdminsFromSpecificOrganization(): void
    {
        // intestorganizationmiddlecreateadministrator
        $testOrgAdmin = $this->organizationAdminDomainService->grant(
            $this->createDataIsolation($this->testOrganizationCode),
            $this->testUserIds[0],
            $this->testGrantorUserId,
            'Test org admin'
        );

        // in另oneorganizationmiddlecreateadministrator
        $anotherOrgAdmin = $this->organizationAdminDomainService->grant(
            $this->createDataIsolation($this->anotherOrganizationCode),
            $this->testUserIds[1],
            $this->testGrantorUserId,
            'Another org admin'
        );

        // callmethodgettestorganizationadministrator
        $testOrgResult = $this->organizationAdminDomainService->getAllOrganizationAdmins($this->createDataIsolation($this->testOrganizationCode));

        // verifyonlyreturntestorganizationadministrator
        $this->assertIsArray($testOrgResult);
        $this->assertCount(1, $testOrgResult);
        $this->assertEquals($this->testUserIds[0], $testOrgResult[0]->getUserId());
        $this->assertEquals($this->testOrganizationCode, $testOrgResult[0]->getOrganizationCode());

        // callmethodget另oneorganizationadministrator
        $anotherOrgResult = $this->organizationAdminDomainService->getAllOrganizationAdmins($this->createDataIsolation($this->anotherOrganizationCode));

        // verifyonlyreturn另oneorganizationadministrator
        $this->assertIsArray($anotherOrgResult);
        $this->assertCount(1, $anotherOrgResult);
        $this->assertEquals($this->testUserIds[1], $anotherOrgResult[0]->getUserId());
        $this->assertEquals($this->anotherOrganizationCode, $anotherOrgResult[0]->getOrganizationCode());
    }

    public function testGetAllOrganizationAdminsWithEmptyOrganizationCodeReturnsEmptyArray(): void
    {
        // createonetheseadministrator
        $this->organizationAdminDomainService->grant(
            $this->createDataIsolation($this->testOrganizationCode),
            $this->testUserIds[0],
            $this->testGrantorUserId
        );

        // useemptyorganizationcodecallmethod
        $result = $this->organizationAdminDomainService->getAllOrganizationAdmins($this->createDataIsolation(''));

        // verifyresultforempty
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetAllOrganizationAdminsWithNonExistentOrganizationCodeReturnsEmptyArray(): void
    {
        // createonetheseadministrator
        $this->organizationAdminDomainService->grant(
            $this->createDataIsolation($this->testOrganizationCode),
            $this->testUserIds[0],
            $this->testGrantorUserId
        );

        // usenot存inorganizationcodecallmethod
        $result = $this->organizationAdminDomainService->getAllOrganizationAdmins($this->createDataIsolation('non_existent_org_code'));

        // verifyresultforempty
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetAllOrganizationAdminsReturnsEntitiesWithAllRequiredFields(): void
    {
        // createoneorganizationadministrator
        $organizationAdmin = $this->organizationAdminDomainService->grant(
            $this->createDataIsolation($this->testOrganizationCode),
            $this->testUserIds[0],
            $this->testGrantorUserId,
            'Test complete data'
        );

        // callmethod
        $result = $this->organizationAdminDomainService->getAllOrganizationAdmins($this->createDataIsolation($this->testOrganizationCode));

        // verifyreturn实bodycontain所have必wantfield
        $this->assertCount(1, $result);
        $entity = $result[0];

        $this->assertNotNull($entity->getId());
        $this->assertNotNull($entity->getUserId());
        $this->assertNotNull($entity->getOrganizationCode());
        $this->assertNotNull($entity->getGrantorUserId());
        $this->assertNotNull($entity->getGrantedAt());
        $this->assertNotNull($entity->getCreatedAt());
        $this->assertNotNull($entity->getUpdatedAt());
        $this->assertIsInt($entity->getStatus());
        $this->assertEquals('Test complete data', $entity->getRemarks());
        $this->assertIsBool($entity->isOrganizationCreator());
    }

    public function testGrantWithOrganizationCreatorFlagSetsIsOrganizationCreatorCorrectly(): void
    {
        // createone普通administrator(nonorganizationcreate者)
        $normalAdmin = $this->organizationAdminDomainService->grant(
            $this->createDataIsolation($this->testOrganizationCode),
            $this->testUserIds[0],
            $this->testGrantorUserId,
            'Normal admin',
            false
        );

        $this->assertFalse($normalAdmin->isOrganizationCreator());

        // createoneorganizationcreate者
        $creatorAdmin = $this->organizationAdminDomainService->grant(
            $this->createDataIsolation($this->testOrganizationCode),
            $this->testUserIds[1],
            $this->testGrantorUserId,
            'Organization creator',
            true
        );

        $this->assertTrue($creatorAdmin->isOrganizationCreator());
    }

    public function testIsOrganizationCreatorMethodReturnsCorrectValue(): void
    {
        // createoneorganizationcreate者
        $creatorAdmin = $this->organizationAdminDomainService->grant(
            $this->createDataIsolation($this->testOrganizationCode),
            $this->testUserIds[0],
            $this->testGrantorUserId,
            'Organization creator',
            true
        );

        // passservicemethodcheckwhetherfororganizationcreate者
        $this->assertTrue($this->organizationAdminDomainService->isOrganizationCreator(
            $this->createDataIsolation($this->testOrganizationCode),
            $this->testUserIds[0]
        ));

        // checknot存inuser
        $this->assertFalse($this->organizationAdminDomainService->isOrganizationCreator(
            $this->createDataIsolation($this->testOrganizationCode),
            'non_existent_user'
        ));
    }

    public function testGetOrganizationCreatorReturnsCorrectEntity(): void
    {
        // create多administrator,itsmiddleoneisorganizationcreate者
        $normalAdmin = $this->organizationAdminDomainService->grant(
            $this->createDataIsolation($this->testOrganizationCode),
            $this->testUserIds[0],
            $this->testGrantorUserId,
            'Normal admin',
            false
        );

        $creatorAdmin = $this->organizationAdminDomainService->grant(
            $this->createDataIsolation($this->testOrganizationCode),
            $this->testUserIds[1],
            $this->testGrantorUserId,
            'Organization creator',
            true
        );

        // getorganizationcreate者
        $foundCreator = $this->organizationAdminDomainService->getOrganizationCreator(
            $this->createDataIsolation($this->testOrganizationCode)
        );

        $this->assertNotNull($foundCreator);
        $this->assertEquals($this->testUserIds[1], $foundCreator->getUserId());
        $this->assertTrue($foundCreator->isOrganizationCreator());
    }

    private function createDataIsolation(string $organizationCode): DataIsolation
    {
        return DataIsolation::simpleMake($organizationCode);
    }

    private function cleanUpTestData(): void
    {
        try {
            // cleanuptestorganizationdata
            $this->cleanUpOrganizationAdmins($this->testOrganizationCode);

            // cleanup另oneorganizationdata
            $this->cleanUpOrganizationAdmins($this->anotherOrganizationCode);
        } catch (Exception $e) {
            // ignorecleanuperror
        }
    }

    private function cleanUpOrganizationAdmins(string $organizationCode): void
    {
        try {
            // get所haveadministratoranddelete
            $allAdmins = $this->organizationAdminDomainService->getAllOrganizationAdmins($this->createDataIsolation($organizationCode));
            foreach ($allAdmins as $admin) {
                $this->organizationAdminDomainService->destroy($this->createDataIsolation($organizationCode), $admin);
            }

            // cleanup特定testuserID
            foreach ($this->testUserIds as $userId) {
                $organizationAdmin = $this->organizationAdminDomainService->getByUserId($this->createDataIsolation($organizationCode), $userId);
                if ($organizationAdmin) {
                    $this->organizationAdminDomainService->destroy($this->createDataIsolation($organizationCode), $organizationAdmin);
                }
            }
        } catch (Exception $e) {
            // ignorecleanuperror
        }
    }
}
