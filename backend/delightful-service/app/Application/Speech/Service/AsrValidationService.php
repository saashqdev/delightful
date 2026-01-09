<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Speech\Service;

use App\Application\Speech\DTO\AsrTaskStatusDTO;
use App\Domain\Asr\Service\AsrTaskDomainService;
use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Domain\Contact\Service\DelightfulDepartmentUserDomainService;
use App\ErrorCode\AsrErrorCode;
use App\Infrastructure\Core\Exception\BusinessException;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ProjectEntity;
use Delightful\BeDelightful\Domain\BeAgent\Entity\TopicEntity;
use Delightful\BeDelightful\Domain\BeAgent\Service\ProjectDomainService;
use Delightful\BeDelightful\Domain\BeAgent\Service\ProjectMemberDomainService;
use Delightful\BeDelightful\Domain\BeAgent\Service\TopicDomainService;
use Delightful\BeDelightful\ErrorCode\BeAgentErrorCode;
use Throwable;

/**
 * ASR verifyservice
 * 负责projectpermission、话题归属、taskstatusetcverify逻辑.
 */
readonly class AsrValidationService
{
    public function __construct(
        private ProjectDomainService $projectDomainService,
        private ProjectMemberDomainService $projectMemberDomainService,
        private DelightfulDepartmentUserDomainService $delightfulDepartmentUserDomainService,
        private TopicDomainService $topicDomainService,
        private AsrTaskDomainService $asrTaskDomainService
    ) {
    }

    /**
     * verifyprojectpermission - ensureproject属atcurrentuser和organization.
     *
     * @param string $projectId projectID
     * @param string $userId userID
     * @param string $organizationCode organizationencoding
     * @return ProjectEntity project实body
     */
    public function validateProjectAccess(string $projectId, string $userId, string $organizationCode): ProjectEntity
    {
        try {
            // getprojectinfo
            $projectEntity = $this->projectDomainService->getProjectNotUserId((int) $projectId);
            if ($projectEntity === null) {
                ExceptionBuilder::throw(BeAgentErrorCode::PROJECT_NOT_FOUND);
            }

            // 校验projectwhether属atcurrentorganization
            if ($projectEntity->getUserOrganizationCode() !== $organizationCode) {
                ExceptionBuilder::throw(AsrErrorCode::ProjectAccessDeniedOrganization);
            }

            // 校验projectwhether属atcurrentuser
            if ($projectEntity->getUserId() === $userId) {
                return $projectEntity;
            }

            // checkuserwhether是projectmember
            if ($this->projectMemberDomainService->isProjectMemberByUser((int) $projectId, $userId)) {
                return $projectEntity;
            }

            // checkuser所indepartmentwhetherhaveprojectpermission
            $dataIsolation = DataIsolation::create($organizationCode, $userId);
            $departmentIds = $this->delightfulDepartmentUserDomainService->getDepartmentIdsByUserId($dataIsolation, $userId, true);

            if (! empty($departmentIds) && $this->projectMemberDomainService->isProjectMemberByDepartments((int) $projectId, $departmentIds)) {
                return $projectEntity;
            }

            // 所havepermissioncheckallfail
            ExceptionBuilder::throw(AsrErrorCode::ProjectAccessDeniedUser);
        } catch (BusinessException $e) {
            // process ExceptionBuilder::throw throw的业务exception
            if ($e->getCode() === BeAgentErrorCode::PROJECT_NOT_FOUND->value) {
                ExceptionBuilder::throw(AsrErrorCode::ProjectNotFound);
            }
            if ($e->getCode() >= 43000 && $e->getCode() < 44000) {
                // 已经是 AsrErrorCode，直接重新throw
                throw $e;
            }

            // 其他业务exceptionconvert为permissionverifyfail
            ExceptionBuilder::throw(AsrErrorCode::ProjectAccessValidationFailed, '', ['error' => $e->getMessage()]);
        } catch (Throwable $e) {
            // 其他exception统一process为permissionverifyfail
            ExceptionBuilder::throw(AsrErrorCode::ProjectAccessValidationFailed, '', ['error' => $e->getMessage()]);
        }
    }

    /**
     * verify话题归属.
     *
     * @param int $topicId 话题ID
     * @param string $userId userID
     * @return TopicEntity 话题实body
     */
    public function validateTopicOwnership(int $topicId, string $userId): TopicEntity
    {
        $topicEntity = $this->topicDomainService->getTopicById($topicId);

        if ($topicEntity === null) {
            ExceptionBuilder::throw(BeAgentErrorCode::TOPIC_NOT_FOUND);
        }

        // verify话题属atcurrentuser
        if ($topicEntity->getUserId() !== $userId) {
            ExceptionBuilder::throw(BeAgentErrorCode::TOPIC_NOT_FOUND);
        }

        return $topicEntity;
    }

    /**
     * verify并gettaskstatus.
     *
     * @param string $taskKey task键
     * @param string $userId userID
     * @return AsrTaskStatusDTO taskstatusDTO
     */
    public function validateTaskStatus(string $taskKey, string $userId): AsrTaskStatusDTO
    {
        $taskStatus = $this->asrTaskDomainService->findTaskByKey($taskKey, $userId);

        if ($taskStatus === null) {
            ExceptionBuilder::throw(AsrErrorCode::UploadAudioFirst);
        }

        // verifyuserID匹配（基本的securitycheck）
        if ($taskStatus->userId !== $userId) {
            ExceptionBuilder::throw(AsrErrorCode::TaskNotBelongToUser);
        }

        return $taskStatus;
    }

    /**
     * from话题getprojectID（contain话题归属verify）.
     *
     * @param int $topicId 话题ID
     * @param string $userId userID
     * @return string projectID
     */
    public function getProjectIdFromTopic(int $topicId, string $userId): string
    {
        $topicEntity = $this->validateTopicOwnership($topicId, $userId);
        return (string) $topicEntity->getProjectId();
    }
}
