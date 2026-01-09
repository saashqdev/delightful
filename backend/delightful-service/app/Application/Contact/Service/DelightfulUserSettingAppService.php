<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Contact\Service;

use App\Application\Contact\UserSetting\UserSettingKey;
use App\Domain\Contact\Entity\DelightfulUserSettingEntity;
use App\Domain\Contact\Entity\ValueObject\Query\DelightfulUserSettingQuery;
use App\Domain\Contact\Repository\Facade\DelightfulUserRepositoryInterface;
use App\Domain\Contact\Service\DelightfulUserSettingDomainService;
use App\ErrorCode\GenericErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Core\Traits\DataIsolationTrait;
use App\Infrastructure\Core\ValueObject\Page;
use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use Hyperf\Di\Annotation\Inject;
use Qbhy\HyperfAuth\Authenticatable;

class DelightfulUserSettingAppService extends AbstractContactAppService
{
    use DataIsolationTrait;

    #[Inject]
    protected DelightfulUserRepositoryInterface $delightfulUserRepository;

    public function __construct(
        private readonly DelightfulUserSettingDomainService $delightfulUserSettingDomainService
    ) {
    }

    public function saveProjectTopicModelConfig(Authenticatable $authorization, string $topicId, array $model, array $imageModel = []): DelightfulUserSettingEntity
    {
        /* @phpstan-ignore-next-line */
        $dataIsolation = $this->createDataIsolation($authorization);
        $entity = new DelightfulUserSettingEntity();
        $entity->setKey(UserSettingKey::genBeDelightfulProjectTopicModel($topicId));
        $entity->setValue([
            'model' => $model,
            'image_model' => $imageModel,
        ]);
        return $this->delightfulUserSettingDomainService->save($dataIsolation, $entity);
    }

    public function getProjectTopicModelConfig(Authenticatable $authorization, string $topicId): ?DelightfulUserSettingEntity
    {
        $key = UserSettingKey::genBeDelightfulProjectTopicModel($topicId);
        /* @phpstan-ignore-next-line */
        return $this->get($authorization, $key);
    }

    public function saveProjectMcpServerConfig(Authenticatable $authorization, string $projectId, array $servers): DelightfulUserSettingEntity
    {
        /* @phpstan-ignore-next-line */
        $dataIsolation = $this->createDataIsolation($authorization);
        $entity = new DelightfulUserSettingEntity();
        $entity->setKey(UserSettingKey::genBeDelightfulProjectMCPServers($projectId));
        $entity->setValue([
            'servers' => $servers,
        ]);
        return $this->delightfulUserSettingDomainService->save($dataIsolation, $entity);
    }

    public function getProjectMcpServerConfig(Authenticatable $authorization, string $projectId): ?DelightfulUserSettingEntity
    {
        $key = UserSettingKey::genBeDelightfulProjectMCPServers($projectId);
        /* @phpstan-ignore-next-line */
        return $this->get($authorization, $key);
    }

    /**
     * @param DelightfulUserAuthorization $authorization
     */
    public function save(Authenticatable $authorization, DelightfulUserSettingEntity $entity): DelightfulUserSettingEntity
    {
        $dataIsolation = $this->createDataIsolation($authorization);
        $key = UserSettingKey::make($entity->getKey());
        if (! $key->isValid()) {
            ExceptionBuilder::throw(GenericErrorCode::AccessDenied);
        }
        return $this->delightfulUserSettingDomainService->save($dataIsolation, $entity);
    }

    /**
     * @param DelightfulUserAuthorization $authorization
     */
    public function get(Authenticatable $authorization, string $key): ?DelightfulUserSettingEntity
    {
        $dataIsolation = $this->createDataIsolation($authorization);
        $flowDataIsolation = $this->createFlowDataIsolation($authorization);

        $setting = $this->delightfulUserSettingDomainService->get($dataIsolation, $key);

        $key = UserSettingKey::make($key);
        if ($setting) {
            $key?->getValueHandler()?->populateValue($flowDataIsolation, $setting);
        } else {
            $setting = $key?->getValueHandler()?->generateDefault() ?? null;
        }

        return $setting;
    }

    /**
     * @param DelightfulUserAuthorization $authorization
     * @return array{total: int, list: array<DelightfulUserSettingEntity>}
     */
    public function queries(Authenticatable $authorization, DelightfulUserSettingQuery $query, Page $page): array
    {
        $dataIsolation = $this->createDataIsolation($authorization);

        // Force query to only return current user's settings
        $query->setUserId($dataIsolation->getCurrentUserId());

        return $this->delightfulUserSettingDomainService->queries($dataIsolation, $query, $page);
    }

    /**
     * 保存when前organizationinformation（通过 delightfulId）.
     * @param string $delightfulId 账号标识
     * @param array<string, mixed> $organizationData organizationinformationdata
     */
    public function saveCurrentOrganizationDataByDelightfulId(string $delightfulId, array $organizationData): DelightfulUserSettingEntity
    {
        $entity = new DelightfulUserSettingEntity();
        $entity->setKey(UserSettingKey::CurrentOrganization->value);
        $entity->setValue($organizationData);

        return $this->delightfulUserSettingDomainService->saveByDelightfulId($delightfulId, $entity);
    }

    /**
     * getwhen前organizationinformation（通过 delightfulId）.
     * @param string $delightfulId 账号标识
     * @return null|array<string, mixed>
     */
    public function getCurrentOrganizationDataByDelightfulId(string $delightfulId): ?array
    {
        $setting = $this->delightfulUserSettingDomainService->getByDelightfulId($delightfulId, UserSettingKey::CurrentOrganization->value);
        return $setting?->getValue();
    }
}
