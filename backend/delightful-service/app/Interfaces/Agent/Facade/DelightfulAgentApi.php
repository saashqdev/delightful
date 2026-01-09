<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Agent\Facade;

use App\Application\Agent\Service\AgentAppService;
use App\Application\Agent\Service\DelightfulAgentAppService;
use App\Application\Chat\Service\DelightfulAccountAppService;
use App\Application\Chat\Service\DelightfulUserContactAppService;
use App\Domain\Agent\Constant\InstructGroupPosition;
use App\Domain\Agent\Constant\InstructType;
use App\Domain\Agent\Constant\DelightfulAgentQueryStatus;
use App\Domain\Agent\Constant\DelightfulAgentVersionStatus;
use App\Domain\Agent\Constant\StatusIcon;
use App\Domain\Agent\Constant\SystemInstructType;
use App\Domain\Agent\Constant\TextColor;
use App\Domain\Agent\DTO\DelightfulAgentDTO;
use App\Domain\Agent\DTO\DelightfulAgentVersionDTO;
use App\Domain\Agent\Entity\DelightfulAgentVersionEntity;
use App\Domain\Agent\Entity\ValueObject\Query\DelightfulAgentQuery;
use App\Domain\Contact\Entity\DelightfulUserEntity;
use App\Domain\Contact\Entity\ValueObject\AddFriendType;
use App\Infrastructure\Core\ValueObject\Page;
use App\Interfaces\Agent\Assembler\AgentAssembler;
use App\Interfaces\Agent\Assembler\FileAssembler;
use App\Interfaces\Agent\Assembler\DelightfulAgentAssembler;
use App\Interfaces\Agent\Assembler\DelightfulBotThirdPlatformChatAssembler;
use App\Interfaces\Agent\DTO\DelightfulBotThirdPlatformChatDTO;
use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use App\Interfaces\Flow\Assembler\Flow\DelightfulFlowAssembler;
use App\Interfaces\Flow\DTO\Flow\DelightfulFlowDTO;
use Delightful\ApiResponse\Annotation\ApiResponse;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Throwable;

#[ApiResponse('low_code')]
class DelightfulAgentApi extends AbstractApi
{
    #[Inject]
    protected DelightfulAgentAppService $delightfulAgentAppService;

    #[Inject]
    protected DelightfulUserContactAppService $userAppService;

    #[Inject]
    protected DelightfulAccountAppService $accountAppService;

    #[Inject]
    protected DelightfulAgentAssembler $delightfulAgentAssembler;

    #[Inject]
    protected DelightfulBotThirdPlatformChatAssembler $delightfulAgentThirdPlatformChatAssembler;

    #[Inject]
    protected AgentAppService $agentAppService;

    public function queries()
    {
        /** @var DelightfulUserAuthorization $authentication */
        $authentication = $this->getAuthorization();
        $inputs = $this->request->all();
        $query = new DelightfulAgentQuery($inputs);
        $agentName = $inputs['agent_name'] ?? $inputs['robot_name'] ?? '';
        $query->setOrder(['id' => 'desc']);
        $page = $this->createPage();
        $query->setAgentName($agentName);
        $data = $this->delightfulAgentAppService->queries($authentication, $query, $page);
        return $this->delightfulAgentAssembler->createPageListAgentDTO($data['total'], $data['list'], $page, $data['avatars']);
    }

    public function queriesAvailable()
    {
        /** @var DelightfulUserAuthorization $authentication */
        $authentication = $this->getAuthorization();
        $inputs = $this->request->all();
        $query = new DelightfulAgentQuery($inputs);
        $query->setOrder(['id' => 'desc']);
        $page = Page::createNoPage();
        $data = $this->agentAppService->queriesAvailable($authentication, $query, $page);
        return AgentAssembler::createAvailableList($page, $data['total'], $data['list'], $data['icons']);
    }

    // create/modify助理
    public function saveAgent(RequestInterface $request, ?string $agentId = null)
    {
        /** @var DelightfulUserAuthorization $authorization */
        $authorization = $this->getAuthorization();
        $inputs = $request->all();

        $agentId = $agentId ?? $inputs['id'] ?? '';

        $delightfulAgentDTO = new DelightfulAgentDTO();
        $userId = $authorization->getId();
        $organizationCode = $authorization->getOrganizationCode();
        $agentName = $inputs['agent_name'] ?? $inputs['robot_name'] ?? '';
        $agentAvatar = $inputs['agent_avatar'] ?? $inputs['robot_avatar'] ?? '';
        $agentDescription = $inputs['agent_description'] ?? $inputs['robot_description'] ?? '';
        $delightfulAgentDTO->setCurrentUserId($userId);
        $delightfulAgentDTO->setCurrentOrganizationCode($organizationCode);

        $delightfulAgentDTO->setAgentAvatar(FileAssembler::formatPath($agentAvatar));
        $delightfulAgentDTO->setAgentName($agentName);
        $delightfulAgentDTO->setAgentDescription($agentDescription);

        $delightfulAgentDTO->setRobotAvatar(FileAssembler::formatPath($agentAvatar));
        $delightfulAgentDTO->setRobotName($agentName);
        $delightfulAgentDTO->setRobotDescription($agentDescription);

        $delightfulAgentDTO->setId($agentId);

        $delightfulAgentEntity = $this->delightfulAgentAppService->saveAgent($authorization, $delightfulAgentDTO);
        $entityArray = $delightfulAgentEntity->toArray();
        $entityArray['robot_avatar'] = $delightfulAgentEntity->getAgentAvatar();
        $entityArray['robot_version_id'] = $delightfulAgentEntity->getAgentVersionId();
        $entityArray['robot_name'] = $delightfulAgentEntity->getAgentName();
        $entityArray['bot_description'] = $delightfulAgentEntity->getAgentDescription();
        return $entityArray;
    }

    // delete助理
    public function deleteAgentById(RequestInterface $request, ?string $agentId = null)
    {
        /** @var DelightfulUserAuthorization $authorization */
        $authorization = $this->getAuthorization();
        $agentId = $agentId ?? $request->input('bot_id');
        return $this->delightfulAgentAppService->deleteAgentById($authorization, $agentId);
    }

    // getcurrentuser助理

    /**
     * @deprecated
     */
    public function getAgentsByUserId(RequestInterface $request)
    {
        /** @var DelightfulUserAuthorization $authenticatable */
        $authenticatable = $this->getAuthorization();
        $page = (int) $request->input('page', 1);
        $pageSize = (int) $request->input('page_size', 10);
        $agentName = $request->input('agent_name') ?? $request->input('robot_name') ?? '';
        $queryType = $request->input('query_type', DelightfulAgentQueryStatus::ALL->value);
        $userId = $authenticatable->getId();
        $agentsByUserIdPage = $this->delightfulAgentAppService->getAgentsByUserIdPage($userId, $page, $pageSize, $agentName, DelightfulAgentQueryStatus::from($queryType));
        foreach ($agentsByUserIdPage['list'] as &$agent) {
            $agent['bot_version_id'] = $agent['agent_version_id'];
            $agent['robot_avatar'] = $agent['agent_avatar'];
            $agent['robot_name'] = $agent['agent_name'];
            $agent['robot_description'] = $agent['agent_description'];
            $agent['bot_version'] = $agent['agent_version'];
        }
        return $agentsByUserIdPage;
    }

    // getpublishversion助理
    public function getAgentVersionById(RequestInterface $request, ?string $agentVersionId = null)
    {
        /** @var DelightfulUserAuthorization $authenticatable */
        $authenticatable = $this->getAuthorization();
        $agentVersionId = $agentVersionId ?? $request->input('bot_version_id');
        $delightfulAgentVO = $this->delightfulAgentAppService->getAgentVersionByIdForUser($agentVersionId, $authenticatable);
        $delightfulFlowDTO = DelightfulFlowAssembler::createDelightfulFlowDTO($delightfulAgentVO->getDelightfulFlowEntity());
        return $this->delightfulAgentAssembler::createAgentV1Response($delightfulAgentVO, $delightfulFlowDTO);
    }

    // get企业inside部助理
    public function getAgentsByOrganization(RequestInterface $request)
    {
        /** @var DelightfulUserAuthorization $authenticatable */
        $authenticatable = $this->getAuthorization();
        $page = (int) $request->input('page', 1);
        $pageSize = (int) $request->input('page_size', 10);
        $agentName = $request->input('agent_name') ?? $request->input('robot_name') ?? '';
        return $this->delightfulAgentAppService->getAgentsByOrganizationPage($authenticatable, $page, $pageSize, $agentName);
    }

    // getapplication市场助理
    public function getAgentsFromMarketplace(RequestInterface $request)
    {
        $this->getAuthorization();
        $page = (int) $request->input('page', 1);
        $pageSize = (int) $request->input('page_size', 10);
        return $this->delightfulAgentAppService->getAgentsFromMarketplacePage($page, $pageSize);
    }

    // publish助理version

    /**
     * @throws Throwable
     */
    public function releaseAgentVersion(RequestInterface $request)
    {
        /** @var DelightfulUserAuthorization $authorization */
        $authorization = $this->getAuthorization();
        $inputs = $request->all();
        $delightfulAgentVersionDTO = new DelightfulAgentVersionDTO($inputs);

        $agentId = $inputs['agent_id'] ?? $inputs['bot_id'];
        $delightfulAgentVersionDTO->setAgentId($agentId);

        $delightfulFlowDO = null;
        if (! empty($inputs['delightful_flow'])) {
            $delightfulFLowDTO = new DelightfulFlowDTO($inputs['delightful_flow']);
            $delightfulFlowDO = DelightfulFlowAssembler::createDelightfulFlowDO($delightfulFLowDTO);
        }

        $thirdPlatformList = null;
        if (isset($inputs['third_platform_list'])) {
            $thirdPlatformList = [];
            foreach ($inputs['third_platform_list'] as $thirdPlatform) {
                $thirdPlatformChatDTO = new DelightfulBotThirdPlatformChatDTO($thirdPlatform);
                $thirdPlatformList[] = $this->delightfulAgentThirdPlatformChatAssembler->createDO($thirdPlatformChatDTO);
            }
        }

        $result = $this->delightfulAgentAppService->releaseAgentVersion($authorization, $delightfulAgentVersionDTO, $delightfulFlowDO, $thirdPlatformList);
        /**
         * @var DelightfulAgentVersionEntity $delightfulAgentVersionEntity
         */
        $delightfulAgentVersionEntity = $result['data'];

        $userDTO = new DelightfulUserEntity();
        $userDTO->setAvatarUrl($delightfulAgentVersionEntity->getAgentAvatar());
        $userDTO->setNickName($delightfulAgentVersionEntity->getAgentName());
        $userDTO->setDescription($delightfulAgentVersionEntity->getAgentDescription());
        $userEntity = $this->accountAppService->aiRegister($userDTO, $authorization, $delightfulAgentVersionEntity->getFlowCode());
        $result['user'] = $userEntity;

        if ($result['is_add_friend']) {
            $friendId = $userEntity->getUserId();
            // addgood友，助理defaultagreegood友
            $this->userAppService->addFriend($authorization, $friendId, AddFriendType::PASS);
        }
        return $result;
    }

    // query助理versionrecord
    public function getReleaseAgentVersions(RequestInterface $request, ?string $agentId = null)
    {
        /** @var DelightfulUserAuthorization $authenticatable */
        $authenticatable = $this->getAuthorization();
        $agentId = $agentId ?? $request->input('bot_id');
        return $this->delightfulAgentAppService->getReleaseAgentVersions($authenticatable, $agentId);
    }

    // get助理mostnewversionnumber
    public function getAgentMaxVersion(RequestInterface $request, ?string $agentId = null)
    {
        /** @var DelightfulUserAuthorization $authorization */
        $authorization = $this->getAuthorization();
        $agentId = $agentId ?? $request->input('bot_id');
        return $this->delightfulAgentAppService->getAgentMaxVersion($authorization, $agentId);
    }

    // enable｜disable助理
    public function updateAgentStatus(RequestInterface $request, ?string $agentId = null)
    {
        /** @var DelightfulUserAuthorization $authorization */
        $authorization = $this->getAuthorization();
        $agentId = $agentId ?? $request->input('bot_id');
        $status = (int) $request->input('status');
        $this->delightfulAgentAppService->updateAgentStatus($authorization, $agentId, DelightfulAgentVersionStatus::from($status));
    }

    // alter助理publishtoorganizationstatus
    public function updateAgentEnterpriseStatus(RequestInterface $request, ?string $agentId = null)
    {
        /** @var DelightfulUserAuthorization $authorization */
        $authorization = $this->getAuthorization();
        $agentId = $agentId ?? $request->input('bot_id');
        $status = (int) $request->input('status');
        $this->delightfulAgentAppService->updateAgentEnterpriseStatus($authorization, $agentId, $status, $authorization->getId());
    }

    // get助理detail
    public function getAgentDetailByAgentId(RequestInterface $request, ?string $agentId = null)
    {
        /** @var DelightfulUserAuthorization $authenticatable */
        $authenticatable = $this->getAuthorization();
        $agentId = $agentId ?? $request->input('bot_id');
        $delightfulAgentAssembler = new DelightfulAgentAssembler();
        $delightfulAgentVO = $this->delightfulAgentAppService->getAgentDetail($agentId, $authenticatable);
        $delightfulFlowDTO = DelightfulFlowAssembler::createDelightfulFlowDTO($delightfulAgentVO->getDelightfulFlowEntity());
        return $delightfulAgentAssembler::createAgentV1Response($delightfulAgentVO, $delightfulFlowDTO);
    }

    /**
     * @throws Throwable
     */
    public function registerAgentAndAddFriend(RequestInterface $request, ?string $agentVersionId = null)
    {
        /** @var DelightfulUserAuthorization $authorization */
        $authorization = $this->getAuthorization();
        $agentVersionId = $agentVersionId ?? $request->input('bot_version_id');
        $delightfulAgentVersionEntity = $this->delightfulAgentAppService->getAgentById($agentVersionId, $authorization);
        $userDTO = DelightfulUserEntity::fromDelightfulAgentVersionEntity($delightfulAgentVersionEntity);
        $aiCode = $delightfulAgentVersionEntity->getFlowCode();
        $userEntity = $this->accountAppService->aiRegister($userDTO, $authorization, $aiCode);
        $friendId = $userEntity->getUserId();
        // addgood友，助理defaultagreegood友
        $this->userAppService->addFriend($authorization, $friendId, AddFriendType::PASS);

        return $userEntity;
    }

    public function isUpdated(RequestInterface $request, ?string $agentId = null)
    {
        /** @var DelightfulUserAuthorization $authenticatable */
        $authenticatable = $this->getAuthorization();
        $agentId = $agentId ?? $request->input('bot_id');
        return $this->delightfulAgentAppService->isUpdated($authenticatable, $agentId);
    }

    // according to userId getpublishversion助理detail
    public function getDetailByUserId(RequestInterface $request, ?string $userId = null)
    {
        $this->getAuthorization();
        $userId = $userId ?? $request->input('user_id');
        $delightfulAgentVersionEntity = $this->delightfulAgentAppService->getDetailByUserId($userId);
        if (! $delightfulAgentVersionEntity) {
            return [];
        }
        return $delightfulAgentVersionEntity->toArray();
    }

    // get交互finger令type
    public function getInstructTypeOptions()
    {
        return InstructType::getTypeOptions();
    }

    // get交互finger令grouptype
    public function getInstructGroupTypeOptions()
    {
        return InstructGroupPosition::getTypeOptions();
    }

    public function getInstructionStateColorOptions()
    {
        return TextColor::getColorOptions();
    }

    public function getInstructionIconColorOptions()
    {
        return StatusIcon::getValues();
    }

    public function getSystemInstructTypeOptions()
    {
        return SystemInstructType::getTypeOptions();
    }

    public function saveInstruct(RequestInterface $request, ?string $agentId = null)
    {
        /** @var DelightfulUserAuthorization $authenticatable */
        $authenticatable = $this->getAuthorization();
        $instructs = $request->input('instructs');
        $agentId = $agentId ?? $request->input('bot_id');
        return $this->delightfulAgentAppService->saveInstruct($authenticatable, $agentId, $instructs);
    }

    // getchat模typecanuse助理list
    public function getChatModeAvailableAgents()
    {
        /** @var DelightfulUserAuthorization $authenticatable */
        $authenticatable = $this->getAuthorization();
        $inputs = $this->request->all();
        $query = new DelightfulAgentQuery($inputs);
        $query->setOrder(['id' => 'desc']);

        // createpaginationobject
        $page = $this->createPage();

        // getallquantitydata
        $data = $this->delightfulAgentAppService->getChatModeAvailableAgents($authenticatable, $query);

        // in API layerconductpaginationprocess
        return AgentAssembler::createChatModelAvailableList($page, $data['total'], $data['list']);
    }
}
