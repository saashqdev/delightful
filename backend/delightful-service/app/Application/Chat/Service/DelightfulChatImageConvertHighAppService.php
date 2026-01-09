<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Chat\Service;

use App\Application\ModelGateway\Service\LLMAppService;
use App\Domain\Chat\DTO\ImageConvertHigh\Request\DelightfulChatImageConvertHighReqDTO;
use App\Domain\Chat\DTO\Message\ChatMessage\AIImageCardMessage;
use App\Domain\Chat\DTO\Message\ChatMessage\ImageConvertHighCardMessage;
use App\Domain\Chat\Entity\Items\SeqExtra;
use App\Domain\Chat\Entity\DelightfulChatFileEntity;
use App\Domain\Chat\Entity\DelightfulSeqEntity;
use App\Domain\Chat\Entity\ValueObject\AIImage\Radio;
use App\Domain\Chat\Entity\ValueObject\FileType;
use App\Domain\Chat\Entity\ValueObject\ImageConvertHigh\ImageConvertHighResponseType;
use App\Domain\Chat\Service\DelightfulAIImageDomainService;
use App\Domain\Chat\Service\DelightfulChatDomainService;
use App\Domain\Chat\Service\DelightfulChatFileDomainService;
use App\Domain\Chat\Service\DelightfulConversationDomainService;
use App\Domain\Contact\Service\DelightfulUserDomainService;
use App\Domain\File\Service\FileDomainService;
use App\Domain\Provider\Service\AdminProviderDomainService;
use App\ErrorCode\ImageGenerateErrorCode;
use App\Infrastructure\Core\Exception\Annotation\ErrorMessage;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\Context\RequestContext;
use App\Infrastructure\Util\SSRF\Exception\SSRFException;
use App\Infrastructure\Util\SSRF\SSRFUtil;
use Delightful\CloudFile\Kernel\Struct\UploadFile;
use Hyperf\Codec\Json;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Redis\Redis;
use Hyperf\Snowflake\IdGeneratorInterface;
use JetBrains\PhpStorm\ArrayShape;
use Psr\Log\LoggerInterface;
use ReflectionEnum;
use Throwable;

use function di;
use function Hyperf\Translation\__;
use function mb_strlen;

/**
 * AI文生graph.
 */
class DelightfulChatImageConvertHighAppService extends AbstractAIImageAppService
{
    protected LoggerInterface $logger;

    public function __construct(
        protected readonly DelightfulConversationDomainService $delightfulConversationDomainService,
        protected readonly DelightfulUserDomainService $delightfulUserDomainService,
        protected readonly DelightfulChatDomainService $delightfulChatDomainService,
        protected readonly DelightfulAIImageDomainService $delightfulAIImageDomainService,
        protected readonly FileDomainService $fileDomainService,
        protected readonly DelightfulChatFileDomainService $delightfulChatFileDomainService,
        protected readonly AdminProviderDomainService $serviceProviderDomainService,
        protected readonly LLMAppService $llmAppService,
        protected readonly Redis $redis,
        protected IdGeneratorInterface $idGenerator,
    ) {
        $this->logger = di()->get(LoggerFactory::class)->get(get_class($this));
    }

    /**
     * @throws SSRFException
     */
    public function handleUserMessage(RequestContext $requestContext, DelightfulChatImageConvertHighReqDTO $reqDTO): void
    {
        $referContent = $this->getReferContentForAIImage($reqDTO->getReferMessageId());
        if ($referContent instanceof AIImageCardMessage || $referContent instanceof ImageConvertHighCardMessage) {
            $reqDTO->setRadio($referContent->getRadio() ?? Radio::OneToOne->value);
        }
        $referText = $this->getReferTextByContentForAIImage($referContent);
        $reqDTO->setReferText($referText);
        $dataIsolation = $this->createDataIsolation($requestContext->getUserAuthorization());
        $requestContext->setDataIsolation($dataIsolation);
        $reqDTO->setAppMessageId((string) $this->idGenerator->generate());

        $url = SSRFUtil::getSafeUrl($reqDTO->getOriginImageUrl(), replaceIp: false);
        $reqDTO->setOriginImageUrl($url);
        $authorization = $requestContext->getUserAuthorization();
        $dataIsolation = $this->createDataIsolation($authorization);
        $requestContext->setDataIsolation($dataIsolation);
        try {
            $taskId = $this->llmAppService->imageConvertHigh($authorization, $reqDTO);
            $this->aiSendMessage(
                $reqDTO->getConversationId(),
                (string) $this->idGenerator->generate(),
                ImageConvertHighResponseType::START_GENERATE,
                [
                    'origin_file_id' => $reqDTO->getOriginImageId() ?? null,
                    'radio' => $reqDTO->getRadio(),
                ],
                $reqDTO->getAppMessageId(),
                $reqDTO->getTopicId(),
                $reqDTO->getReferMessageId(),
            );
            // 计o clockstart
            $start = microtime(true);
            // round询600time，until拿toimage
            $count = 600;
            $response = null;

            while ($count-- > 0) {
                $response = $this->llmAppService->imageConvertHighQuery($authorization, $taskId);
                if ($response->isFinishStatus() === true) {
                    break;
                }
                sleep(2);
            }
            // if未complete，then报错timeout
            if (! $response?->isFinishStatus() || empty($response?->getUrls())) {
                ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR, 'image_generate.task_timeout');
            }
            // 计o clockend，outputsecondleveltime
            $end = microtime(true);
            $this->logger->info(sprintf('转高清end，耗o clock: %ssecond。', $end - $start));
            // will新旧image存入attachment
            $newFile = $this->upLoadFiles($requestContext, [$response->getUrls()[0]])[0] ?? [];
            $this->aiSendMessage(
                $reqDTO->getConversationId(),
                (string) $this->idGenerator->generate(),
                ImageConvertHighResponseType::GENERATED,
                [
                    'origin_file_id' => $reqDTO->getOriginImageId() ?? null,
                    'new_file_id' => $newFile['file_id'] ?? null,
                    'refer_text' => $reqDTO->getReferText(),
                    'radio' => $reqDTO->getRadio(),
                ],
                $reqDTO->getAppMessageId(),
                $reqDTO->getTopicId(),
                $reqDTO->getReferMessageId(),
            );
        } catch (Throwable $e) {
            // hair生exceptiono clock，sendterminationmessage，andthrowexception
            $this->handleGlobalThrowable($reqDTO, $e);
        }
    }

    /**
     * willfileuploadto云端.
     */
    #[ArrayShape([['file_id' => 'string', 'url' => 'string']])]
    private function upLoadFiles(RequestContext $requestContext, array $attachments): array
    {
        $images = [];
        foreach ($attachments as $attachment) {
            if (! is_string($attachment)) {
                continue;
            }
            try {
                // uploadOSS
                $uploadFile = new UploadFile($attachment);
                $this->fileDomainService->uploadByCredential($requestContext->getUserAuthorization()->getOrganizationCode(), $uploadFile);
                // geturl
                $url = $this->fileDomainService->getLink($requestContext->getUserAuthorization()->getOrganizationCode(), $uploadFile->getKey())->getUrl();
                // syncfiletodelightful
                $fileUploadDTOs = [];
                $fileType = FileType::getTypeFromFileExtension($uploadFile->getExt());
                $fileUploadDTO = new DelightfulChatFileEntity();
                $fileUploadDTO->setFileKey($uploadFile->getKey());
                $fileUploadDTO->setFileSize($uploadFile->getSize());
                $fileUploadDTO->setFileExtension($uploadFile->getExt());
                $fileUploadDTO->setFileName($uploadFile->getName());
                $fileUploadDTO->setFileType($fileType);
                $fileUploadDTOs[] = $fileUploadDTO;
                $delightfulChatFileEntity = $this->delightfulChatFileDomainService->fileUpload($fileUploadDTOs, $requestContext->getDataIsolation())[0] ?? null;
                $images[] = [
                    'file_id' => $delightfulChatFileEntity->getFileId(),
                    'url' => $url,
                ];
            } catch (Throwable $throwable) {
                // submitimagefail
                $this->logger->error('upload_attachment_error', [
                    'error' => $throwable->getMessage(),
                    'file' => $attachment,
                ]);
            }
        }
        return $images;
    }

    private function handleGlobalThrowable(DelightfulChatImageConvertHighReqDTO $reqDTO, Throwable $e)
    {
        $errorCode = $e->getCode();
        $errorMessage = __('chat.agent.user_call_agent_fail_notice');
        $errorCode = ImageGenerateErrorCode::tryFrom($errorCode);
        if ($errorCode instanceof ImageGenerateErrorCode) {
            $errorMessage = $this->getErrorMessageFromImageGenerateErrorCode($errorCode) . $e->getMessage();
        }
        $this->aiSendMessage(
            $reqDTO->getConversationId(),
            (string) $this->idGenerator->generate(),
            ImageConvertHighResponseType::TERMINATE,
            [
                'error_message' => $errorMessage,
                'origin_file_id' => $reqDTO->getOriginImageId() ?? null,
            ],
            $reqDTO->getAppMessageId(),
            $reqDTO->getTopicId(),
            $reqDTO->getReferMessageId(),
        );
        $errMsg = [
            'function' => 'imageConvertHighError',
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'message' => $errorMessage,
            'trace' => $e->getTraceAsString(),
        ];
        $this->logger->error('imageConvertHighError ' . Json::encode($errMsg, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        //        throw $e;
    }

    private function getErrorMessageFromImageGenerateErrorCode(ImageGenerateErrorCode $case): ?string
    {
        // get枚举constantreflectionobject
        $reflectionEnum = new ReflectionEnum($case);
        $reflectionCase = $reflectionEnum->getCase($case->name);

        // getconstant所haveannotation
        $attributes = $reflectionCase->getAttributes(ErrorMessage::class);

        // checkwhether存in ErrorMessage annotation
        if (! empty($attributes)) {
            // instance化annotationobject
            $errorMessageAttribute = $attributes[0]->newInstance();

            // returnannotationmiddle message property
            return '[' . __($errorMessageAttribute->getMessage()) . ']';
        }

        return null;
    }

    private function aiSendMessage(
        string $conversationId,
        ?string $id,
        ImageConvertHighResponseType $type,
        array $content,
        // streamresponse，拿tocustomer端传come app_message_id ，asforresponsetime唯one标识
        string $appMessageId = '',
        string $topicId = '',
        string $referMessageId = '',
    ): array {
        $logMessageContent = Json::encode($content, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (mb_strlen($logMessageContent) > 300) {
            $logMessageContent = '';
        }
        $this->logger->info(sprintf(
            'imageConvertHighSendMessage conversationId:%s id:%s messageName:%s Type:%s appMessageId:%s topicId:%s logMessageContent:%s',
            $conversationId,
            $id,
            ImageConvertHighResponseType::getNameFromType($type),
            $type->value,
            $appMessageId,
            $topicId,
            $logMessageContent
        ));
        $content = $content + [
            'id' => $id ?? (string) $this->idGenerator->generate(),
            'type' => $type,
        ];
        $messageInterface = new ImageConvertHighCardMessage($content);
        $extra = new SeqExtra();
        $extra->setTopicId($topicId);
        $seqDTO = (new DelightfulSeqEntity())
            ->setConversationId($conversationId)
            ->setContent($messageInterface)
            ->setSeqType($messageInterface->getMessageTypeEnum())
            ->setAppMessageId($appMessageId)
            ->setReferMessageId($referMessageId)
            ->setExtra($extra);
        // set话题 id
        return $this->getDelightfulChatMessageAppService()->aiSendMessage($seqDTO, $appMessageId, doNotParseReferMessageId: true);
    }

    private function getDelightfulChatMessageAppService(): DelightfulChatMessageAppService
    {
        return di(DelightfulChatMessageAppService::class);
    }
}
