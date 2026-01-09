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
 * ASR 验证service
 * 负责项目permission、话题归属、taskstatus等验证逻辑.
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
     * 验证项目permission - 确保项目属于当前user和organization.
     *
     * @param string $projectId 项目ID
     * @param string $userId userID
     * @param string $organizationCode organization编码
     * @return ProjectEntity 项目实体
     */
    public function validateProjectAccess(string $projectId, string $userId, string $organizationCode): ProjectEntity
    {
        try {
            // get项目info
            $projectEntity = $this->projectDomainService->getProjectNotUserId((int) $projectId);
            if ($projectEntity === null) {
                ExceptionBuilder::throw(BeAgentErrorCode::PROJECT_NOT_FOUND);
            }

            // 校验项目是否属于当前organization
            if ($projectEntity->getUserOrganizationCode() !== $organizationCode) {
                ExceptionBuilder::throw(AsrErrorCode::ProjectAccessDeniedOrganization);
            }

            // 校验项目是否属于当前user
            if ($projectEntity->getUserId() === $userId) {
                return $projectEntity;
            }

            // checkuser是否是项目成员
            if ($this->projectMemberDomainService->isProjectMemberByUser((int) $projectId, $userId)) {
                return $projectEntity;
            }

            // checkuser所在department是否有项目permission
            $dataIsolation = DataIsolation::create($organizationCode, $userId);
            $departmentIds = $this->delightfulDepartmentUserDomainService->getDepartmentIdsByUserId($dataIsolation, $userId, true);

            if (! empty($departmentIds) && $this->projectMemberDomainService->isProjectMemberByDepartments((int) $projectId, $departmentIds)) {
                return $projectEntity;
            }

            // 所有permissioncheck都fail
            ExceptionBuilder::throw(AsrErrorCode::ProjectAccessDeniedUser);
        } catch (BusinessException $e) {
            // 处理 ExceptionBuilder::throw 抛出的业务exception
            if ($e->getCode() === BeAgentErrorCode::PROJECT_NOT_FOUND->value) {
                ExceptionBuilder::throw(AsrErrorCode::ProjectNotFound);
            }
            if ($e->getCode() >= 43000 && $e->getCode() < 44000) {
                // 已经是 AsrErrorCode，直接重新抛出
                throw $e;
            }

            // 其他业务exception转换为permission验证fail
            ExceptionBuilder::throw(AsrErrorCode::ProjectAccessValidationFailed, '', ['error' => $e->getMessage()]);
        } catch (Throwable $e) {
            // 其他exception统一处理为permission验证fail
            ExceptionBuilder::throw(AsrErrorCode::ProjectAccessValidationFailed, '', ['error' => $e->getMessage()]);
        }
    }

    /**
     * 验证话题归属.
     *
     * @param int $topicId 话题ID
     * @param string $userId userID
     * @return TopicEntity 话题实体
     */
    public function validateTopicOwnership(int $topicId, string $userId): TopicEntity
    {
        $topicEntity = $this->topicDomainService->getTopicById($topicId);

        if ($topicEntity === null) {
            ExceptionBuilder::throw(BeAgentErrorCode::TOPIC_NOT_FOUND);
        }

        // 验证话题属于当前user
        if ($topicEntity->getUserId() !== $userId) {
            ExceptionBuilder::throw(BeAgentErrorCode::TOPIC_NOT_FOUND);
        }

        return $topicEntity;
    }

    /**
     * 验证并gettaskstatus.
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

        // 验证userID匹配（基本的安全check）
        if ($taskStatus->userId !== $userId) {
            ExceptionBuilder::throw(AsrErrorCode::TaskNotBelongToUser);
        }

        return $taskStatus;
    }

    /**
     * 从话题get项目ID（包含话题归属验证）.
     *
     * @param int $topicId 话题ID
     * @param string $userId userID
     * @return string 项目ID
     */
    public function getProjectIdFromTopic(int $topicId, string $userId): string
    {
        $topicEntity = $this->validateTopicOwnership($topicId, $userId);
        return (string) $topicEntity->getProjectId();
    }
}
