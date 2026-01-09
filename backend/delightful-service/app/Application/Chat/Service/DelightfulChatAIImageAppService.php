<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Chat\Service;

use App\Application\Flow\ExecuteManager\Attachment\AbstractAttachment;
use App\Application\ModelGateway\Service\LLMAppService;
use App\Domain\Chat\DTO\AIImage\Request\DelightfulChatAIImageReqDTO;
use App\Domain\Chat\DTO\Message\ChatMessage\AIImageCardMessage;
use App\Domain\Chat\DTO\Message\ChatMessage\ImageConvertHighCardMessage;
use App\Domain\Chat\Entity\Items\SeqExtra;
use App\Domain\Chat\Entity\DelightfulChatFileEntity;
use App\Domain\Chat\Entity\DelightfulSeqEntity;
use App\Domain\Chat\Entity\ValueObject\AIImage\AIImageCardResponseType;
use App\Domain\Chat\Entity\ValueObject\AIImage\AIImageGenerateParamsVO;
use App\Domain\Chat\Entity\ValueObject\AIImage\Radio;
use App\Domain\Chat\Entity\ValueObject\FileType;
use App\Domain\Chat\Service\DelightfulAIImageDomainService;
use App\Domain\Chat\Service\DelightfulChatDomainService;
use App\Domain\Chat\Service\DelightfulChatFileDomainService;
use App\Domain\Chat\Service\DelightfulConversationDomainService;
use App\Domain\Contact\Service\DelightfulUserDomainService;
use App\Domain\File\Service\FileDomainService;
use App\Domain\ModelGateway\Service\MsgLogDomainService;
use App\Domain\Provider\Service\AdminProviderDomainService;
use App\ErrorCode\ImageGenerateErrorCode;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\ImageGenerateModelType;
use App\Infrastructure\Util\Context\RequestContext;
use Delightful\CloudFile\Kernel\Struct\UploadFile;
use Hyperf\Codec\Json;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Redis\Redis;
use Hyperf\Snowflake\IdGeneratorInterface;
use JetBrains\PhpStorm\ArrayShape;
use Psr\Log\LoggerInterface;
use Throwable;

use function di;
use function Hyperf\Translation\__;
use function mb_strlen;

/**
 * AI文生graph.
 */
class DelightfulChatAIImageAppService extends AbstractAIImageAppService
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
        protected readonly MsgLogDomainService $msgLogDomainService,
        protected readonly Redis $redis,
        protected IdGeneratorInterface $idGenerator,
    ) {
        $this->logger = di()->get(LoggerFactory::class)->get(get_class($this));
    }

    public function handleUserMessage(RequestContext $requestContext, DelightfulChatAIImageReqDTO $reqDTO): void
    {
        $referContent = $this->getReferContentForAIImage($reqDTO->getReferMessageId());
        $referText = $this->getReferTextByContentForAIImage($referContent);
        // ifisgraph生graph，thensizemaintainandoriginalimagesizeone致
        if ($referContent instanceof AIImageCardMessage || $referContent instanceof ImageConvertHighCardMessage) {
            // setactualrequestsizeandratio例
            $radio = $referContent->getRadio() ?? Radio::OneToOne->value;
            $enumModel = ImageGenerateModelType::fromModel($reqDTO->getParams()->getModel(), false);
            $reqDTO->getParams()->setRatioForModel($radio, $enumModel);
            $radio = $reqDTO->getParams()->getRatio();
            $reqDTO->getParams()->setSizeFromRadioAndModel($radio, $enumModel);
        }
        $reqDTO->setReferText($referText);
        $dataIsolation = $this->createDataIsolation($requestContext->getUserAuthorization());
        $requestContext->setDataIsolation($dataIsolation);
        $reqDTO->setAppMessageId((string) $this->idGenerator->generate());
        try {
            /** @var null|AbstractAttachment $attachment */
            $attachment = $reqDTO->getAttachments()[0] ?? null;
            $this->aiSendMessage(
                $reqDTO->getConversationId(),
                null,
                AIImageCardResponseType::START_GENERATE,
                [
                    'refer_file_id' => ! empty($reqDTO->getAttachments()) ? $attachment?->getUrl() : null,
                    'radio' => $reqDTO->getParams()->getRatio(),
                ],
                $reqDTO->getAppMessageId(),
                $reqDTO->getTopicId(),
                $reqDTO->getReferMessageId(),
            );
            if (! empty($reqDTO->getAttachments())) {
                // toquotecontent重new文生graph
                $this->handleGenerateImageByReference($requestContext, $reqDTO);
            } else {
                // 文生graph
                $this->handleGenerateImage($requestContext, $reqDTO);
            }
        } catch (Throwable $e) {
            // hair生exceptiono clock，sendterminationmessage，andthrowexception
            $this->handleGlobalThrowable($reqDTO, $e);
        }
    }

    /**
     * toquotecontent重new文生graph.
     */
    private function handleGenerateImageByReference(RequestContext $requestContext, DelightfulChatAIImageReqDTO $reqDTO): void
    {
        $reqDTO->getParams()->setGenerateNum(1);
        // clearemptyvalue
        $urls = array_filter(array_map(fn ($attachment) => $attachment->getUrl(), $reqDTO->getAttachments()));
        $reqDTO->getParams()->setReferenceImages($urls);
        $this->handleGenerateImage($requestContext, $reqDTO);
    }

    /**
     * 文生graph.
     */
    private function handleGenerateImage(RequestContext $requestContext, DelightfulChatAIImageReqDTO $reqDTO): void
    {
        $res = $this->generateImage($requestContext, $reqDTO->getParams());
        $this->aiSendMessage(
            $reqDTO->getConversationId(),
            (string) $this->idGenerator->generate(),
            AIImageCardResponseType::GENERATED,
            [
                'items' => $res['images'],
                'radio' => $reqDTO->getParams()->getRatio(),
                'refer_text' => $reqDTO->getReferText(),
            ],
            $reqDTO->getAppMessageId(),
            $reqDTO->getTopicId(),
            $reqDTO->getReferMessageId(),
        );
    }

    #[ArrayShape(
        [
            'images' => [
                [
                    'file_id' => 'string',
                    'url' => 'string',
                ],
            ],
        ]
    )]
    private function generateImage(RequestContext $requestContext, AIImageGenerateParamsVO $generateParamsVO): array
    {
        $model = $generateParamsVO->getModel();
        // according tomodeltypecreateto应service
        $data = $generateParamsVO->toArray();
        $delightfulUserAuthorization = $requestContext->getUserAuthorization();
        $images = $this->llmAppService->imageGenerate($delightfulUserAuthorization, $model, '', $data);
        $this->logger->info('images', $images);
        $images = $this->uploadFiles($requestContext, $images);
        return [
            'images' => $images,
        ];
    }

    /**
     * willfileuploadto云端.
     */
    #[ArrayShape([['file_id' => 'string', 'url' => 'string']])]
    private function uploadFiles(RequestContext $requestContext, array $attachments): array
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

    private function handleGlobalThrowable(DelightfulChatAIImageReqDTO $reqDTO, Throwable $e)
    {
        $errorCode = $e->getCode();
        $errorMessage = __('chat.agent.user_call_agent_fail_notice');
        $errorCode = ImageGenerateErrorCode::tryFrom($errorCode);
        if ($errorCode instanceof ImageGenerateErrorCode) {
            $errorMessage = $e->getMessage();
        }
        $this->aiSendMessage(
            $reqDTO->getConversationId(),
            (string) $this->idGenerator->generate(),
            AIImageCardResponseType::TERMINATE,
            ['error_message' => $errorMessage],
            $reqDTO->getAppMessageId(),
            $reqDTO->getTopicId(),
            $reqDTO->getReferMessageId(),
        );
        $errMsg = [
            'function' => 'aiImageCardError',
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'message' => $errorMessage,
            'trace' => $e->getTraceAsString(),
        ];
        $this->logger->error('aiImageCardError ' . Json::encode($errMsg, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        //        throw $e;
    }

    private function aiSendMessage(
        string $conversationId,
        ?string $id,
        AIImageCardResponseType $type,
        array $content,
        // streamresponse，拿tocustomer端传come app_message_id ，asforresponsetime唯oneidentifier
        string $appMessageId = '',
        string $topicId = '',
        string $referMessageId = '',
    ): array {
        $logMessageContent = Json::encode($content, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (mb_strlen($logMessageContent) > 300) {
            $logMessageContent = '';
        }
        $this->logger->info(sprintf(
            'aiImageSendMessage conversationId:%s id:%s messageName:%s Type:%s appMessageId:%s topicId:%s logMessageContent:%s',
            $conversationId,
            $id,
            AIImageCardResponseType::getNameFromType($type),
            $type->value,
            $appMessageId,
            $topicId,
            $logMessageContent
        ));
        $content = $content + [
            'id' => $id ?? (string) $this->idGenerator->generate(),
            'type' => $type,
        ];
        $messageInterface = new AIImageCardMessage($content);
        $extra = new SeqExtra();
        $extra->setTopicId($topicId);
        $seqDTO = (new DelightfulSeqEntity())
            ->setConversationId($conversationId)
            ->setContent($messageInterface)
            ->setSeqType($messageInterface->getMessageTypeEnum())
            ->setAppMessageId($appMessageId)
            ->setExtra($extra)
            ->setReferMessageId($referMessageId);
        // set话题 id
        return $this->getDelightfulChatMessageAppService()->aiSendMessage($seqDTO, $appMessageId, doNotParseReferMessageId: true);
    }

    private function getDelightfulChatMessageAppService(): DelightfulChatMessageAppService
    {
        return di(DelightfulChatMessageAppService::class);
    }
}
