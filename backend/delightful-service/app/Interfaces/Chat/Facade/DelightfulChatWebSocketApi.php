<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Chat\Facade;

use App\Application\Chat\Event\Publish\MessageDispatchPublisher;
use App\Application\Chat\Event\Publish\MessagePushPublisher;
use App\Application\Chat\Service\DelightfulChatMessageAppService;
use App\Application\Chat\Service\DelightfulControlMessageAppService;
use App\Application\Chat\Service\DelightfulIntermediateMessageAppService;
use App\Domain\Chat\Annotation\VerifyStructure;
use App\Domain\Chat\DTO\Request\ChatRequest;
use App\Domain\Chat\DTO\Request\Common\DelightfulContext;
use App\Domain\Chat\DTO\Request\ControlRequest;
use App\Domain\Chat\Entity\ValueObject\ConversationType;
use App\Domain\Chat\Entity\ValueObject\MessagePriority;
use App\Domain\Chat\Entity\ValueObject\MessageType\ControlMessageType;
use App\Domain\Chat\Entity\ValueObject\SocketEventType;
use App\Domain\Chat\Event\Seq\SeqCreatedEvent;
use App\ErrorCode\ChatErrorCode;
use App\Infrastructure\Core\Exception\BusinessException;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\Auth\Guard\WebsocketChatUserGuard;
use App\Infrastructure\Util\Context\CoContext;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use App\Infrastructure\Util\ShadowCode\ShadowCode;
use App\Infrastructure\Util\SocketIO\SocketIOUtil;
use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use App\Interfaces\Chat\Assembler\MessageAssembler;
use Delightful\ApiResponse\Annotation\ApiResponse;
use Hyperf\Amqp\Producer;
use Hyperf\Codec\Json;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Contract\TranslatorInterface;
use Hyperf\Coordinator\Timer;
use Hyperf\Redis\Redis;
use Hyperf\SocketIOServer\Annotation\Event;
use Hyperf\SocketIOServer\Annotation\SocketIONamespace;
use Hyperf\SocketIOServer\BaseNamespace;
use Hyperf\SocketIOServer\SidProvider\SidProviderInterface;
use Hyperf\SocketIOServer\Socket;
use Hyperf\SocketIOServer\SocketIOConfig;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Hyperf\WebSocketServer\Sender;
use Qbhy\HyperfAuth\Authenticatable;
use Qbhy\HyperfAuth\AuthGuard;
use Qbhy\HyperfAuth\AuthManager;
use Throwable;

use function Hyperf\Coroutine\co;

#[SocketIONamespace('/im')]
#[ApiResponse('low_code')]
class DelightfulChatWebSocketApi extends BaseNamespace
{
    /**
     * @var WebsocketChatUserGuard
     */
    protected AuthGuard $userGuard;

    public function __construct(
        Sender $sender,
        SidProviderInterface $sidProvider,
        private readonly DelightfulChatMessageAppService $delightfulChatMessageAppService,
        private readonly ValidatorFactoryInterface $validatorFactory,
        private readonly StdoutLoggerInterface $logger,
        private readonly SocketIOConfig $config,
        private readonly Redis $redis,
        private readonly Timer $timer,
        private readonly AuthManager $authManager,
        private readonly DelightfulControlMessageAppService $delightfulControlMessageAppService,
        private readonly DelightfulIntermediateMessageAppService $delightfulIntermediateMessageAppService,
        private readonly TranslatorInterface $translator
    ) {
        $this->config->setPingTimeout(2000); // ping timeout
        $this->config->setPingInterval(10 * 1000); // pingbetween隔
        parent::__construct($sender, $sidProvider, $config);
        $this->keepSubscribeAlive();
        /* @phpstan-ignore-next-line */
        $this->userGuard = $this->authManager->guard(name: 'websocket');
    }

    #[Event('connect')]
    #[VerifyStructure]
    public function onConnect(Socket $socket)
    {
        // linko clockrefresh sid cache的permissioninfo，避免极端情况down，use了byfront的 sid permission
        $this->logger->info(sprintf('sid:%s connect', $socket->getSid()));
    }

    #[VerifyStructure]
    #[Event('login')]
    /**
     * @throws Throwable
     */
    public function onLogin(Socket $socket, array $params)
    {
        $rules = [
            'context' => 'required',
            'context.organization_code' => 'string|nullable',
        ];
        $validator = $this->validatorFactory->make($params, $rules);
        if ($validator->fails()) {
            ExceptionBuilder::throw(ChatErrorCode::INPUT_PARAM_ERROR);
        }
        $this->setLocale($params['context']['language'] ?? '');
        try {
            // use delightfulChatContract 校验parameter
            $context = new DelightfulContext($params['context']);
            // compatiblehistoryversion,fromquerymiddlegettoken
            $userToken = $socket->getRequest()->getQueryParams()['authorization'] ?? '';
            $this->delightfulChatMessageAppService->setUserContext($userToken, $context);
            // call guard getuserinfo
            $userAuthorization = $this->getAuthorization();
            // 将账number的所have设备加入同一room
            $this->delightfulChatMessageAppService->joinRoom($userAuthorization, $socket);
            return ['type' => 'user', 'user' => [
                'delightful_id' => $userAuthorization->getDelightfulId(),
                'user_id' => $userAuthorization->getId(),
                'status' => $userAuthorization->getStatus(),
                'nickname' => $userAuthorization->getNickname(),
                'avatar' => $userAuthorization->getAvatar(),
                'organization_code' => $userAuthorization->getOrganizationCode(),
                'sid' => $socket->getSid(),
            ]];
        } catch (BusinessException $exception) {
            $errMsg = [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'trace' => $exception->getTraceAsString(),
            ];
            $this->logger->error('onControlMessage ' . Json::encode($errMsg, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            throw $exception;
        } catch (Throwable $exception) {
            $errMsg = [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'trace' => $exception->getTraceAsString(),
            ];
            ExceptionBuilder::throw(
                ChatErrorCode::LOGIN_FAILED,
                Json::encode($errMsg, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                throwable: $exception
            );
        }
    }

    #[Event('control')]
    #[VerifyStructure]
    /**
     * 控制message.
     * @throws Throwable
     */
    public function onControlMessage(Socket $socket, array $params)
    {
        $appendRules = [
            'data.refer_message_id' => 'string',
            'data.message' => 'required|array',
            'data.message.type' => 'required|string',
            'data.message.app_message_id' => 'string',
        ];
        try {
            $this->relationAppMsgIdAndRequestId($params['data']['message']['app_message_id'] ?? '');
            $this->checkParams($appendRules, $params);
            $this->setLocale($params['context']['language'] ?? '');
            // use delightfulChatContract 校验parameter
            $controlRequest = new ControlRequest($params);
            // compatiblehistoryversion,fromquerymiddlegettoken
            $userToken = $socket->getRequest()->getQueryParams()['authorization'] ?? '';
            $this->delightfulChatMessageAppService->setUserContext($userToken, $controlRequest->getContext());
            // getuserinfo
            $userAuthorization = $this->getAuthorization();
            // according tomessagetype,minutehairto对应的process模piece
            $messageDTO = MessageAssembler::getControlMessageDTOByRequest($controlRequest, $userAuthorization, ConversationType::User);
            return $this->delightfulControlMessageAppService->dispatchClientControlMessage($messageDTO, $userAuthorization);
        } catch (BusinessException $exception) {
            $errMsg = [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'trace' => $exception->getTraceAsString(),
            ];
            $this->logger->error('onControlMessage ' . Json::encode($errMsg, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            throw $exception;
        } catch (Throwable $exception) {
            $errMsg = [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'trace' => $exception->getTraceAsString(),
            ];
            ExceptionBuilder::throw(
                ChatErrorCode::OPERATION_FAILED,
                Json::encode($errMsg, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                throwable: $exception
            );
        }
    }

    #[Event('chat')]
    #[VerifyStructure]
    /**
     * chatmessage.
     * @throws Throwable
     */
    public function onChatMessage(Socket $socket, array $params)
    {
        // 判断messagetype,if是控制message,minutehairto对应的process模piece
        try {
            $appendRules = [
                'data.conversation_id' => 'required|string',
                'data.refer_message_id' => 'string',
                'data.message' => 'required|array',
                'data.message.type' => 'required|string',
                'data.message.app_message_id' => 'required|string',
            ];
            $this->relationAppMsgIdAndRequestId($params['data']['message']['app_message_id'] ?? '');
            $this->checkParams($appendRules, $params);
            $this->setLocale($params['context']['language'] ?? '');
            # use delightfulChatContract 校验parameter
            $chatRequest = new ChatRequest($params);
            // compatiblehistoryversion,fromquerymiddlegettoken
            $userToken = $socket->getRequest()->getQueryParams()['authorization'] ?? '';
            $this->delightfulChatMessageAppService->setUserContext($userToken, $chatRequest->getContext());
            // according tomessagetype,minutehairto对应的process模piece
            $userAuthorization = $this->getAuthorization();
            // 将账number的所have设备加入同一room
            $this->delightfulChatMessageAppService->joinRoom($userAuthorization, $socket);
            return $this->delightfulChatMessageAppService->onChatMessage($chatRequest, $userAuthorization);
        } catch (BusinessException $businessException) {
            throw $businessException;
        } catch (Throwable $exception) {
            $errMsg = [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'trace' => $exception->getTraceAsString(),
            ];
            ExceptionBuilder::throw(
                ChatErrorCode::OPERATION_FAILED,
                Json::encode($errMsg, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                throwable: $exception
            );
        }
    }

    #[Event('intermediate')]
    #[VerifyStructure]
    /**
     * not存入database的实o clockmessage，useat一些temporarymessage场景。
     * @throws Throwable
     */
    public function onIntermediateMessage(Socket $socket, array $params)
    {
        try {
            // 查看whether混淆
            $isConfusion = $params['obfuscated'] ?? false;
            if ($isConfusion) {
                $rawData = ShadowCode::unShadow($params['shadow'] ?? '');
                $params = json_decode($rawData, true);
            }

            $appendRules = [
                'data.conversation_id' => 'required|string',
                'data.refer_message_id' => 'string',
                'data.message' => 'required|array',
                'data.message.type' => 'required|string',
                'data.message.app_message_id' => 'required|string',
            ];
            $this->relationAppMsgIdAndRequestId($params['data']['message']['app_message_id'] ?? '');
            $this->checkParams($appendRules, $params);
            $this->setLocale($params['context']['language'] ?? '');
            # use delightfulChatContract 校验parameter
            $chatRequest = new ChatRequest($params);
            // compatiblehistoryversion,fromquerymiddlegettoken
            $userToken = $socket->getRequest()->getQueryParams()['authorization'] ?? '';
            $this->delightfulChatMessageAppService->setUserContext($userToken, $chatRequest->getContext());
            // according tomessagetype,minutehairto对应的process模piece
            $userAuthorization = $this->getAuthorization();
            // 将账number的所have设备加入同一room
            $this->delightfulChatMessageAppService->joinRoom($userAuthorization, $socket);
            return $this->delightfulIntermediateMessageAppService->dispatchClientIntermediateMessage($chatRequest, $userAuthorization);
        } catch (BusinessException $businessException) {
            throw $businessException;
        } catch (Throwable $exception) {
            $errMsg = [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'trace' => $exception->getTraceAsString(),
            ];
            ExceptionBuilder::throw(
                ChatErrorCode::OPERATION_FAILED,
                Json::encode($errMsg, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                throwable: $exception
            );
        }
    }

    /**
     * @return DelightfulUserAuthorization
     * @throws Throwable
     */
    protected function getAuthorization(): Authenticatable
    {
        return $this->userGuard->user();
    }

    private function checkParams(array $appendRules, array $params): void
    {
        $rules = $this->delightfulControlMessageAppService->getCommonRules();
        $rules = array_merge($rules, $appendRules);
        $validator = $this->validatorFactory->make($params, $rules);
        if ($validator->fails()) {
            foreach ($validator->errors()->getMessages() as $key => $error) {
                ExceptionBuilder::throw(ChatErrorCode::INPUT_PARAM_ERROR, 'chat.common.param_error', ['param' => $key]);
            }
        }
    }

    private function relationAppMsgIdAndRequestId(?string $appMsgId): void
    {
        // 直接use appMsgId 作为 requestIdwill导致very多invalid log，难bytrace。
        $requestId = empty($appMsgId) ? (string) IdGenerator::getSnowId() : $appMsgId;
        CoContext::setRequestId($requestId);
        $this->logger->info('relationAppMsgIdAndRequestId requestId:' . $requestId . ' appMsgId: ' . $appMsgId);
    }

    /**
     * publishsubscribe/多messageminutehair和push的queue保活.
     */
    private function keepSubscribeAlive(): void
    {
        // 只need一进程能schedulepublishmessage,让subscribe的redislink保活即可.
        // not把lock放inmostoutsidelayer,是为了防止pod频繁重启o clock,nothave任何一进程canpublishmessage
        co(function () {
            // each 5 second推一timemessage
            $this->timer->tick(
                5,
                function () {
                    if (! $this->redis->set('delightful-im:subscribe:keepalive', '1', ['ex' => 5, 'nx'])) {
                        return;
                    }
                    SocketIOUtil::sendIntermediate(SocketEventType::Chat, 'delightful-im:subscribe:keepalive', ControlMessageType::Ping->value);

                    $producer = ApplicationContext::getContainer()->get(Producer::class);
                    // 对所havequeue投一itemmessage,by保活link/queue
                    $messagePriorities = MessagePriority::cases();
                    foreach ($messagePriorities as $priority) {
                        $seqCreatedEvent = new SeqCreatedEvent([ControlMessageType::Ping->value]);
                        $seqCreatedEvent->setPriority($priority);
                        // messageminutehair. 一itemseq可能willgenerate多itemseq
                        $messageDispatch = new MessageDispatchPublisher($seqCreatedEvent);
                        // messagepush. 一itemseq只willpush给一user(的多设备)
                        $messagePush = new MessagePushPublisher($seqCreatedEvent);
                        $producer->produce($messageDispatch);
                        $producer->produce($messagePush);
                    }
                },
                'delightful-im:subscribe:keepalive'
            );
        });
    }

    // set语言
    private function setLocale(?string $language): void
    {
        if (! empty($language)) {
            CoContext::setLanguage($language);
            $this->translator->setLocale($language);
        }
    }
}
