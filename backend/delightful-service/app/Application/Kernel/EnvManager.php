<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Kernel;

use App\Application\ModelGateway\Mapper\ProviderManager;
use App\Domain\Contact\Service\DelightfulUserDomainService;
use App\Domain\OrganizationEnvironment\Service\DelightfulOrganizationEnvDomainService;
use App\Domain\Provider\Entity\ValueObject\ProviderDataIsolation;
use App\Domain\Provider\Service\ModelFilter\PackageFilterInterface;
use App\Infrastructure\Core\DataIsolation\BaseDataIsolation;
use Hyperf\Context\Context;

class EnvManager
{
    public static function initDataIsolationEnv(BaseDataIsolation $baseDataIsolation, int $envId = 0, bool $force = false): void
    {
        $lastBaseDataIsolation = Context::get('LastBaseDataIsolationInitEnv');
        if (! $force && $lastBaseDataIsolation instanceof BaseDataIsolation) {
            $baseDataIsolation->extends($lastBaseDataIsolation);
            return;
        }

        if (empty($envId) && empty($baseDataIsolation->getCurrentOrganizationCode())) {
            return;
        }
        if (empty($envId)) {
            // 尝试getwhen前环境的环境 ID.
            $envId = $baseDataIsolation->getEnvId();
        }

        $delightfulOrganizationEnvDomainService = di(DelightfulOrganizationEnvDomainService::class);

        if (! $envId) {
            $envDTO = $delightfulOrganizationEnvDomainService->getOrganizationsEnvironmentDTO($baseDataIsolation->getCurrentOrganizationCode());
            $env = $envDTO?->getDelightfulEnvironmentEntity();
            $envId = $envDTO?->getEnvironmentId() ?? 0;
            $relationEnvIds = $env?->getRelationEnvIds() ?? [];
            if (count($relationEnvIds) > 0 && ! $env?->getEnvironment()?->isProduction()) {
                foreach ($relationEnvIds as $relationEnvId) {
                    if ($relationEnvId === $envId) {
                        continue;
                    }
                    $relationEnv = $delightfulOrganizationEnvDomainService->getDelightfulEnvironmentById((int) $relationEnvId);
                    if ($relationEnv?->getEnvironment()?->isProduction()) {
                        $env = $relationEnv;
                        break;
                    }
                }
            }
        } else {
            $env = $delightfulOrganizationEnvDomainService->getDelightfulEnvironmentById($envId);
        }
        if (! $env) {
            return;
        }
        $baseDataIsolation->setEnvId($env->getId());
        $baseDataIsolation->getThirdPlatformDataIsolationManager()->init($baseDataIsolation, $env);

        self::initSubscription($baseDataIsolation, true);

        simple_log('EnvManagerInit', [
            'class' => get_class($baseDataIsolation),
            'env_id' => $baseDataIsolation->getEnvId(),
            'third_platform_manager' => $baseDataIsolation->getThirdPlatformDataIsolationManager()->toArray(),
            'third_user_id' => $baseDataIsolation->getThirdPlatformUserId(),
            'third_organization_code' => $baseDataIsolation->getThirdPlatformOrganizationCode(),
        ]);

        // 同one协程内无需重复load
        Context::set('LastBaseDataIsolationInitEnv', $baseDataIsolation);
    }

    public static function getDelightfulId(string $userId): ?string
    {
        $delightfulUserDomainService = di(DelightfulUserDomainService::class);
        $delightfulUser = $delightfulUserDomainService->getByUserId($userId);
        return $delightfulUser?->getDelightfulId();
    }

    private static function initSubscription(BaseDataIsolation $baseDataIsolation, bool $lazy = true): void
    {
        if ($lazy) {
            $lazyFun = function () use ($baseDataIsolation) {
                self::initSubscription($baseDataIsolation, false);
            };
            $baseDataIsolation->addLazyFunction('initSubscription', $lazyFun);
            return;
        }
        $subscriptionManager = $baseDataIsolation->getSubscriptionManager();
        $providerDataIsolation = ProviderDataIsolation::create($baseDataIsolation->getCurrentOrganizationCode(), $baseDataIsolation->getCurrentUserId(), $baseDataIsolation->getDelightfulId());
        $providerDataIsolation->setContainOfficialOrganization(true);
        if (! $subscriptionManager->isEnabled()) {
            return;
        }
        if ($baseDataIsolation->isOfficialOrganization()) {
            $subscriptionManager->setEnabled(false);
        }

        $subscription = di(PackageFilterInterface::class)->getCurrentSubscription($baseDataIsolation);
        $modelIdsGroupByType = di(ProviderManager::class)->getModelIdsGroupByType($providerDataIsolation);
        $subscriptionManager->setCurrentSubscription($subscription['id'] ?? '', $subscription['info'] ?? [], $modelIdsGroupByType);

        simple_log('CurrentSubscription', $subscriptionManager->toArray());
    }
}
