<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Provider\Repository\Persistence;

use App\Domain\Provider\DTO\ProviderConfigDTO;
use App\Domain\Provider\Entity\ValueObject\Category;
use App\Domain\Provider\Entity\ValueObject\ProviderCode;
use App\Domain\Provider\Entity\ValueObject\Status;
use App\Domain\Provider\Repository\Facade\ProviderRepositoryInterface;
use App\Interfaces\Provider\Assembler\ProviderConfigIdAssembler;
use DateTime;

/**
 * service商templategenerate仓储
 * 支持为所有 ProviderCode generatetemplateconfiguration.
 */
readonly class ProviderTemplateRepository
{
    public function __construct(
        private ProviderRepositoryInterface $providerRepository,
    ) {
    }

    /**
     * get所有service商的template列表.
     * @param Category $category service商category
     * @return ProviderConfigDTO[] service商template列表
     */
    public function getAllProviderTemplates(Category $category): array
    {
        $templates = [];

        // get指定category下所有enable的service商
        $providers = $this->providerRepository->getByCategory($category);

        foreach ($providers as $provider) {
            // 为每个service商createtemplateconfiguration
            $templateId = ProviderConfigIdAssembler::generateProviderTemplate($provider->getProviderCode(), $category);

            // 除了 delightful service商，defaultstatus都是close
            $defaultStatus = $provider->getProviderCode() === ProviderCode::Official
                ? Status::Enabled
                : Status::Disabled;

            $templateData = [
                'id' => $templateId,
                'service_provider_id' => (string) $provider->getId(),
                'organization_code' => '', // template不bind具体organization
                'config' => [],
                'decryptedConfig' => [],
                'status' => $defaultStatus->value,
                'alias' => '',
                'translate' => [],
                'created_at' => (new DateTime())->format('Y-m-d H:i:s'),
                'updated_at' => (new DateTime())->format('Y-m-d H:i:s'),
                'name' => $provider->getName(),
                'description' => $provider->getDescription(),
                'icon' => $provider->getIcon(),
                'provider_type' => $provider->getProviderType()->value,
                'category' => $category->value,
                'provider_code' => $provider->getProviderCode()->value,
                'remark' => '',
            ];

            $templates[] = new ProviderConfigDTO($templateData);
        }

        return $templates;
    }
}
