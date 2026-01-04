<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the software license
 */

namespace App\Interfaces\Provider\Facade;

use App\Application\Kernel\Enum\MagicOperationEnum;
use App\Application\Kernel\Enum\MagicResourceEnum;
use App\Application\Provider\Service\AiAbilityAppService;
use App\Infrastructure\Util\Permission\Annotation\CheckPermission;
use App\Interfaces\Authorization\Web\MagicUserAuthorization;
use App\Interfaces\Provider\Assembler\AiAbilityAssembler;
use App\Interfaces\Provider\DTO\UpdateAiAbilityRequest;
use Dtyq\ApiResponse\Annotation\ApiResponse;
use Hyperf\Di\Annotation\Inject;

#[ApiResponse('low_code')]
class AiAbilityApi extends AbstractApi
{
    #[Inject]
    protected AiAbilityAppService $aiAbilityAppService;

    /**
     * Get all AI abilities.
     */
    #[CheckPermission([MagicResourceEnum::ADMIN_AI_ABILITY], MagicOperationEnum::QUERY)]
    public function queries(): array
    {
        /** @var MagicUserAuthorization $authorization */
        $authorization = $this->getAuthorization();

        $list = $this->aiAbilityAppService->queries($authorization);

        return AiAbilityAssembler::listDTOsToArray($list);
    }

    /**
     * Get AI ability details.
     */
    #[CheckPermission([MagicResourceEnum::ADMIN_AI_ABILITY], MagicOperationEnum::QUERY)]
    public function detail(string $code): array
    {
        /** @var MagicUserAuthorization $authorization */
        $authorization = $this->getAuthorization();

        $detail = $this->aiAbilityAppService->getDetail($authorization, $code);

        return $detail->toArray();
    }

    /**
     * Update an AI ability.
     */
    #[CheckPermission([MagicResourceEnum::ADMIN_AI_ABILITY], MagicOperationEnum::EDIT)]
    public function update(string $code): array
    {
        /** @var MagicUserAuthorization $authorization */
        $authorization = $this->getAuthorization();

        $requestData = $this->request->all();
        $requestData['code'] = $code;

        $updateRequest = new UpdateAiAbilityRequest($requestData);

        $this->aiAbilityAppService->update($authorization, $updateRequest);

        return [];
    }
}
