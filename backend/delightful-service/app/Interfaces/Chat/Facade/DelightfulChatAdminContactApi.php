<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Chat\Facade;

use App\Application\Chat\Service\DelightfulDepartmentAppService;
use App\Application\Chat\Service\DelightfulUserContactAppService;
use App\Application\Kernel\SuperPermissionEnum;
use App\Domain\Contact\DTO\DepartmentQueryDTO;
use App\Domain\Contact\DTO\UserQueryDTO;
use App\Domain\Contact\Entity\ValueObject\DepartmentOption;
use App\Domain\Contact\Entity\ValueObject\DepartmentSumType;
use App\Domain\Contact\Entity\ValueObject\UserOption;
use App\Domain\Contact\Entity\ValueObject\UserQueryType;
use App\ErrorCode\ChatErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\Auth\PermissionChecker;
use Delightful\ApiResponse\Annotation\ApiResponse;
use Hyperf\HttpServer\Contract\RequestInterface;

/**
 * managebackplatformaddress bookinterface,andopenputplatforminterfacereturnformatdifferent.
 */
#[ApiResponse('low_code')]
class DelightfulChatAdminContactApi extends AbstractApi
{
    public function __construct(
        private readonly DelightfulDepartmentAppService $departmentContactAppService,
        private readonly DelightfulUserContactAppService $userContactAppService,
    ) {
    }

    /**
     * getdownleveldepartmentlist.
     */
    public function getSubList(string $id, RequestInterface $request): array
    {
        $pageToken = (string) $request->input('page_token', '');
        $sumType = (int) ($request->query('sum_type') ?: DepartmentSumType::DirectEmployee->value);
        $queryDTO = $this->getDepartmentQueryDTO($id, $pageToken, $sumType);
        $authorization = $this->getAuthorization();
        return $this->departmentContactAppService->getSubDepartments($queryDTO, $authorization)->toArray();
    }

    public function getDepartmentInfoById(string $id, RequestInterface $request): array
    {
        $pageToken = (string) $request->input('page_token', '');
        $sumType = (int) ($request->query('sum_type') ?: DepartmentSumType::DirectEmployee->value);
        $queryDTO = $this->getDepartmentQueryDTO($id, $pageToken, $sumType);
        $authorization = $this->getAuthorization();
        $departmentEntity = $this->departmentContactAppService->getDepartmentById($queryDTO, $authorization);
        return $departmentEntity ? $departmentEntity->toArray() : [];
    }

    public function departmentSearch(RequestInterface $request): array
    {
        $pageToken = (string) $request->query('page_token', '');
        $sumType = (int) ($request->query('sum_type') ?: DepartmentSumType::DirectEmployee->value);
        $queryDTO = $this->getDepartmentQueryDTO('', $pageToken, $sumType);
        $authorization = $this->getAuthorization();
        $queryDTO->setQuery((string) $request->query('name', ''));
        return $this->departmentContactAppService->searchDepartment($queryDTO, $authorization);
    }

    /**
     * byuseridquery,returnuserbyandhe indepartmentinfo.
     */
    public function userGetByIds(RequestInterface $request): array
    {
        $ids = $request->input('user_ids', '');
        // uponepagetoken. toatmysqlcomesay,returnaccumulateproductoffsetquantity;toatescomesay,returncursor
        $pageToken = (string) $request->input('page_token', '');
        $queryType = (int) ($request->input('query_type') ?: UserQueryType::User->value);
        if (! in_array($queryType, UserQueryType::types())) {
            ExceptionBuilder::throw(ChatErrorCode::INPUT_PARAM_ERROR, 'chat.common.param_error', ['param' => 'query_type']);
        }
        $queryType = UserQueryType::from($queryType);
        $listQuery = new UserQueryDTO();
        $listQuery->setPageToken($pageToken);
        $listQuery->setQueryType($queryType);
        $listQuery->setUserIds($ids);
        $authorization = $this->getAuthorization();
        return $this->userContactAppService->getUserDetailByIds($listQuery, $authorization);
    }

    /**
     * querydepartmentdirectly underuserlist.
     */
    public function departmentUserList(string $id, RequestInterface $request): array
    {
        // departmentid
        // uponepagetoken. toatmysqlcomesay,returnaccumulateproductoffsetquantity;toatescomesay,returncursor
        $pageToken = (string) $request->input('page_token', '');
        // whetherrecursion
        $recursive = (bool) $request->input('recursive', false);
        $listQuery = new UserQueryDTO();
        $listQuery->setDepartmentId($id);
        $listQuery->setPageToken($pageToken);
        $listQuery->setIsRecursive($recursive);
        $authorization = $this->getAuthorization();
        return $this->userContactAppService->getUsersDetailByDepartmentId($listQuery, $authorization);
    }

    public function searchForSelect(RequestInterface $request): array
    {
        $authorization = $this->getAuthorization();
        $query = (string) $request->input('query', '');
        // uponepagetoken. toatmysqlcomesay,returnaccumulateproductoffsetquantity;toatescomesay,returncursor
        $pageToken = (string) $request->input('page_token', '');
        if (empty($query)) {
            ExceptionBuilder::throw(ChatErrorCode::INPUT_PARAM_ERROR, 'chat.common.param_error', ['param' => 'query']);
        }
        $queryType = (int) $request->input('query_type', UserQueryType::User->value);
        if (! in_array($queryType, UserQueryType::types())) {
            ExceptionBuilder::throw(ChatErrorCode::INPUT_PARAM_ERROR, 'chat.common.param_error', ['param' => 'query_type']);
        }
        $filterAgent = (bool) $request->input('filter_agent', false);
        $queryType = UserQueryType::from($queryType);
        $listQuery = new UserQueryDTO();
        $listQuery->setQuery($query);
        $listQuery->setPageToken($pageToken);
        $listQuery->setQueryType($queryType);
        $listQuery->setFilterAgent($filterAgent);
        return $this->userContactAppService->searchDepartmentUser($listQuery, $authorization);
    }

    public function updateDepartmentsOptionByIds(RequestInterface $request): array
    {
        $authorization = $this->getAuthorization();

        if (! PermissionChecker::mobileHasPermission($authorization->getMobile(), SuperPermissionEnum::HIDE_USER_OR_DEPT)) {
            ExceptionBuilder::throw(ChatErrorCode::OPERATION_FAILED);
        }
        $userIds = (array) $request->input('department_ids', '');
        $option = $request->input('option');
        $option = is_numeric($option) ? DepartmentOption::tryFrom((int) $option) : null;
        return [
            'changed_num' => $this->departmentContactAppService->updateDepartmentsOptionByIds($userIds, $option),
        ];
    }

    public function updateUsersOptionByIds(RequestInterface $request): array
    {
        $authorization = $this->getAuthorization();
        if (! PermissionChecker::mobileHasPermission($authorization->getMobile(), SuperPermissionEnum::HIDE_USER_OR_DEPT)) {
            ExceptionBuilder::throw(ChatErrorCode::OPERATION_FAILED);
        }
        $userIds = (array) $request->input('user_ids', '');
        $option = $request->input('option');
        $option = is_numeric($option) ? UserOption::tryFrom((int) $option) : null;
        return [
            'changed_num' => $this->userContactAppService->updateUserOptionByIds($userIds, $option),
        ];
    }

    // getdepartmentquerydto
    private function getDepartmentQueryDTO(string $id, string $pageToken, int $sumType): DepartmentQueryDTO
    {
        $queryDTO = new DepartmentQueryDTO();
        $queryDTO->setDepartmentId($id);
        $queryDTO->setSumType(DepartmentSumType::from($sumType));
        $queryDTO->setPageToken($pageToken);
        return $queryDTO;
    }
}
