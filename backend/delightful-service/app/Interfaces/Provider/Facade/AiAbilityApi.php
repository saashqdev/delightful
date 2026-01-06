<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Provider\Facade;

use App\Application\Kernel\Enum\DelightfulOperationEnum;
use App\Application\Kernel\Enum\DelightfulResourceEnum;
use App\Application\Provider\Service\AiAbilityAppService;
use App\Infrastructure\Util\Permission\Annotation\CheckPermission;
use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use App\Interfaces\Provider\Assembler\AiAbilityAssembler;
use App\Interfaces\Provider\DTO\UpdateAiAbilityRequest;
use Delightful\ApiResponse\Annotation\ApiResponse;
use Hyperf\Di\Annotation\Inject;

#[ApiResponse('low_code')]
class AiAbilityApi extends AbstractApi
{
    #[Inject]
    protected AiAbilityAppService $aiAbilityAppService;

    /**
     * Get all AI abilities.
     */
    #[CheckPermission([DelightfulResourceEnum::ADMIN_AI_ABILITY], DelightfulOperationEnum::QUERY)]
    public function queries(): array
    {
        /** @var DelightfulUserAuthorization $authorization */
        $authorization = $this->getAuthorization();

        $list = $this->aiAbilityAppService->queries($authorization);

        return AiAbilityAssembler::listDTOsToArray($list);
    }

    /**
     * Get AI ability details.
     */
    #[CheckPermission([DelightfulResourceEnum::ADMIN_AI_ABILITY], DelightfulOperationEnum::QUERY)]
    public function detail(string $code): array
    {
        /** @var DelightfulUserAuthorization $authorization */
        $authorization = $this->getAuthorization();

        $detail = $this->aiAbilityAppService->getDetail($authorization, $code);

        return $detail->toArray();
    }

    /**
     * Update an AI ability.
     */
    #[CheckPermission([DelightfulResourceEnum::ADMIN_AI_ABILITY], DelightfulOperationEnum::EDIT)]
    public function update(string $code): array
    {
        /** @var DelightfulUserAuthorization $authorization */
        $authorization = $this->getAuthorization();

        $requestData = $this->request->all();
        $requestData['code'] = $code;

        $updateRequest = new UpdateAiAbilityRequest($requestData);

        $this->aiAbilityAppService->update($authorization, $updateRequest);

        return [];
    }
}
