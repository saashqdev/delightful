<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Interfaces\BeAgent\Facade;

use App\Application\File\Service\FileAppService;
use App\Domain\Contact\Entity\ValueObject\UserType;
use App\Infrastructure\Util\Context\RequestContext;
use App\Interfaces\Authorization\Web\MagicUserAuthorization;
use Dtyq\ApiResponse\Annotation\ApiResponse;
use Delightful\BeDelightful\Application\BeAgent\Service\AccountAppService;
use Delightful\BeDelightful\Domain\BeAgent\Constant\AgentConstant;
use Hyperf\HttpServer\Contract\RequestInterface;

#[ApiResponse('low_code')]
class AccountApi extends AbstractApi
{
    public function __construct(
        private readonly AccountAppService $accountAppService,
        protected RequestInterface $request,
        private readonly FileAppService $fileAppService,
    ) {
        parent::__construct($request);
    }

    public function initAccount(RequestContext $requestContext): array
    {
        $token = $this->request->input('token', '');
        $organizationCode = $this->request->input('organization_code', '');
        if ($token !== md5(AgentConstant::BE_DELIGHTFUL_CODE)) {
            return ['result' => 'token failed'];
        }

        return $this->accountAppService->initAccount($organizationCode);
    }

    public function getStsToken(RequestContext $requestContext): array
    {
        // 设置用户授权信息
        $requestContext->setAuthorization($this->request->header('authorization', ''));
        $requestContext->setUserAuthorization($this->getAuthorization());
        $userAuthorization = new MagicUserAuthorization();
        $userAuthorization->setId($requestContext->getUserId());
        $userAuthorization->setOrganizationCode($requestContext->getOrganizationCode());
        $userAuthorization->setUserType(UserType::Human);
        $dir = $this->request->input('dir', '');
        return $this->fileAppService->getStsTemporaryCredentialV2($requestContext->getOrganizationCode(), 'private', $dir, 3600 * 2);
    }
}
