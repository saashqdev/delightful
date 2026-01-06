<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Permission\Event\Subscribe;

use App\Domain\Agent\Entity\DelightfulAgentEntity;
use App\Domain\Agent\Event\DelightfulAgentSavedEvent;
use App\Domain\Flow\Entity\DelightfulFlowEntity;
use App\Domain\Flow\Entity\DelightfulFlowToolSetEntity;
use App\Domain\Flow\Entity\ValueObject\Type;
use App\Domain\Flow\Event\DelightfulFLowSavedEvent;
use App\Domain\Flow\Event\DelightfulFLowToolSetSavedEvent;
use App\Domain\KnowledgeBase\Entity\KnowledgeBaseEntity;
use App\Domain\KnowledgeBase\Event\KnowledgeBaseSavedEvent;
use App\Domain\MCP\Entity\MCPServerEntity;
use App\Domain\MCP\Event\MCPServerSavedEvent;
use App\Domain\Permission\Entity\ValueObject\OperationPermission\ResourceType;
use App\Domain\Permission\Entity\ValueObject\PermissionDataIsolation;
use App\Domain\Permission\Service\OperationPermissionDomainService;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Psr\Container\ContainerInterface;

#[Listener]
readonly class AccessOwnerSubscriber implements ListenerInterface
{
    private OperationPermissionDomainService $operationPermissionDomainService;

    public function __construct(private ContainerInterface $container)
    {
        $this->operationPermissionDomainService = $this->container->get(OperationPermissionDomainService::class);
    }

    public function listen(): array
    {
        return [
            DelightfulAgentSavedEvent::class,
            DelightfulFLowSavedEvent::class,
            DelightfulFLowToolSetSavedEvent::class,
            KnowledgeBaseSavedEvent::class,
            MCPServerSavedEvent::class,
        ];
    }

    public function process(object $event): void
    {
        if ($event instanceof DelightfulAgentSavedEvent) {
            $this->handleAgent($event->agentEntity, $event->create);
        }
        if ($event instanceof DelightfulFLowSavedEvent) {
            $this->handleFlow($event->flow, $event->create);
        }
        if ($event instanceof DelightfulFLowToolSetSavedEvent) {
            $this->handleToolSet($event->toolSetEntity, $event->create);
        }
        if ($event instanceof KnowledgeBaseSavedEvent) {
            $this->handleKnowledge($event->delightfulFlowKnowledgeEntity, $event->create);
        }
        if ($event instanceof MCPServerSavedEvent) {
            $this->handleMCPServer($event->MCPServerEntity, $event->create);
        }
    }

    private function handleAgent(DelightfulAgentEntity $agentEntity, bool $create): void
    {
        $permissionDataIsolation = PermissionDataIsolation::create($agentEntity->getOrganizationCode(), $agentEntity->getCreatedUid());
        if ($create) {
            $this->operationPermissionDomainService->accessOwner(
                $permissionDataIsolation,
                ResourceType::AgentCode,
                $agentEntity->getId(),
                $agentEntity->getCreatedUid()
            );
        }
    }

    private function handleFlow(DelightfulFlowEntity $flowEntity, bool $create): void
    {
        $permissionDataIsolation = PermissionDataIsolation::create($flowEntity->getOrganizationCode(), $flowEntity->getCreator());
        if ($flowEntity->getType() === Type::Sub && $create) {
            $this->operationPermissionDomainService->accessOwner(
                $permissionDataIsolation,
                ResourceType::SubFlowCode,
                $flowEntity->getCode(),
                $flowEntity->getCreator()
            );
        }
    }

    private function handleToolSet(DelightfulFlowToolSetEntity $toolSetEntity, bool $create): void
    {
        $permissionDataIsolation = PermissionDataIsolation::create($toolSetEntity->getOrganizationCode(), $toolSetEntity->getCreator());
        if ($create) {
            $this->operationPermissionDomainService->accessOwner(
                $permissionDataIsolation,
                ResourceType::ToolSet,
                $toolSetEntity->getCode(),
                $toolSetEntity->getCreator()
            );
        }
    }

    private function handleKnowledge(KnowledgeBaseEntity $knowledgeEntity, bool $create): void
    {
        $permissionDataIsolation = PermissionDataIsolation::create($knowledgeEntity->getOrganizationCode(), $knowledgeEntity->getCreator());
        if ($create) {
            $this->operationPermissionDomainService->accessOwner(
                $permissionDataIsolation,
                ResourceType::Knowledge,
                $knowledgeEntity->getCode(),
                $knowledgeEntity->getCreator()
            );
        }
    }

    private function handleMCPServer(MCPServerEntity $MCPServerEntity, bool $create): void
    {
        $permissionDataIsolation = PermissionDataIsolation::create($MCPServerEntity->getOrganizationCode(), $MCPServerEntity->getCreator());
        if ($create) {
            $this->operationPermissionDomainService->accessOwner(
                $permissionDataIsolation,
                ResourceType::MCPServer,
                $MCPServerEntity->getCode(),
                $MCPServerEntity->getCreator()
            );
        }
    }
}
