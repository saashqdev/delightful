<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Flow\Service;

use App\Domain\Flow\Entity\DelightfulFlowToolSetEntity;
use App\Domain\Flow\Entity\ValueObject\ConstValue;
use App\Domain\Flow\Entity\ValueObject\Query\DelightfulFlowToolSetQuery;
use App\Domain\Permission\Entity\ValueObject\OperationPermission\Operation;
use App\Domain\Permission\Entity\ValueObject\OperationPermission\ResourceType;
use App\Domain\Permission\Entity\ValueObject\PermissionDataIsolation;
use App\ErrorCode\PermissionErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Core\ValueObject\Page;
use Delightful\CloudFile\Kernel\Struct\FileLink;
use Hyperf\DbConnection\Annotation\Transactional;
use Qbhy\HyperfAuth\Authenticatable;

class DelightfulFlowToolSetAppService extends AbstractFlowAppService
{
    public function getByCode(Authenticatable $authorization, string $code): DelightfulFlowToolSetEntity
    {
        $dataIsolation = $this->createFlowDataIsolation($authorization);
        $permissionDataIsolation = $this->createPermissionDataIsolation($dataIsolation);
        $operation = $this->operationPermissionAppService->getOperationByResourceAndUser(
            $permissionDataIsolation,
            ResourceType::ToolSet,
            $code,
            $authorization->getId()
        );
        if (! $operation->canRead()) {
            ExceptionBuilder::throw(PermissionErrorCode::BusinessException, 'common.access', ['label' => $code]);
        }

        $toolSet = $this->delightfulFlowToolSetDomainService->getByCode($dataIsolation, $code);
        $toolSet->setUserOperation($operation->value);
        return $toolSet;
    }

    #[Transactional]
    public function save(Authenticatable $authorization, DelightfulFlowToolSetEntity $savingDelightfulFLowToolSetEntity): DelightfulFlowToolSetEntity
    {
        $dataIsolation = $this->createFlowDataIsolation($authorization);
        $permissionDataIsolation = $this->createPermissionDataIsolation($dataIsolation);

        // default是create
        $operation = Operation::Owner;
        if (! $savingDelightfulFLowToolSetEntity->shouldCreate()) {
            // 修改needcheckpermission
            $operation = $this->operationPermissionAppService->getOperationByResourceAndUser(
                $permissionDataIsolation,
                ResourceType::ToolSet,
                $savingDelightfulFLowToolSetEntity->getCode(),
                $authorization->getId()
            );
            if (! $operation->canEdit()) {
                ExceptionBuilder::throw(PermissionErrorCode::BusinessException, 'common.access', ['label' => $savingDelightfulFLowToolSetEntity->getCode()]);
            }
        }

        $toolSet = $this->delightfulFlowToolSetDomainService->save($dataIsolation, $savingDelightfulFLowToolSetEntity);
        $toolSet->setUserOperation($operation->value);
        return $toolSet;
    }

    /**
     * @return array{total: int, list: array<DelightfulFlowToolSetEntity>, icons: array<string,FileLink>}
     */
    public function queries(Authenticatable $authorization, DelightfulFlowToolSetQuery $query, Page $page): array
    {
        $dataIsolation = $this->createFlowDataIsolation($authorization);
        $permissionDataIsolation = PermissionDataIsolation::create($dataIsolation->getCurrentOrganizationCode(), $dataIsolation->getCurrentUserId());

        // 仅query目前user具有permission的tool集
        $resources = $this->operationPermissionAppService->getResourceOperationByUserIds(
            $permissionDataIsolation,
            ResourceType::ToolSet,
            [$authorization->getId()]
        )[$authorization->getId()] ?? [];
        $resourceIds = array_keys($resources);

        // 其实不太适合的 whereIn 暂时没想到其他好办法
        $query->setCodes($resourceIds);

        $data = $this->delightfulFlowToolSetDomainService->queries($dataIsolation, $query, $page);
        $filePaths = [];
        foreach ($data['list'] ?? [] as $item) {
            $filePaths[] = $item->getIcon();
            if ($item->getCode() === ConstValue::TOOL_SET_DEFAULT_CODE) {
                // 未分group的直接分配管理员permission
                $item->setUserOperation(Operation::Admin->value);
            } else {
                $operation = $resources[$item->getCode()] ?? Operation::None;
                $item->setUserOperation($operation->value);
            }
        }
        $data['icons'] = $this->getIcons($dataIsolation->getCurrentOrganizationCode(), $filePaths);
        return $data;
    }

    public function destroy(Authenticatable $authorization, string $code): void
    {
        $dataIsolation = $this->createFlowDataIsolation($authorization);
        $permissionDataIsolation = $this->createPermissionDataIsolation($dataIsolation);
        $operation = $this->operationPermissionAppService->getOperationByResourceAndUser(
            $permissionDataIsolation,
            ResourceType::ToolSet,
            $code,
            $authorization->getId()
        );
        if (! $operation->canDelete()) {
            ExceptionBuilder::throw(PermissionErrorCode::BusinessException, 'common.access', ['label' => $code]);
        }
        $this->delightfulFlowToolSetDomainService->destroy($dataIsolation, $code);
    }
}
