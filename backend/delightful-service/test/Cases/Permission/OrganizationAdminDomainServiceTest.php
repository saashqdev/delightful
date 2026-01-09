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

        // 为每个test生成唯一的userID，避免test之间的数据冲突
        $this->testUserIds = [
            'test_domain_user_' . uniqid(),
            'test_domain_user_' . uniqid(),
            'test_domain_user_' . uniqid(),
        ];

        // 清理可能存在的test数据
        $this->cleanUpTestData();
    }

    protected function tearDown(): void
    {
        // 清理test数据
        $this->cleanUpTestData();

        parent::tearDown();
    }

    public function testGetAllOrganizationAdminsWithNoAdminsReturnsEmptyArray(): void
    {
        // 确保没有organization管理员数据
        $this->cleanUpTestData();

        // callmethod
        $result = $this->organizationAdminDomainService->getAllOrganizationAdmins($this->createDataIsolation($this->testOrganizationCode));

        // 验证result
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetAllOrganizationAdminsWithSingleAdminReturnsOneEntity(): void
    {
        // create一个organization管理员
        $organizationAdmin = $this->organizationAdminDomainService->grant(
            $this->createDataIsolation($this->testOrganizationCode),
            $this->testUserIds[0],
            $this->testGrantorUserId,
            'Test single admin'
        );

        // callmethod
        $result = $this->organizationAdminDomainService->getAllOrganizationAdmins($this->createDataIsolation($this->testOrganizationCode));

        // 验证result
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
        // create多个organization管理员
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

        // 验证result
        $this->assertIsArray($result);
        $this->assertCount(3, $result);

        // 验证每个return的实体
        $userIds = array_map(fn ($entity) => $entity->getUserId(), $result);
        foreach ($this->testUserIds as $testUserId) {
            $this->assertContains($testUserId, $userIds);
        }

        // 验证所有实体都是正确的type和organization代码
        foreach ($result as $entity) {
            $this->assertInstanceOf(OrganizationAdminEntity::class, $entity);
            $this->assertEquals($this->testOrganizationCode, $entity->getOrganizationCode());
            $this->assertEquals($this->testGrantorUserId, $entity->getGrantorUserId());
            $this->assertTrue($entity->isEnabled());
        }
    }

    public function testGetAllOrganizationAdminsOnlyReturnsAdminsFromSpecificOrganization(): void
    {
        // 在testorganization中create管理员
        $testOrgAdmin = $this->organizationAdminDomainService->grant(
            $this->createDataIsolation($this->testOrganizationCode),
            $this->testUserIds[0],
            $this->testGrantorUserId,
            'Test org admin'
        );

        // 在另一个organization中create管理员
        $anotherOrgAdmin = $this->organizationAdminDomainService->grant(
            $this->createDataIsolation($this->anotherOrganizationCode),
            $this->testUserIds[1],
            $this->testGrantorUserId,
            'Another org admin'
        );

        // callmethodgettestorganization的管理员
        $testOrgResult = $this->organizationAdminDomainService->getAllOrganizationAdmins($this->createDataIsolation($this->testOrganizationCode));

        // 验证只returntestorganization的管理员
        $this->assertIsArray($testOrgResult);
        $this->assertCount(1, $testOrgResult);
        $this->assertEquals($this->testUserIds[0], $testOrgResult[0]->getUserId());
        $this->assertEquals($this->testOrganizationCode, $testOrgResult[0]->getOrganizationCode());

        // callmethodget另一个organization的管理员
        $anotherOrgResult = $this->organizationAdminDomainService->getAllOrganizationAdmins($this->createDataIsolation($this->anotherOrganizationCode));

        // 验证只return另一个organization的管理员
        $this->assertIsArray($anotherOrgResult);
        $this->assertCount(1, $anotherOrgResult);
        $this->assertEquals($this->testUserIds[1], $anotherOrgResult[0]->getUserId());
        $this->assertEquals($this->anotherOrganizationCode, $anotherOrgResult[0]->getOrganizationCode());
    }

    public function testGetAllOrganizationAdminsWithEmptyOrganizationCodeReturnsEmptyArray(): void
    {
        // create一些管理员
        $this->organizationAdminDomainService->grant(
            $this->createDataIsolation($this->testOrganizationCode),
            $this->testUserIds[0],
            $this->testGrantorUserId
        );

        // 使用空的organization代码callmethod
        $result = $this->organizationAdminDomainService->getAllOrganizationAdmins($this->createDataIsolation(''));

        // 验证result为空
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetAllOrganizationAdminsWithNonExistentOrganizationCodeReturnsEmptyArray(): void
    {
        // create一些管理员
        $this->organizationAdminDomainService->grant(
            $this->createDataIsolation($this->testOrganizationCode),
            $this->testUserIds[0],
            $this->testGrantorUserId
        );

        // 使用不存在的organization代码callmethod
        $result = $this->organizationAdminDomainService->getAllOrganizationAdmins($this->createDataIsolation('non_existent_org_code'));

        // 验证result为空
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetAllOrganizationAdminsReturnsEntitiesWithAllRequiredFields(): void
    {
        // create一个organization管理员
        $organizationAdmin = $this->organizationAdminDomainService->grant(
            $this->createDataIsolation($this->testOrganizationCode),
            $this->testUserIds[0],
            $this->testGrantorUserId,
            'Test complete data'
        );

        // callmethod
        $result = $this->organizationAdminDomainService->getAllOrganizationAdmins($this->createDataIsolation($this->testOrganizationCode));

        // 验证return的实体包含所有必要field
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
        // create一个普通管理员（非organizationcreate者）
        $normalAdmin = $this->organizationAdminDomainService->grant(
            $this->createDataIsolation($this->testOrganizationCode),
            $this->testUserIds[0],
            $this->testGrantorUserId,
            'Normal admin',
            false
        );

        $this->assertFalse($normalAdmin->isOrganizationCreator());

        // create一个organizationcreate者
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
        // create一个organizationcreate者
        $creatorAdmin = $this->organizationAdminDomainService->grant(
            $this->createDataIsolation($this->testOrganizationCode),
            $this->testUserIds[0],
            $this->testGrantorUserId,
            'Organization creator',
            true
        );

        // 通过servicemethodcheck是否为organizationcreate者
        $this->assertTrue($this->organizationAdminDomainService->isOrganizationCreator(
            $this->createDataIsolation($this->testOrganizationCode),
            $this->testUserIds[0]
        ));

        // check不存在的user
        $this->assertFalse($this->organizationAdminDomainService->isOrganizationCreator(
            $this->createDataIsolation($this->testOrganizationCode),
            'non_existent_user'
        ));
    }

    public function testGetOrganizationCreatorReturnsCorrectEntity(): void
    {
        // create多个管理员，其中一个是organizationcreate者
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
            // 清理testorganization的数据
            $this->cleanUpOrganizationAdmins($this->testOrganizationCode);

            // 清理另一个organization的数据
            $this->cleanUpOrganizationAdmins($this->anotherOrganizationCode);
        } catch (Exception $e) {
            // 忽略清理error
        }
    }

    private function cleanUpOrganizationAdmins(string $organizationCode): void
    {
        try {
            // get所有管理员并delete
            $allAdmins = $this->organizationAdminDomainService->getAllOrganizationAdmins($this->createDataIsolation($organizationCode));
            foreach ($allAdmins as $admin) {
                $this->organizationAdminDomainService->destroy($this->createDataIsolation($organizationCode), $admin);
            }

            // 清理特定testuserID
            foreach ($this->testUserIds as $userId) {
                $organizationAdmin = $this->organizationAdminDomainService->getByUserId($this->createDataIsolation($organizationCode), $userId);
                if ($organizationAdmin) {
                    $this->organizationAdminDomainService->destroy($this->createDataIsolation($organizationCode), $organizationAdmin);
                }
            }
        } catch (Exception $e) {
            // 忽略清理error
        }
    }
}
