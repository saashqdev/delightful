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
 * servicequotienttemplategenerate仓储
 * supportfor所have ProviderCode generatetemplateconfiguration.
 */
readonly class ProviderTemplateRepository
{
    public function __construct(
        private ProviderRepositoryInterface $providerRepository,
    ) {
    }

    /**
     * get所haveservicequotienttemplatecolumntable.
     * @param Category $category servicequotientcategory
     * @return ProviderConfigDTO[] servicequotienttemplatecolumntable
     */
    public function getAllProviderTemplates(Category $category): array
    {
        $templates = [];

        // getfinger定categorydown所haveenableservicequotient
        $providers = $this->providerRepository->getByCategory($category);

        foreach ($providers as $provider) {
            // foreachservicequotientcreatetemplateconfiguration
            $templateId = ProviderConfigIdAssembler::generateProviderTemplate($provider->getProviderCode(), $category);

            // except delightful servicequotient,defaultstatusallisclose
            $defaultStatus = $provider->getProviderCode() === ProviderCode::Official
                ? Status::Enabled
                : Status::Disabled;

            $templateData = [
                'id' => $templateId,
                'service_provider_id' => (string) $provider->getId(),
                'organization_code' => '', // templatenotbindspecificorganization
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
