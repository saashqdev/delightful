<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Admin\Facade\Agent;

use App\Application\Admin\Agent\Service\AdminAgentAppService;
use App\Application\Chat\Service\DelightfulAccountAppService;
use App\Application\Chat\Service\DelightfulUserContactAppService;
use App\Application\Permission\Service\OperationPermissionAppService;
use App\Domain\Admin\Entity\ValueObject\AgentFilterType;
use App\ErrorCode\UserErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\Auth\PermissionChecker;
use App\Interfaces\Admin\DTO\Request\QueryPageAgentDTO;
use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use BeDelightful\ApiResponse\Annotation\ApiResponse;
use Hyperf\HttpServer\Contract\RequestInterface;
use Qbhy\HyperfAuth\AuthManager;

#[ApiResponse('low_code')]
class AdminAgentApi extends AbstractApi
{
    public function __construct(
        protected AdminAgentAppService $adminAgentAppService,
        protected OperationPermissionAppService $permissionAppService,
        RequestInterface $request,
        AuthManager $authManager,
    ) {
        parent::__construct(
            $authManager,
            $request,
        );
    }

    public function getPublishedAgents()
    {
        $this->isInWhiteListForOrgization();
        $pageToken = $this->request->input('page_token', '');
        $pageSize = (int) $this->request->input('page_size', 20);
        $type = AgentFilterType::from((int) $this->request->input('type', AgentFilterType::ALL->value));

        return $this->adminAgentAppService->getPublishedAgents(
            $this->getAuthorization(),
            $pageToken,
            $pageSize,
            $type
        );
    }

    public function queriesAgents(RequestInterface $request)
    {
        $this->isInWhiteListForOrgization();
        /**
         * @var DelightfulUserAuthorization $authenticatable
         */
        $authenticatable = $this->getAuthorization();
        $queryPageAgentDTO = new QueryPageAgentDTO($request->all());
        return $this->adminAgentAppService->queriesAgents($authenticatable, $queryPageAgentDTO);
    }

    public function getAgentDetail(RequestInterface $request, string $agentId)
    {
        $this->isInWhiteListForOrgization();
        /**
         * @var DelightfulUserAuthorization $authenticatable
         */
        $authenticatable = $this->getAuthorization();
        return $this->adminAgentAppService->getAgentDetail($authenticatable, $agentId);
    }

    public function getOrganizationAgentsCreators(RequestInterface $request)
    {
        $this->isInWhiteListForOrgization();
        /**
         * @var DelightfulUserAuthorization $authenticatable
         */
        $authenticatable = $this->getAuthorization();
        return $this->adminAgentAppService->getOrganizationAgentsCreators($authenticatable);
    }

    public function deleteAgent(RequestInterface $request, string $agentId)
    {
        $this->isInWhiteListForOrgization();
        /**
         * @var DelightfulUserAuthorization $authenticatable
         */
        $authenticatable = $this->getAuthorization();
        $this->adminAgentAppService->deleteAgent($authenticatable, $agentId);
    }

    private function getPhone(string $userId)
    {
        $delightfulUserContactAppService = di(DelightfulUserContactAppService::class);
        $user = $delightfulUserContactAppService->getByUserId($userId);
        $delightfulAccountAppService = di(DelightfulAccountAppService::class);
        $accountEntity = $delightfulAccountAppService->getAccountInfoByDelightfulId($user->getDelightfulId());
        return $accountEntity->getPhone();
    }

    private function isInWhiteListForOrgization(): void
    {
        /**
         * @var DelightfulUserAuthorization $authentication
         */
        $authentication = $this->getAuthorization();
        $phone = $this->getPhone($authentication->getId());
        if (! PermissionChecker::isOrganizationAdmin($authentication->getOrganizationCode(), $phone)) {
            ExceptionBuilder::throw(UserErrorCode::ORGANIZATION_NOT_AUTHORIZE);
        }
    }
}
