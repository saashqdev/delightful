<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Contact\Service;

use App\Domain\Authentication\Service\PasswordService;
use App\Domain\Chat\Repository\Facade\DelightfulChatConversationRepositoryInterface;
use App\Domain\Chat\Repository\Facade\DelightfulChatFileRepositoryInterface;
use App\Domain\Chat\Repository\Facade\DelightfulChatSeqRepositoryInterface;
use App\Domain\Chat\Repository\Facade\DelightfulChatTopicRepositoryInterface;
use App\Domain\Chat\Repository\Facade\DelightfulContactIdMappingRepositoryInterface;
use App\Domain\Chat\Repository\Facade\DelightfulFriendRepositoryInterface;
use App\Domain\Chat\Repository\Facade\DelightfulMessageRepositoryInterface;
use App\Domain\Chat\Repository\Persistence\DelightfulContactIdMappingRepository;
use App\Domain\Contact\Repository\Facade\DelightfulAccountRepositoryInterface;
use App\Domain\Contact\Repository\Facade\DelightfulDepartmentRepositoryInterface;
use App\Domain\Contact\Repository\Facade\DelightfulDepartmentUserRepositoryInterface;
use App\Domain\Contact\Repository\Facade\DelightfulUserIdRelationRepositoryInterface;
use App\Domain\Contact\Repository\Facade\DelightfulUserRepositoryInterface;
use App\Domain\File\Repository\Persistence\Facade\CloudFileRepositoryInterface;
use App\Domain\Flow\Repository\Facade\DelightfulFlowAIModelRepositoryInterface;
use App\Domain\Group\Repository\Facade\DelightfulGroupRepositoryInterface;
use App\Domain\OrganizationEnvironment\Repository\Facade\EnvironmentRepositoryInterface;
use App\Domain\OrganizationEnvironment\Repository\Facade\OrganizationsEnvironmentRepositoryInterface;
use App\Domain\OrganizationEnvironment\Repository\Facade\OrganizationsPlatformRepositoryInterface;
use App\Domain\Token\Repository\Facade\DelightfulTokenRepositoryInterface;
use App\Infrastructure\ExternalAPI\Sms\SmsInterface;
use App\Infrastructure\ExternalAPI\Sms\TemplateInterface;
use App\Infrastructure\Util\Locker\LockerInterface;
use App\Infrastructure\Util\Locker\RedisLocker;
use Hyperf\Amqp\Producer;
use Hyperf\Context\ApplicationContext;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Redis\Redis;
use Hyperf\Snowflake\IdGeneratorInterface;
use Psr\Log\LoggerInterface;
use Throwable;

abstract class AbstractContactDomainService
{
    public function __construct(
        protected DelightfulUserRepositoryInterface $userRepository,
        protected IdGeneratorInterface $idGenerator,
        protected Redis $redis,
        protected SmsInterface $sms,
        protected TemplateInterface $template,
        protected DelightfulAccountRepositoryInterface $accountRepository,
        protected LockerInterface $locker,
        protected PasswordService $passwordService,
        protected DelightfulMessageRepositoryInterface $magicMessageRepository,
        protected DelightfulChatSeqRepositoryInterface $magicSeqRepository,
        protected DelightfulAccountRepositoryInterface $magicAccountRepository,
        protected DelightfulChatConversationRepositoryInterface $magicConversationRepository,
        protected RedisLocker $redisLocker,
        protected Producer $producer,
        protected DelightfulChatTopicRepositoryInterface $magicChatTopicRepository,
        protected DelightfulGroupRepositoryInterface $magicGroupRepository,
        protected DelightfulChatFileRepositoryInterface $magicFileRepository,
        protected readonly DelightfulFriendRepositoryInterface $friendRepository,
        protected readonly DelightfulUserIdRelationRepositoryInterface $userIdRelationRepository,
        protected readonly DelightfulContactIdMappingRepositoryInterface $contactThirdPlatformIdMappingRepository,
        protected readonly DelightfulContactIdMappingRepository $contactIdMappingRepository,
        protected readonly OrganizationsEnvironmentRepositoryInterface $magicOrganizationsEnvironmentRepository,
        protected readonly DelightfulTokenRepositoryInterface $magicTokenRepository,
        protected readonly EnvironmentRepositoryInterface $magicEnvironmentsRepository,
        protected readonly DelightfulFlowAIModelRepositoryInterface $magicFlowAIModelRepository,
        protected LoggerInterface $logger,
        protected DelightfulDepartmentUserRepositoryInterface $departmentUserRepository,
        protected readonly DelightfulContactIdMappingRepositoryInterface $thirdPlatformIdMappingRepository,
        protected readonly DelightfulDepartmentRepositoryInterface $departmentRepository,
        protected CloudFileRepositoryInterface $cloudFileRepository,
        protected readonly OrganizationsPlatformRepositoryInterface $organizationsPlatformRepository,
    ) {
        try {
            $this->logger = ApplicationContext::getContainer()->get(LoggerFactory::class)->get(get_class($this));
        } catch (Throwable) {
        }
    }
}
