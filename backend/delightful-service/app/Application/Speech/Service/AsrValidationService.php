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
 * responsibleprojectpermission,topicbelong to,taskstatusetcverifylogic.
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
     * verifyprojectpermission - ensureprojectbelongatcurrentuserandorganization.
     *
     * @param string $projectId projectID
     * @param string $userId userID
     * @param string $organizationCode organizationencoding
     * @return ProjectEntity projectactualbody
     */
    public function validateProjectAccess(string $projectId, string $userId, string $organizationCode): ProjectEntity
    {
        try {
            // getprojectinfo
            $projectEntity = $this->projectDomainService->getProjectNotUserId((int) $projectId);
            if ($projectEntity === null) {
                ExceptionBuilder::throw(BeAgentErrorCode::PROJECT_NOT_FOUND);
            }

            // validationprojectwhetherbelongatcurrentorganization
            if ($projectEntity->getUserOrganizationCode() !== $organizationCode) {
                ExceptionBuilder::throw(AsrErrorCode::ProjectAccessDeniedOrganization);
            }

            // validationprojectwhetherbelongatcurrentuser
            if ($projectEntity->getUserId() === $userId) {
                return $projectEntity;
            }

            // checkuserwhetherisprojectmember
            if ($this->projectMemberDomainService->isProjectMemberByUser((int) $projectId, $userId)) {
                return $projectEntity;
            }

            // checkuser indepartmentwhetherhaveprojectpermission
            $dataIsolation = DataIsolation::create($organizationCode, $userId);
            $departmentIds = $this->delightfulDepartmentUserDomainService->getDepartmentIdsByUserId($dataIsolation, $userId, true);

            if (! empty($departmentIds) && $this->projectMemberDomainService->isProjectMemberByDepartments((int) $projectId, $departmentIds)) {
                return $projectEntity;
            }

            //  havepermissioncheckallfail
            ExceptionBuilder::throw(AsrErrorCode::ProjectAccessDeniedUser);
        } catch (BusinessException $e) {
            // process ExceptionBuilder::throw throwbusinessexception
            if ($e->getCode() === BeAgentErrorCode::PROJECT_NOT_FOUND->value) {
                ExceptionBuilder::throw(AsrErrorCode::ProjectNotFound);
            }
            if ($e->getCode() >= 43000 && $e->getCode() < 44000) {
                // alreadyalreadyis AsrErrorCode,directlyreloadnewthrow
                throw $e;
            }

            // otherbusinessexceptionconvertforpermissionverifyfail
            ExceptionBuilder::throw(AsrErrorCode::ProjectAccessValidationFailed, '', ['error' => $e->getMessage()]);
        } catch (Throwable $e) {
            // otherexception统oneprocessforpermissionverifyfail
            ExceptionBuilder::throw(AsrErrorCode::ProjectAccessValidationFailed, '', ['error' => $e->getMessage()]);
        }
    }

    /**
     * verifytopicbelong to.
     *
     * @param int $topicId topicID
     * @param string $userId userID
     * @return TopicEntity topicactualbody
     */
    public function validateTopicOwnership(int $topicId, string $userId): TopicEntity
    {
        $topicEntity = $this->topicDomainService->getTopicById($topicId);

        if ($topicEntity === null) {
            ExceptionBuilder::throw(BeAgentErrorCode::TOPIC_NOT_FOUND);
        }

        // verifytopicbelongatcurrentuser
        if ($topicEntity->getUserId() !== $userId) {
            ExceptionBuilder::throw(BeAgentErrorCode::TOPIC_NOT_FOUND);
        }

        return $topicEntity;
    }

    /**
     * verifyandgettaskstatus.
     *
     * @param string $taskKey taskkey
     * @param string $userId userID
     * @return AsrTaskStatusDTO taskstatusDTO
     */
    public function validateTaskStatus(string $taskKey, string $userId): AsrTaskStatusDTO
    {
        $taskStatus = $this->asrTaskDomainService->findTaskByKey($taskKey, $userId);

        if ($taskStatus === null) {
            ExceptionBuilder::throw(AsrErrorCode::UploadAudioFirst);
        }

        // verifyuserIDmatch(basicsecuritycheck)
        if ($taskStatus->userId !== $userId) {
            ExceptionBuilder::throw(AsrErrorCode::TaskNotBelongToUser);
        }

        return $taskStatus;
    }

    /**
     * fromtopicgetprojectID(containtopicbelong toverify).
     *
     * @param int $topicId topicID
     * @param string $userId userID
     * @return string projectID
     */
    public function getProjectIdFromTopic(int $topicId, string $userId): string
    {
        $topicEntity = $this->validateTopicOwnership($topicId, $userId);
        return (string) $topicEntity->getProjectId();
    }
}
