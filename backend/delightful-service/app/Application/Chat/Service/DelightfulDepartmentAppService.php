<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Chat\Service;

use App\Domain\Agent\Service\DelightfulAgentDomainService;
use App\Domain\Chat\DTO\PageResponseDTO\DepartmentsPageResponseDTO;
use App\Domain\Chat\Entity\ValueObject\PlatformRootDepartmentId;
use App\Domain\Contact\DTO\DepartmentQueryDTO;
use App\Domain\Contact\Entity\DelightfulDepartmentEntity;
use App\Domain\Contact\Entity\ValueObject\DepartmentOption;
use App\Domain\Contact\Entity\ValueObject\DepartmentSumType;
use App\Domain\Contact\Service\DelightfulAccountDomainService;
use App\Domain\Contact\Service\DelightfulDepartmentDomainService;
use App\Domain\Contact\Service\DelightfulThirdPlatformDomainService;
use App\Domain\Contact\Service\DelightfulUserDomainService;
use App\Domain\OrganizationEnvironment\Service\DelightfulOrganizationEnvDomainService;
use App\Infrastructure\Util\Locker\LockerInterface;
use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use App\Interfaces\Chat\Assembler\PageListAssembler;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use Throwable;

class DelightfulDepartmentAppService extends AbstractAppService
{
    public LoggerInterface $logger;

    public function __construct(
        protected DelightfulDepartmentDomainService $delightfulDepartmentDomainService,
        protected DelightfulOrganizationEnvDomainService $organizationEnvDomainService,
        protected DelightfulAccountDomainService $delightfulAccountDomainService,
        protected DelightfulUserDomainService $delightfulUserDomainService,
        protected LockerInterface $locker,
        protected LoggerFactory $loggerFactory,
        protected DelightfulAgentDomainService $delightfulAgentDomainService,
        protected DelightfulThirdPlatformDomainService $thirdPlatformDomainService,
    ) {
        try {
            $this->logger = $loggerFactory->get(get_class($this));
        } catch (Throwable) {
        }
    }

    // querydepartmentdetail,needreturnwhetherhavechilddepartment
    public function getDepartmentById(DepartmentQueryDTO $queryDTO, DelightfulUserAuthorization $userAuthorization): ?DelightfulDepartmentEntity
    {
        // toatfrontclientcomesay, -1 tableshowrootdepartmentinfo.
        $dataIsolation = $this->createDataIsolation($userAuthorization);
        $departmentEntity = $this->delightfulDepartmentDomainService->getDepartmentById($dataIsolation, $queryDTO->getDepartmentId());
        if ($departmentEntity === null) {
            return null;
        }
        $this->setChildrenEmployeeSum($queryDTO, $departmentEntity);
        // judgewhetherhavedownleveldepartment
        $departmentEntity = $this->delightfulDepartmentDomainService->getDepartmentsHasChild([$departmentEntity], $dataIsolation->getCurrentOrganizationCode())[0];
        return $this->filterDepartmentsHidden([$departmentEntity])[0] ?? null;
    }

    /**
     * querydepartmentdetail.
     * @return array<DelightfulDepartmentEntity>
     */
    public function getDepartmentByIds(DepartmentQueryDTO $queryDTO, DelightfulUserAuthorization $userAuthorization): array
    {
        // toatfrontclientcomesay, -1 tableshowrootdepartmentinfo.
        $dataIsolation = $this->createDataIsolation($userAuthorization);
        $departmentEntities = $this->delightfulDepartmentDomainService->getDepartmentByIds($dataIsolation, $queryDTO->getDepartmentIds(), true);
        return $this->filterDepartmentsHidden($departmentEntities);
    }

    public function getSubDepartments(DepartmentQueryDTO $queryDTO, DelightfulUserAuthorization $userAuthorization): DepartmentsPageResponseDTO
    {
        $offset = 0;
        $pageSize = 50;
        $pageToken = $queryDTO->getPageToken();
        $departmentId = $queryDTO->getDepartmentId();
        if (is_numeric($pageToken)) {
            $offset = (int) $pageToken;
        }
        $dataIsolation = $this->createDataIsolation($userAuthorization);
        // departmentId for-1 tableshowgetrootdepartmentdowntheoneleveldepartment
        if ($departmentId === PlatformRootDepartmentId::Delightful) {
            $departmentsPageResponseDTO = $this->delightfulDepartmentDomainService->getSubDepartmentsByLevel($dataIsolation, 0, 1, $pageSize, $offset);
        } else {
            // getdepartment
            $departmentsPageResponseDTO = $this->delightfulDepartmentDomainService->getSubDepartmentsById($dataIsolation, $departmentId, $pageSize, $offset);
        }
        $departments = $departmentsPageResponseDTO->getItems();
        // setdepartmentbyand havechilddepartmentpersonmemberquantity.
        foreach ($departments as $delightfulDepartmentEntity) {
            $this->setChildrenEmployeeSum($queryDTO, $delightfulDepartmentEntity);
        }
        // address bookandsearchrelatedcloseinterface,filterhiddendepartmentandhiddenuser.
        $departments = $this->filterDepartmentsHidden($departments);
        $departmentsPageResponseDTO->setItems($departments);
        return $departmentsPageResponseDTO;
    }

    public function searchDepartment(DepartmentQueryDTO $queryDTO, DelightfulUserAuthorization $userAuthorization): array
    {
        $pageToken = $queryDTO->getPageToken();
        $departmentName = $queryDTO->getQuery();
        $dataIsolation = $this->createDataIsolation($userAuthorization);
        $departments = $this->delightfulDepartmentDomainService->searchDepartment($dataIsolation, $departmentName);
        foreach ($departments as $delightfulDepartmentEntity) {
            $this->setChildrenEmployeeSum($queryDTO, $delightfulDepartmentEntity);
        }
        // address bookandsearchrelatedcloseinterface,filterhiddendepartmentandhiddenuser.
        $departments = $this->filterDepartmentsHidden($departments);
        // allquantityfind,nothavemoremultiple
        return PageListAssembler::pageByMysql($departments);
    }

    public function updateDepartmentsOptionByIds(array $userIds, ?DepartmentOption $departmentOption = null): int
    {
        return $this->delightfulDepartmentDomainService->updateDepartmentsOptionByIds($userIds, $departmentOption);
    }

    /**
     * address bookandsearchrelatedcloseinterface,filterhiddendepartmentandhiddenuser.
     * @param DelightfulDepartmentEntity[] $delightfulDepartments
     */
    protected function filterDepartmentsHidden(array $delightfulDepartments): array
    {
        foreach ($delightfulDepartments as $key => $departmentEntity) {
            if ($departmentEntity->getOption() === DepartmentOption::Hidden) {
                unset($delightfulDepartments[$key]);
            }
        }
        return array_values($delightfulDepartments);
    }

    /**
     * setdepartmentbyand havechilddepartmentpersonmemberquantity.
     */
    protected function setChildrenEmployeeSum(DepartmentQueryDTO $queryDTO, DelightfulDepartmentEntity $departmentEntity): void
    {
        // departmentbyand havechilddepartmentpersonmemberquantity
        if ($queryDTO->getSumType() === DepartmentSumType::All) {
            $employeeSum = $this->delightfulDepartmentDomainService->getDepartmentChildrenEmployeeSum($departmentEntity);
            $departmentEntity->setEmployeeSum($employeeSum);
        }
    }
}
