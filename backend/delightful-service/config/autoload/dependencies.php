<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use App\Application\Chat\Service\DelightfulAgentEventAppService;
use App\Application\Chat\Service\SessionAppService;
use App\Application\Flow\ExecuteManager\NodeRunner\Cache\StringCache\MysqlStringCache;
use App\Application\Flow\ExecuteManager\NodeRunner\Cache\StringCache\StringCacheInterface;
use App\Application\Flow\ExecuteManager\NodeRunner\Code\CodeExecutor\PHPExecutor;
use App\Application\Flow\ExecuteManager\NodeRunner\Code\CodeExecutor\PythonExecutor;
use App\Application\Flow\ExecuteManager\NodeRunner\ReplyMessage\Struct\BaseMessageAttachmentHandler;
use App\Application\Flow\ExecuteManager\NodeRunner\ReplyMessage\Struct\MessageAttachmentHandlerInterface;
use App\Application\Kernel\Contract\DelightfulPermissionInterface;
use App\Application\Kernel\DelightfulPermission;
use App\Application\KnowledgeBase\Service\Strategy\DocumentFile\Driver\ExternalFileDocumentFileStrategyDriver;
use App\Application\KnowledgeBase\Service\Strategy\DocumentFile\Driver\Interfaces\ExternalFileDocumentFileStrategyInterface;
use App\Application\KnowledgeBase\Service\Strategy\DocumentFile\Driver\Interfaces\ThirdPlatformDocumentFileStrategyInterface;
use App\Application\KnowledgeBase\Service\Strategy\DocumentFile\Driver\ThirdPlatformDocumentFileStrategyDriver;
use App\Application\KnowledgeBase\Service\Strategy\KnowledgeBase\BaseKnowledgeBaseStrategy;
use App\Application\KnowledgeBase\Service\Strategy\KnowledgeBase\KnowledgeBaseStrategyInterface;
use App\Application\KnowledgeBase\VectorDatabase\Similarity\Driver\BaseFullTextSimilaritySearch;
use App\Application\KnowledgeBase\VectorDatabase\Similarity\Driver\BaseGraphSimilaritySearch;
use App\Application\KnowledgeBase\VectorDatabase\Similarity\Driver\BaseHybridSimilaritySearch;
use App\Application\KnowledgeBase\VectorDatabase\Similarity\Driver\BaseSemanticSimilaritySearch;
use App\Application\KnowledgeBase\VectorDatabase\Similarity\Driver\FullTextSimilaritySearchInterface;
use App\Application\KnowledgeBase\VectorDatabase\Similarity\Driver\GraphSimilaritySearchInterface;
use App\Application\KnowledgeBase\VectorDatabase\Similarity\Driver\HybridSimilaritySearchInterface;
use App\Application\KnowledgeBase\VectorDatabase\Similarity\Driver\SemanticSimilaritySearchInterface;
use App\Application\MCP\BeDelightfulMCP\BeDelightfulAgentMCP;
use App\Application\MCP\BeDelightfulMCP\BeDelightfulAgentMCPInterface;
use App\Application\MCP\Utils\MCPExecutor\ExternalHttpExecutor;
use App\Application\MCP\Utils\MCPExecutor\ExternalHttpExecutorInterface;
use App\Application\MCP\Utils\MCPExecutor\ExternalStdioExecutor;
use App\Application\MCP\Utils\MCPExecutor\ExternalStdioExecutorInterface;
use App\Application\ModelGateway\Component\Points\PointComponent;
use App\Application\ModelGateway\Component\Points\PointComponentInterface;
use App\Domain\Admin\Repository\Facade\AdminGlobalSettingsRepositoryInterface;
use App\Domain\Admin\Repository\Persistence\AdminGlobalSettingsRepository;
use App\Domain\Agent\Repository\Facade\AgentRepositoryInterface;
use App\Domain\Agent\Repository\Facade\AgentVersionRepositoryInterface;
use App\Domain\Agent\Repository\Facade\DelightfulBotThirdPlatformChatRepositoryInterface;
use App\Domain\Agent\Repository\Persistence\AgentRepository;
use App\Domain\Agent\Repository\Persistence\AgentVersionRepository;
use App\Domain\Agent\Repository\Persistence\DelightfulBotThirdPlatformChatRepository;
use App\Domain\Authentication\Repository\ApiKeyProviderRepository;
use App\Domain\Authentication\Repository\Facade\ApiKeyProviderRepositoryInterface;
use App\Domain\Authentication\Repository\Facade\AuthenticationRepositoryInterface;
use App\Domain\Authentication\Repository\Implement\AuthenticationRepository;
use App\Domain\Chat\DTO\Message\ChatMessage\BeAgentMessageInterface;
use App\Domain\Chat\DTO\Message\ChatMessage\UnknowChatMessage;
use App\Domain\Chat\Event\Agent\AgentExecuteInterface;
use App\Domain\Chat\Repository\Facade\DelightfulChatConversationRepositoryInterface;
use App\Domain\Chat\Repository\Facade\DelightfulChatFileRepositoryInterface;
use App\Domain\Chat\Repository\Facade\DelightfulChatMessageVersionsRepositoryInterface;
use App\Domain\Chat\Repository\Facade\DelightfulChatSeqRepositoryInterface;
use App\Domain\Chat\Repository\Facade\DelightfulChatTopicRepositoryInterface;
use App\Domain\Chat\Repository\Facade\DelightfulContactIdMappingRepositoryInterface;
use App\Domain\Chat\Repository\Facade\DelightfulFriendRepositoryInterface;
use App\Domain\Chat\Repository\Facade\DelightfulMessageRepositoryInterface;
use App\Domain\Chat\Repository\Persistence\DelightfulChatConversationRepository;
use App\Domain\Chat\Repository\Persistence\DelightfulChatFileRepository;
use App\Domain\Chat\Repository\Persistence\DelightfulChatSeqRepository;
use App\Domain\Chat\Repository\Persistence\DelightfulChatTopicRepository;
use App\Domain\Chat\Repository\Persistence\DelightfulContactIdMappingRepository;
use App\Domain\Chat\Repository\Persistence\DelightfulFriendRepository;
use App\Domain\Chat\Repository\Persistence\DelightfulMessageRepository;
use App\Domain\Chat\Repository\Persistence\DelightfulMessageVersionsRepository;
use App\Domain\Chat\Service\MessageContentProvider;
use App\Domain\Chat\Service\MessageContentProviderInterface;
use App\Domain\Contact\Repository\Facade\DelightfulAccountRepositoryInterface;
use App\Domain\Contact\Repository\Facade\DelightfulDepartmentRepositoryInterface;
use App\Domain\Contact\Repository\Facade\DelightfulDepartmentUserRepositoryInterface;
use App\Domain\Contact\Repository\Facade\DelightfulUserIdRelationRepositoryInterface;
use App\Domain\Contact\Repository\Facade\DelightfulUserRepositoryInterface;
use App\Domain\Contact\Repository\Facade\DelightfulUserSettingRepositoryInterface;
use App\Domain\Contact\Repository\Persistence\DelightfulAccountRepository;
use App\Domain\Contact\Repository\Persistence\DelightfulDepartmentRepository;
use App\Domain\Contact\Repository\Persistence\DelightfulDepartmentUserRepository;
use App\Domain\Contact\Repository\Persistence\DelightfulUserIdRelationRepository;
use App\Domain\Contact\Repository\Persistence\DelightfulUserRepository;
use App\Domain\Contact\Repository\Persistence\DelightfulUserSettingRepository;
use App\Domain\Contact\Service\Facade\DelightfulUserDomainExtendInterface;
use App\Domain\Contact\Service\DelightfulUserDomainExtendService;
use App\Domain\File\Repository\Persistence\CloudFileRepository;
use App\Domain\File\Repository\Persistence\Facade\CloudFileRepositoryInterface;
use App\Domain\Flow\Repository\Facade\DelightfulFlowAIModelRepositoryInterface;
use App\Domain\Flow\Repository\Facade\DelightfulFlowApiKeyRepositoryInterface;
use App\Domain\Flow\Repository\Facade\DelightfulFlowCacheRepositoryInterface;
use App\Domain\Flow\Repository\Facade\DelightfulFlowDraftRepositoryInterface;
use App\Domain\Flow\Repository\Facade\DelightfulFlowExecuteLogRepositoryInterface;
use App\Domain\Flow\Repository\Facade\DelightfulFlowMemoryHistoryRepositoryInterface;
use App\Domain\Flow\Repository\Facade\DelightfulFlowMultiModalLogRepositoryInterface;
use App\Domain\Flow\Repository\Facade\DelightfulFlowPermissionRepositoryInterface;
use App\Domain\Flow\Repository\Facade\DelightfulFlowRepositoryInterface;
use App\Domain\Flow\Repository\Facade\DelightfulFlowToolSetRepositoryInterface;
use App\Domain\Flow\Repository\Facade\DelightfulFlowTriggerTestcaseRepositoryInterface;
use App\Domain\Flow\Repository\Facade\DelightfulFlowVersionRepositoryInterface;
use App\Domain\Flow\Repository\Facade\DelightfulFlowWaitMessageRepositoryInterface;
use App\Domain\Flow\Repository\Persistence\DelightfulFlowAIModelRepository;
use App\Domain\Flow\Repository\Persistence\DelightfulFlowApiKeyRepository;
use App\Domain\Flow\Repository\Persistence\DelightfulFlowCacheRepository;
use App\Domain\Flow\Repository\Persistence\DelightfulFlowDraftRepository;
use App\Domain\Flow\Repository\Persistence\DelightfulFlowExecuteLogRepository;
use App\Domain\Flow\Repository\Persistence\DelightfulFlowMemoryHistoryRepository;
use App\Domain\Flow\Repository\Persistence\DelightfulFlowMultiModalLogRepository;
use App\Domain\Flow\Repository\Persistence\DelightfulFlowPermissionRepository;
use App\Domain\Flow\Repository\Persistence\DelightfulFlowRepository;
use App\Domain\Flow\Repository\Persistence\DelightfulFlowToolSetRepository;
use App\Domain\Flow\Repository\Persistence\DelightfulFlowTriggerTestcaseRepository;
use App\Domain\Flow\Repository\Persistence\DelightfulFlowVersionRepository;
use App\Domain\Flow\Repository\Persistence\DelightfulFlowWaitMessageRepository;
use App\Domain\Group\Repository\Facade\DelightfulGroupRepositoryInterface;
use App\Domain\Group\Repository\Persistence\DelightfulGroupRepository;
use App\Domain\ImageGenerate\Contract\FontProviderInterface;
use App\Domain\ImageGenerate\Contract\ImageEnhancementProcessorInterface;
use App\Domain\ImageGenerate\Contract\WatermarkConfigInterface;
use App\Domain\KnowledgeBase\Entity\ValueObject\DocumentFile\ExternalDocumentFile;
use App\Domain\KnowledgeBase\Entity\ValueObject\DocumentFile\Interfaces\ExternalDocumentFileInterface;
use App\Domain\KnowledgeBase\Entity\ValueObject\DocumentFile\Interfaces\ThirdPlatformDocumentFileInterface;
use App\Domain\KnowledgeBase\Entity\ValueObject\DocumentFile\ThirdPlatformDocumentFile;
use App\Domain\KnowledgeBase\Repository\Facade\KnowledgeBaseDocumentRepositoryInterface;
use App\Domain\KnowledgeBase\Repository\Facade\KnowledgeBaseFragmentRepositoryInterface;
use App\Domain\KnowledgeBase\Repository\Facade\KnowledgeBaseRepositoryInterface;
use App\Domain\KnowledgeBase\Repository\Persistence\KnowledgeBaseBaseRepository;
use App\Domain\KnowledgeBase\Repository\Persistence\KnowledgeBaseDocumentRepository;
use App\Domain\KnowledgeBase\Repository\Persistence\KnowledgeBaseFragmentRepository;
use App\Domain\LongTermMemory\Repository\LongTermMemoryRepositoryInterface;
use App\Domain\MCP\Repository\Facade\MCPServerRepositoryInterface;
use App\Domain\MCP\Repository\Facade\MCPServerToolRepositoryInterface;
use App\Domain\MCP\Repository\Facade\MCPUserSettingRepositoryInterface;
use App\Domain\MCP\Repository\Persistence\MCPServerRepository;
use App\Domain\MCP\Repository\Persistence\MCPServerToolRepository;
use App\Domain\MCP\Repository\Persistence\MCPUserSettingRepository;
use App\Domain\Mode\Repository\Facade\ModeGroupRelationRepositoryInterface;
use App\Domain\Mode\Repository\Facade\ModeGroupRepositoryInterface;
use App\Domain\Mode\Repository\Facade\ModeRepositoryInterface;
use App\Domain\Mode\Repository\Persistence\ModeGroupRelationRepository;
use App\Domain\Mode\Repository\Persistence\ModeGroupRepository;
use App\Domain\Mode\Repository\Persistence\ModeRepository;
use App\Domain\ModelGateway\Repository\Facade\AccessTokenRepositoryInterface;
use App\Domain\ModelGateway\Repository\Facade\ApplicationRepositoryInterface;
use App\Domain\ModelGateway\Repository\Facade\ModelConfigRepositoryInterface;
use App\Domain\ModelGateway\Repository\Facade\MsgLogRepositoryInterface;
use App\Domain\ModelGateway\Repository\Facade\OrganizationConfigRepositoryInterface;
use App\Domain\ModelGateway\Repository\Facade\UserConfigRepositoryInterface;
use App\Domain\ModelGateway\Repository\Persistence\AccessTokenRepository;
use App\Domain\ModelGateway\Repository\Persistence\ApplicationRepository;
use App\Domain\ModelGateway\Repository\Persistence\ModelConfigRepository;
use App\Domain\ModelGateway\Repository\Persistence\MsgLogRepository;
use App\Domain\ModelGateway\Repository\Persistence\OrganizationConfigRepository;
use App\Domain\ModelGateway\Repository\Persistence\UserConfigRepository;
use App\Domain\OrganizationEnvironment\Entity\Facade\OpenPlatformConfigInterface;
use App\Domain\OrganizationEnvironment\Entity\Item\OpenPlatformConfigItem;
use App\Domain\OrganizationEnvironment\Repository\Facade\EnvironmentRepositoryInterface;
use App\Domain\OrganizationEnvironment\Repository\Facade\OrganizationRepositoryInterface;
use App\Domain\OrganizationEnvironment\Repository\Facade\OrganizationsEnvironmentRepositoryInterface;
use App\Domain\OrganizationEnvironment\Repository\Facade\OrganizationsPlatformRepositoryInterface;
use App\Domain\OrganizationEnvironment\Repository\DelightfulEnvironmentsRepository;
use App\Domain\OrganizationEnvironment\Repository\OrganizationsEnvironmentRepository;
use App\Domain\OrganizationEnvironment\Repository\OrganizationsPlatformRepository;
use App\Domain\Permission\Repository\Facade\OperationPermissionRepositoryInterface;
use App\Domain\Permission\Repository\Facade\OrganizationAdminRepositoryInterface;
use App\Domain\Permission\Repository\Facade\RoleRepositoryInterface;
use App\Domain\Permission\Repository\Persistence\OperationPermissionRepository;
use App\Domain\Provider\Repository\Facade\AiAbilityRepositoryInterface;
use App\Domain\Provider\Repository\Facade\DelightfulProviderAndModelsInterface;
use App\Domain\Provider\Repository\Facade\ProviderConfigRepositoryInterface;
use App\Domain\Provider\Repository\Facade\ProviderModelConfigVersionRepositoryInterface;
use App\Domain\Provider\Repository\Facade\ProviderModelRepositoryInterface;
use App\Domain\Provider\Repository\Facade\ProviderOriginalModelRepositoryInterface;
use App\Domain\Provider\Repository\Facade\ProviderRepositoryInterface;
use App\Domain\Provider\Repository\Persistence\AiAbilityRepository;
use App\Domain\Provider\Repository\Persistence\DelightfulProviderAndModelsRepository;
use App\Domain\Provider\Repository\Persistence\ProviderConfigRepository;
use App\Domain\Provider\Repository\Persistence\ProviderModelConfigVersionRepository;
use App\Domain\Provider\Repository\Persistence\ProviderModelRepository;
use App\Domain\Provider\Repository\Persistence\ProviderOriginalModelRepository;
use App\Domain\Provider\Repository\Persistence\ProviderRepository;
use App\Domain\Provider\Service\ModelFilter\DefaultOrganizationModelFilter;
use App\Domain\Provider\Service\ModelFilter\DefaultPackageFilter;
use App\Domain\Provider\Service\ModelFilter\OrganizationBasedModelFilterInterface;
use App\Domain\Provider\Service\ModelFilter\PackageFilterInterface;
use App\Domain\BeAgent\Service\UsageCalculator\DefaultUsageCalculator;
use App\Domain\BeAgent\Service\UsageCalculator\UsageCalculatorInterface;
use App\Domain\Token\Item\DelightfulTokenExtra;
use App\Domain\Token\Repository\Facade\DelightfulTokenExtraInterface;
use App\Domain\Token\Repository\Facade\DelightfulTokenRepositoryInterface;
use App\Domain\Token\Repository\Persistence\DelightfulDelightfulTokenRepository;
use App\Infrastructure\Core\Broadcast\Publisher\AmqpPublisher;
use App\Infrastructure\Core\Broadcast\Publisher\PublisherInterface;
use App\Infrastructure\Core\Broadcast\Subscriber\AmqpSubscriber;
use App\Infrastructure\Core\Broadcast\Subscriber\SubscriberInterface;
use App\Infrastructure\Core\Contract\Authorization\BaseFlowOpenApiCheck;
use App\Infrastructure\Core\Contract\Authorization\FlowOpenApiCheckInterface;
use App\Infrastructure\Core\Contract\Flow\CodeExecutor\PHPExecutorInterface;
use App\Infrastructure\Core\Contract\Flow\CodeExecutor\PythonExecutorInterface;
use App\Infrastructure\Core\Contract\Session\SessionInterface;
use App\Infrastructure\Core\DataIsolation\BaseHandleDataIsolation;
use App\Infrastructure\Core\DataIsolation\BaseSubscriptionManager;
use App\Infrastructure\Core\DataIsolation\BaseThirdPlatformDataIsolationManager;
use App\Infrastructure\Core\DataIsolation\HandleDataIsolationInterface;
use App\Infrastructure\Core\DataIsolation\SubscriptionManagerInterface;
use App\Infrastructure\Core\DataIsolation\ThirdPlatformDataIsolationManagerInterface;
use App\Infrastructure\Core\Embeddings\DocumentSplitter\DocumentSplitterInterface;
use App\Infrastructure\Core\Embeddings\DocumentSplitter\OdinRecursiveCharacterTextSplitter;
use App\Infrastructure\Core\Embeddings\EmbeddingGenerator\EmbeddingGeneratorInterface;
use App\Infrastructure\Core\Embeddings\EmbeddingGenerator\OdinEmbeddingGenerator;
use App\Infrastructure\Core\File\Parser\Driver\ExcelFileParserDriver;
use App\Infrastructure\Core\File\Parser\Driver\Interfaces\ExcelFileParserDriverInterface;
use App\Infrastructure\Core\File\Parser\Driver\Interfaces\OcrFileParserDriverInterface;
use App\Infrastructure\Core\File\Parser\Driver\Interfaces\PdfFileParserDriverInterface;
use App\Infrastructure\Core\File\Parser\Driver\Interfaces\TextFileParserDriverInterface;
use App\Infrastructure\Core\File\Parser\Driver\Interfaces\WordFileParserDriverInterface;
use App\Infrastructure\Core\File\Parser\Driver\OcrFileParserDriver;
use App\Infrastructure\Core\File\Parser\Driver\PdfFileParserDriver;
use App\Infrastructure\Core\File\Parser\Driver\TextFileParserDriver;
use App\Infrastructure\Core\File\Parser\Driver\WordFileParserDriver;
use App\Infrastructure\Core\HighAvailability\Interface\EndpointProviderInterface;
use App\Infrastructure\Core\HighAvailability\Service\ModelGatewayEndpointProvider;
use App\Infrastructure\Core\TempAuth\RedisTempAuth;
use App\Infrastructure\Core\TempAuth\TempAuthInterface;
use App\Infrastructure\ExternalAPI\Sms\SmsInterface;
use App\Infrastructure\ExternalAPI\Sms\TemplateInterface;
use App\Infrastructure\ExternalAPI\Sms\Volcengine\Template;
use App\Infrastructure\ExternalAPI\Sms\Volcengine\VolceApiClient;
use App\Infrastructure\ImageGenerate\DefaultFontProvider;
use App\Infrastructure\ImageGenerate\DefaultWatermarkConfig;
use App\Infrastructure\ImageGenerate\NullImageEnhancementProcessor;
use App\Infrastructure\Repository\LongTermMemory\MySQLLongTermMemoryRepository;
use App\Infrastructure\Util\Auth\Permission\Permission;
use App\Infrastructure\Util\Auth\Permission\PermissionInterface;
use App\Infrastructure\Util\Client\SimpleClientFactory;
use App\Infrastructure\Util\Locker\LockerInterface;
use App\Infrastructure\Util\Locker\RedisLocker;
use App\Infrastructure\Util\OrganizationEnvironment\Repository\OrganizationRepository;
use App\Infrastructure\Util\Permission\Repository\OrganizationAdminRepository;
use App\Infrastructure\Util\Permission\Repository\RoleRepository;
use App\Interfaces\MCP\Facade\HttpTransportHandler\ApiKeyProviderAuthenticator;
use BeDelightful\PhpMcp\Shared\Auth\AuthenticatorInterface;
use Hyperf\Config\ProviderConfig;
use Hyperf\Crontab\Strategy\CoroutineStrategy;
use Hyperf\Crontab\Strategy\StrategyInterface;
use Hyperf\Di\Definition\PriorityDefinition;
use Hyperf\HttpServer\Server;
use Hyperf\SocketIOServer\BaseNamespace;
use Hyperf\SocketIOServer\NamespaceInterface;
use Hyperf\SocketIOServer\Room\AdapterInterface;
use Hyperf\SocketIOServer\Room\RedisAdapter;
use Hyperf\SocketIOServer\SidProvider\DistributedSidProvider;
use Hyperf\SocketIOServer\SidProvider\SidProviderInterface;
use Psr\Http\Client\ClientInterface;

$dependencies = [
    PointComponentInterface::class => new PriorityDefinition(PointComponent::class, 10),

    SmsInterface::class => VolceApiClient::class,
    LockerInterface::class => RedisLocker::class,
    DelightfulTokenRepositoryInterface::class => DelightfulDelightfulTokenRepository::class,
    TemplateInterface::class => Template::class,

    // core
    ThirdPlatformDataIsolationManagerInterface::class => BaseThirdPlatformDataIsolationManager::class,
    DocumentSplitterInterface::class => OdinRecursiveCharacterTextSplitter::class,
    TempAuthInterface::class => RedisTempAuth::class,
    HandleDataIsolationInterface::class => BaseHandleDataIsolation::class,
    FlowOpenApiCheckInterface::class => BaseFlowOpenApiCheck::class,
    MessageAttachmentHandlerInterface::class => BaseMessageAttachmentHandler::class,

    // delightful-chat
    DelightfulChatConversationRepositoryInterface::class => DelightfulChatConversationRepository::class,
    DelightfulMessageRepositoryInterface::class => DelightfulMessageRepository::class,
    DelightfulChatSeqRepositoryInterface::class => DelightfulChatSeqRepository::class,
    DelightfulChatTopicRepositoryInterface::class => DelightfulChatTopicRepository::class,
    DelightfulContactIdMappingRepositoryInterface::class => DelightfulContactIdMappingRepository::class,
    MessageContentProviderInterface::class => MessageContentProvider::class,
    OrganizationsPlatformRepositoryInterface::class => OrganizationsPlatformRepository::class,
    OpenPlatformConfigInterface::class => OpenPlatformConfigItem::class,
    DelightfulChatMessageVersionsRepositoryInterface::class => DelightfulMessageVersionsRepository::class,
    BeAgentMessageInterface::class => UnknowChatMessage::class,
    // Socket.io pub/sub changed to RabbitMQ implementation, but rooms still use Redis
    AdapterInterface::class => RedisAdapter::class,
    SidProviderInterface::class => DistributedSidProvider::class,
    NamespaceInterface::class => BaseNamespace::class,

    // agent
    AgentRepositoryInterface::class => AgentRepository::class,
    AgentVersionRepositoryInterface::class => AgentVersionRepository::class,

    // delightful-flow
    DelightfulFlowRepositoryInterface::class => DelightfulFlowRepository::class,
    DelightfulFlowDraftRepositoryInterface::class => DelightfulFlowDraftRepository::class,
    DelightfulFlowVersionRepositoryInterface::class => DelightfulFlowVersionRepository::class,
    DelightfulFlowTriggerTestcaseRepositoryInterface::class => DelightfulFlowTriggerTestcaseRepository::class,
    DelightfulFlowMemoryHistoryRepositoryInterface::class => DelightfulFlowMemoryHistoryRepository::class,
    DelightfulFlowExecuteLogRepositoryInterface::class => DelightfulFlowExecuteLogRepository::class,
    DelightfulFlowAIModelRepositoryInterface::class => DelightfulFlowAIModelRepository::class,
    DelightfulFlowPermissionRepositoryInterface::class => DelightfulFlowPermissionRepository::class,
    DelightfulFlowApiKeyRepositoryInterface::class => DelightfulFlowApiKeyRepository::class,
    DelightfulFlowToolSetRepositoryInterface::class => DelightfulFlowToolSetRepository::class,
    DelightfulFlowWaitMessageRepositoryInterface::class => DelightfulFlowWaitMessageRepository::class,
    DelightfulFlowMultiModalLogRepositoryInterface::class => DelightfulFlowMultiModalLogRepository::class,
    DelightfulFlowCacheRepositoryInterface::class => DelightfulFlowCacheRepository::class,
    StringCacheInterface::class => MysqlStringCache::class,

    // knowledge-base
    KnowledgeBaseRepositoryInterface::class => KnowledgeBaseBaseRepository::class,
    KnowledgeBaseDocumentRepositoryInterface::class => KnowledgeBaseDocumentRepository::class,
    KnowledgeBaseFragmentRepositoryInterface::class => KnowledgeBaseFragmentRepository::class,

    // vector
    SemanticSimilaritySearchInterface::class => BaseSemanticSimilaritySearch::class,
    FullTextSimilaritySearchInterface::class => BaseFullTextSimilaritySearch::class,
    HybridSimilaritySearchInterface::class => BaseHybridSimilaritySearch::class,
    GraphSimilaritySearchInterface::class => BaseGraphSimilaritySearch::class,

    // code
    PHPExecutorInterface::class => PHPExecutor::class,
    PythonExecutorInterface::class => PythonExecutor::class,

    // delightful-bot
    DelightfulBotThirdPlatformChatRepositoryInterface::class => DelightfulBotThirdPlatformChatRepository::class,

    // provider
    ProviderRepositoryInterface::class => ProviderRepository::class,
    ProviderConfigRepositoryInterface::class => ProviderConfigRepository::class,
    ProviderModelRepositoryInterface::class => ProviderModelRepository::class,
    ProviderModelConfigVersionRepositoryInterface::class => ProviderModelConfigVersionRepository::class,
    ProviderOriginalModelRepositoryInterface::class => ProviderOriginalModelRepository::class,
    DelightfulProviderAndModelsInterface::class => DelightfulProviderAndModelsRepository::class,
    AiAbilityRepositoryInterface::class => AiAbilityRepository::class,
    // mcp
    MCPServerRepositoryInterface::class => MCPServerRepository::class,
    MCPServerToolRepositoryInterface::class => MCPServerToolRepository::class,
    AuthenticatorInterface::class => ApiKeyProviderAuthenticator::class,
    MCPUserSettingRepositoryInterface::class => MCPUserSettingRepository::class,
    BeDelightfulAgentMCPInterface::class => BeDelightfulAgentMCP::class,
    ExternalStdioExecutorInterface::class => ExternalStdioExecutor::class,
    ExternalHttpExecutorInterface::class => ExternalHttpExecutor::class,

    // api-key
    ApiKeyProviderRepositoryInterface::class => ApiKeyProviderRepository::class,

    // delightful-api
    ApplicationRepositoryInterface::class => ApplicationRepository::class,
    ModelConfigRepositoryInterface::class => ModelConfigRepository::class,
    AccessTokenRepositoryInterface::class => AccessTokenRepository::class,
    OrganizationConfigRepositoryInterface::class => OrganizationConfigRepository::class,
    UserConfigRepositoryInterface::class => UserConfigRepository::class,
    MsgLogRepositoryInterface::class => MsgLogRepository::class,
    SubscriptionManagerInterface::class => BaseSubscriptionManager::class,

    // embeddings
    EmbeddingGeneratorInterface::class => OdinEmbeddingGenerator::class,

    // rerank

    // permission
    OperationPermissionRepositoryInterface::class => OperationPermissionRepository::class,
    RoleRepositoryInterface::class => RoleRepository::class,
    OrganizationAdminRepositoryInterface::class => OrganizationAdminRepository::class,

    // system
    ClientInterface::class => SimpleClientFactory::class,
    StrategyInterface::class => CoroutineStrategy::class,

    // contact
    DelightfulUserRepositoryInterface::class => DelightfulUserRepository::class,
    DelightfulFriendRepositoryInterface::class => DelightfulFriendRepository::class,
    DelightfulAccountRepositoryInterface::class => DelightfulAccountRepository::class,
    DelightfulUserIdRelationRepositoryInterface::class => DelightfulUserIdRelationRepository::class,
    DelightfulDepartmentUserRepositoryInterface::class => DelightfulDepartmentUserRepository::class,
    DelightfulDepartmentRepositoryInterface::class => DelightfulDepartmentRepository::class,
    DelightfulUserSettingRepositoryInterface::class => DelightfulUserSettingRepository::class,
    DelightfulUserDomainExtendInterface::class => DelightfulUserDomainExtendService::class,

    // Authentication system

    EnvironmentRepositoryInterface::class => DelightfulEnvironmentsRepository::class,
    OrganizationsEnvironmentRepositoryInterface::class => OrganizationsEnvironmentRepository::class,

    // Organization management
    OrganizationRepositoryInterface::class => OrganizationRepository::class,

    // Groups
    DelightfulGroupRepositoryInterface::class => DelightfulGroupRepository::class,

    // Chat files
    DelightfulChatFileRepositoryInterface::class => DelightfulChatFileRepository::class,

    AuthenticationRepositoryInterface::class => AuthenticationRepository::class,
    CloudFileRepositoryInterface::class => CloudFileRepository::class,

    // Login verification
    SessionInterface::class => SessionAppService::class,

    // Token extension fields
    DelightfulTokenExtraInterface::class => DelightfulTokenExtra::class,
    // Agent execution events
    AgentExecuteInterface::class => DelightfulAgentEventAppService::class,

    // mock-http-service
    'mock-http-service' => Server::class,

    // File parsing
    OcrFileParserDriverInterface::class => OcrFileParserDriver::class,
    TextFileParserDriverInterface::class => TextFileParserDriver::class,
    ExcelFileParserDriverInterface::class => ExcelFileParserDriver::class,
    WordFileParserDriverInterface::class => WordFileParserDriver::class,
    PdfFileParserDriverInterface::class => PdfFileParserDriver::class,

    // Knowledge base
    KnowledgeBaseStrategyInterface::class => BaseKnowledgeBaseStrategy::class,
    ExternalFileDocumentFileStrategyInterface::class => ExternalFileDocumentFileStrategyDriver::class,
    ThirdPlatformDocumentFileStrategyInterface::class => ThirdPlatformDocumentFileStrategyDriver::class,
    ExternalDocumentFileInterface::class => ExternalDocumentFile::class,
    ThirdPlatformDocumentFileInterface::class => ThirdPlatformDocumentFile::class,

    // admin
    AdminGlobalSettingsRepositoryInterface::class => AdminGlobalSettingsRepository::class,

    // Permission
    PermissionInterface::class => Permission::class,
    DelightfulPermissionInterface::class => DelightfulPermission::class,

    // broadcast
    SubscriberInterface::class => AmqpSubscriber::class,
    PublisherInterface::class => AmqpPublisher::class,

    // high-availability
    EndpointProviderInterface::class => ModelGatewayEndpointProvider::class,

    // long-term-memory
    LongTermMemoryRepositoryInterface::class => MySQLLongTermMemoryRepository::class,

    WatermarkConfigInterface::class => DefaultWatermarkConfig::class,

    // package filter
    PackageFilterInterface::class => DefaultPackageFilter::class,

    // usage calculator
    UsageCalculatorInterface::class => DefaultUsageCalculator::class,

    FontProviderInterface::class => DefaultFontProvider::class,
    ImageEnhancementProcessorInterface::class => NullImageEnhancementProcessor::class,

    // mode
    ModeRepositoryInterface::class => ModeRepository::class,
    ModeGroupRepositoryInterface::class => ModeGroupRepository::class,
    ModeGroupRelationRepositoryInterface::class => ModeGroupRelationRepository::class,

    OrganizationBasedModelFilterInterface::class => DefaultOrganizationModelFilter::class,
];

/**
 * Load and merge dependency priority configurations from all vendor packages.
 *
 * Hyperf's ProviderConfig::load() uses array_merge_recursive to merge configurations.
 * When multiple packages define PriorityDefinition for the same interface, the internal
 * dependencies arrays are merged into nested arrays, requiring manual parsing to select
 * the highest priority class.
 */
$configFromProviders = [];
if (class_exists(ProviderConfig::class)) {
    $configFromProviders = ProviderConfig::load();
}

$dependenciesPriority = $configFromProviders['dependencies_priority'] ?? [];

// Process priority dependency configurations
foreach ($dependenciesPriority as $interface => $definition) {
    // Case 1: Direct PriorityDefinition object (not merged)
    if ($definition instanceof PriorityDefinition) {
        $dependencies[$interface] = $definition->getDefinition();
        continue;
    }

    // Case 2: Plain string binding
    if (! is_array($definition)) {
        $dependencies[$interface] = $definition;
        continue;
    }

    // Case 3: Multiple PriorityDefinition objects merged by array_merge_recursive
    // Structure example: [' * dependencies' => ['ClassA' => 20, 'ClassB' => 99]]
    // Need to find the dependencies array and select the highest priority class
    $priorityMap = null;
    foreach ($definition as $key => $value) {
        if (is_array($value) && str_contains($key, 'dependencies')) {
            $priorityMap = $value;
            break;
        }
    }

    if ($priorityMap) {
        // Select the class with highest priority
        $selectedClass = null;
        $maxPriority = -1;
        foreach ($priorityMap as $className => $priority) {
            if ($priority > $maxPriority) {
                $maxPriority = $priority;
                $selectedClass = $className;
            }
        }
        $dependencies[$interface] = $selectedClass;
    }
}

return $dependencies;
