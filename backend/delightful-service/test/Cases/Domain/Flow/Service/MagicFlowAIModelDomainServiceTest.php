<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace HyperfTest\Cases\Domain\Flow\Service;

use App\Domain\Flow\Entity\DelightfulFlowAIModelEntity;
use App\Domain\Flow\Entity\ValueObject\FlowDataIsolation;
use App\Domain\Flow\Entity\ValueObject\Query\DelightfulFlowAIModelQuery;
use App\Domain\Flow\Repository\Facade\DelightfulFlowAIModelRepositoryInterface;
use App\Domain\Flow\Service\DelightfulFlowAIModelDomainService;
use App\Infrastructure\Core\ValueObject\Page;
use HyperfTest\Cases\BaseTest;
use HyperfTest\Cases\Domain\Flow\Entity\MockDelightfulFlowAIModelEntity;

/**
 * @internal
 */
class DelightfulFlowAIModelDomainServiceTest extends BaseTest
{
    public function testSave()
    {
        $repository = $this->getRepositoryTemplate();
        $service = new DelightfulFlowAIModelDomainService($repository);
        $entity = MockDelightfulFlowAIModelEntity::createMockDelightfulFlowAIModelEntity('glm-4-9b');
        $entity->setId(null);
        $this->assertNull($entity->getId());
        $entity = $service->save(FlowDataIsolation::create('DT001'), $entity);
        $this->assertNotNull($entity->getId());
    }

    public function testGetByName()
    {
        $repository = $this->getRepositoryTemplate();
        $service = new DelightfulFlowAIModelDomainService($repository);
        $entity = $service->getByName(FlowDataIsolation::create(), 'test');
        $this->assertNull($entity);

        $entity = $service->getByName(FlowDataIsolation::create(), 'glm-4-9b');
        $this->assertNotEmpty($entity);
    }

    public function testQueries()
    {
        $repository = $this->getRepositoryTemplate();
        $service = new DelightfulFlowAIModelDomainService($repository);
        $query = new DelightfulFlowAIModelQuery();
        $page = new Page();
        $result = $service->queries(FlowDataIsolation::create(), $query, $page);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('list', $result);
    }

    private function getRepositoryTemplate(): DelightfulFlowAIModelRepositoryInterface
    {
        return new class implements DelightfulFlowAIModelRepositoryInterface {
            public function save(FlowDataIsolation $dataIsolation, DelightfulFlowAIModelEntity $magicFlowAIModelEntity): DelightfulFlowAIModelEntity
            {
                $magicFlowAIModelEntity->setId(123);
                return $magicFlowAIModelEntity;
            }

            public function getByName(FlowDataIsolation $dataIsolation, string $name): ?DelightfulFlowAIModelEntity
            {
                return MockDelightfulFlowAIModelEntity::createMockDelightfulFlowAIModelEntity($name);
            }

            public function queries(FlowDataIsolation $dataIsolation, DelightfulFlowAIModelQuery $query, Page $page): array
            {
                return [
                    'total' => 0,
                    'list' => [],
                ];
            }
        };
    }
}
