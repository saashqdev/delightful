<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Flow\Facade\Admin;

use App\Application\Flow\Service\DelightfulFlowApiKeyAppService;
use App\Domain\Flow\Entity\ValueObject\ApiKeyType;
use App\Domain\Flow\Entity\ValueObject\Query\DelightfulFlowApiKeyQuery;
use App\Interfaces\Flow\Assembler\ApiKey\DelightfulFlowApiKeyAssembler;
use Delightful\ApiResponse\Annotation\ApiResponse;
use Hyperf\Di\Annotation\Inject;

#[ApiResponse(version: 'low_code')]
class DelightfulFlowApiKeyFlowAdminApi extends AbstractFlowAdminApi
{
    #[Inject]
    protected DelightfulFlowApiKeyAppService $delightfulFlowApiKeyAppService;

    public function save(string $flowId)
    {
        $authorization = $this->getAuthorization();

        $DTO = DelightfulFlowApiKeyAssembler::createFlowApiKeyDTOByMixed($this->request->all());
        $DTO->setFlowCode($flowId);

        $DO = DelightfulFlowApiKeyAssembler::createDO($DTO);
        $entity = $this->delightfulFlowApiKeyAppService->save($authorization, $DO);
        return DelightfulFlowApiKeyAssembler::createDTO($entity);
    }

    public function queries(string $flowId)
    {
        $authorization = $this->getAuthorization();

        // 获取我创建的个人密钥
        $query = new DelightfulFlowApiKeyQuery();
        $query->setFlowCode($flowId);
        $query->setType(ApiKeyType::Personal->value);
        $query->setCreator($authorization->getId());
        $query->setOrder(['id' => 'desc']);

        $page = $this->createPage();
        $result = $this->delightfulFlowApiKeyAppService->queries($authorization, $query, $page);
        return DelightfulFlowApiKeyAssembler::createPageListDTO($result['total'], $result['list'], $page);
    }

    public function show(string $flowId, string $code)
    {
        $authorization = $this->getAuthorization();
        $entity = $this->delightfulFlowApiKeyAppService->getByCode($authorization, $flowId, $code);
        return DelightfulFlowApiKeyAssembler::createDTO($entity);
    }

    public function changeSecretKey(string $flowId, string $code)
    {
        $authorization = $this->getAuthorization();
        $entity = $this->delightfulFlowApiKeyAppService->changeSecretKey($authorization, $flowId, $code);
        return DelightfulFlowApiKeyAssembler::createDTO($entity);
    }

    public function destroy(string $flowId, string $code)
    {
        $authorization = $this->getAuthorization();
        $this->delightfulFlowApiKeyAppService->destroy($authorization, $code);
    }
}
