<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Contact\UserSetting;

use App\Application\Permission\Service\OperationPermissionAppService;
use App\Domain\Contact\Entity\DelightfulUserSettingEntity;
use App\Domain\File\Service\FileDomainService;
use App\Domain\MCP\Entity\ValueObject\MCPDataIsolation;
use App\Domain\MCP\Entity\ValueObject\Query\MCPServerQuery;
use App\Domain\MCP\Service\MCPServerDomainService;
use App\Domain\Permission\Entity\ValueObject\OperationPermission\ResourceType;
use App\Infrastructure\Core\DataIsolation\BaseDataIsolation;
use App\Infrastructure\Core\ValueObject\Page;
use DateTime;

class BeDelightfulMCPServerHandler extends AbstractUserSettingHandler
{
    public function __construct(
        protected OperationPermissionAppService $operationPermissionAppService,
        protected MCPServerDomainService $MCPServerDomainService,
        protected FileDomainService $fileDomainService,
    ) {
    }

    public function populateValue(BaseDataIsolation $dataIsolation, DelightfulUserSettingEntity $setting): void
    {
        $mcpDataIsolation = MCPDataIsolation::createByBaseDataIsolation($dataIsolation);

        $mcpServerIds = array_column($setting->getValue()['servers'] ?? [], 'id');

        $servers = [];

        // organizationinsidehavepermission的data
        $resources = $this->operationPermissionAppService->getResourceOperationByUserIds(
            $mcpDataIsolation,
            ResourceType::MCPServer,
            [$mcpDataIsolation->getCurrentUserId()]
        )[$mcpDataIsolation->getCurrentUserId()] ?? [];
        $resourceIds = array_keys($resources);

        $query = new MCPServerQuery();
        $query->setCodes($mcpServerIds);

        $data = $this->MCPServerDomainService->queries($mcpDataIsolation->disabled(), $query, Page::createNoPage());
        foreach ($data['list'] ?? [] as $item) {
            if (in_array($item->getOrganizationCode(), $dataIsolation->getOfficialOrganizationCodes(), true) || in_array($item->getCode(), $resourceIds, true)) {
                $servers[] = [
                    'id' => $item->getCode(),
                    'name' => $item->getName(),
                    'description' => $item->getDescription(),
                    'icon' => $this->fileDomainService->getLink($item->getOrganizationCode(), $item->getIcon())?->getUrl() ?? '',
                ];
            }
        }

        $setting->setValue(['servers' => $servers]);
    }

    public function generateDefault(): ?DelightfulUserSettingEntity
    {
        $setting = new DelightfulUserSettingEntity();
        $setting->setKey(UserSettingKey::BeDelightfulMCPServers->value);
        $setting->setValue(['servers' => []]);
        $setting->setCreatedAt(new DateTime());
        $setting->setUpdatedAt(new DateTime());
        return $setting;
    }
}
