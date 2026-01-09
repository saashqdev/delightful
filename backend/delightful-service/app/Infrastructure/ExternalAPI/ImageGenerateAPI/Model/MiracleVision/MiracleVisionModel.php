<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\ImageGenerateAPI\Model\MiracleVision;

use App\ErrorCode\ImageGenerateErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\AbstractImageGenerate;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\ImageGenerate;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\ImageGenerateModelType;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Request\ImageGenerateRequest;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Request\MiracleVisionModelRequest;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Response\ImageGenerateResponse;
use App\Infrastructure\Util\FileType;
use BadMethodCallException;
use Exception;
use Hyperf\RateLimit\Annotation\RateLimit;

class MiracleVisionModel extends AbstractImageGenerate
{
    private const STATUS_INIT = 0;

    private const STATUS_PROCESSING = 1;

    private const STATUS_FAILED = 2;

    private const STATUS_SUCCESS = 10;

    private const STATUS_NOT_FOUND = -1;

    // commentdropisitemfrontusenotto
    //    private const STYLE_PORTRAIT = 25;
    private const STYLE_GENERAL = 26;
    //    private const STYLE_LANDSCAPE = 28;
    //    private const STYLE_3D = 27;

    private const ALLOWED_IMAGE_TYPES = ['JPG', 'JPEG', 'BMP', 'IMAGE', 'PNG'];

    private MiracleVisionAPI $api;

    public function __construct(array $serviceProviderConfig)
    {
        $this->api = new MiracleVisionAPI($serviceProviderConfig['ak'], $serviceProviderConfig['sk']);
    }

    public function generateImageRaw(ImageGenerateRequest $imageGenerateRequest): array
    {
        throw new BadMethodCallException('themethodtemporarynot supported');
    }

    public function imageConvertHigh(ImageGenerateRequest $imageGenerateRequest): string
    {
        $this->logger->info('美graphultra clearconvert:startprocessconvertrequest', [
            'request_type' => get_class($imageGenerateRequest),
        ]);

        $this->validateRequest($imageGenerateRequest);

        try {
            /**
             * @var MiracleVisionModelRequest $imageGenerateRequest
             */
            $styles = $this->api->getStyle();
            $this->validateApiResponse($styles);

            $styleId = $this->determineStyleId($styles);
            $this->logger->info('美graphultra clearconvert:alreadychooseconvert样type', ['style_id' => $styleId]);

            $result = $this->api->submitTask($imageGenerateRequest->getUrl(), $styleId);
            $this->validateApiResponse($result);

            $taskId = $result['data']['result']['id'];
            $this->logger->info('美graphultra clearconvert:tasksubmitsuccess', [
                'task_id' => $taskId,
            ]);

            return $taskId;
        } catch (Exception $e) {
            $this->logger->error('美graphultra clearconvert:tasksubmitexception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR);
        }
    }

    #[RateLimit(create: 5, consume: 1, capacity: 0, key: ImageGenerate::IMAGE_GENERATE_KEY_PREFIX . ImageGenerate::IMAGE_GENERATE_POLL_KEY_PREFIX . ImageGenerateModelType::MiracleVision->value, waitTimeout: 60)]
    public function queryTask(string $taskId): MiracleVisionModelResponse
    {
        $this->logger->info('美graphultra clearconvert:startquerytaskstatus', ['task_id' => $taskId]);

        if (empty($taskId)) {
            $this->logger->error('美graphultra clearconvert:missingtaskIDparameter');
            ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR, 'image_generate.missing_job_id');
        }

        try {
            $result = $this->api->queryTask($taskId);
            $this->validateApiResponse($result);

            $response = new MiracleVisionModelResponse();
            $status = (int) ($result['data']['status'] ?? self::STATUS_FAILED);

            $this->logger->info('美graphultra clearconvert:gettaskstatus', [
                'task_id' => $taskId,
                'status' => $status,
                'progress' => $result['data']['progress'] ?? 0,
            ]);

            return $this->handleTaskStatus($status, $result, $response);
        } catch (Exception $e) {
            $this->logger->error('美graphultra clearconvert:querytaskstatusexception', [
                'task_id' => $taskId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function getStyle(): array
    {
        try {
            $result = $this->api->getStyle();
            $this->validateApiResponse($result);
            return $result;
        } catch (Exception $e) {
            $this->logger->error('美graphultra clearconvert:get样typelistexception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR);
        }
    }

    public function setAK(string $ak)
    {
        $this->api->setKey($ak);
    }

    public function setSK(string $sk)
    {
        $this->api->setSecret($sk);
    }

    public function setApiKey(string $apiKey)
    {
    }

    public function generateImageRawWithWatermark(ImageGenerateRequest $imageGenerateRequest): array
    {
        throw new BadMethodCallException('themethodtemporarynot supported');
    }

    public function getProviderName(): string
    {
        return 'miracle';
    }

    protected function generateImageInternal(ImageGenerateRequest $imageGenerateRequest): ImageGenerateResponse
    {
        throw new BadMethodCallException('themethodtemporarynot supported');
    }

    private function handleTaskStatus(int $status, array $result, MiracleVisionModelResponse $response): MiracleVisionModelResponse
    {
        $this->logger->info('美graphultra clearconvert:processtaskstatusinfo', ['status' => $status]);

        switch ($status) {
            case self::STATUS_SUCCESS:
                if (empty($result['data']['result']['urls'])) {
                    $this->logger->error('美graphultra clearconvert:taskcompletebutmissingresultURL', ['response' => $result]);
                    ExceptionBuilder::throw(ImageGenerateErrorCode::MISSING_IMAGE_DATA);
                }
                $response->setFinishStatus(true);
                $response->setUrls($result['data']['result']['urls']);
                $this->logger->info('美graphultra clearconvert:taskprocesssuccess', [
                    'urls_count' => count($result['data']['result']['urls']),
                ]);
                break;
            case self::STATUS_PROCESSING:
                $response->setFinishStatus(false);
                $response->setProgress($result['data']['progress']);
                $this->logger->info('美graphultra clearconvert:taskprocessconductmiddle', [
                    'progress' => $result['data']['progress'],
                ]);
                // no break
            case self::STATUS_INIT:
                $response->setFinishStatus(false);
                $response->setProgress($result['data']['progress']);
                $this->logger->info('美graphultra clearconvert:taskjustininitialize', [
                    'progress' => $result['data']['progress'],
                ]);
                break;
            case self::STATUS_FAILED:
            case self::STATUS_NOT_FOUND:
            default:
                $response->setFinishStatus(false);
                $response->setError($result['message'] ?? 'unknownerror');
                $this->logger->error(
                    $status === self::STATUS_NOT_FOUND ? '美graphultra clearconvert:tasknotexistsin' : '美graphultra clearconvert:taskprocessfail',
                    ['status' => $status, 'response' => $result]
                );
        }

        return $response;
    }

    private function validateRequest(ImageGenerateRequest $request): void
    {
        if (! $request instanceof MiracleVisionModelRequest) {
            $this->logger->error('美graphultra clearconvert:requesttypenotmatch', ['class' => get_class($request)]);
            ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR);
        }

        $this->validateImageType($request->getUrl());
    }

    private function validateImageType(string $url): void
    {
        $this->logger->info('美graphultra clearconvert:startverifyimagetype', ['url' => $url]);

        $type = FileType::getType($url);
        if (empty($type)) {
            $this->logger->error('美graphultra clearconvert:nomethodidentifyimagetype', ['url' => $url]);
            ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR);
        }

        if (! in_array(strtoupper($type), self::ALLOWED_IMAGE_TYPES, true)) {
            $this->logger->error('美graphultra clearconvert:imagetypenot supported', [
                'url' => $url,
                'type' => $type,
                'allowed_types' => self::ALLOWED_IMAGE_TYPES,
            ]);
            ExceptionBuilder::throw(ImageGenerateErrorCode::UNSUPPORTED_IMAGE_FORMAT);
        }

        $this->logger->info('美graphultra clearconvert:imagetypeverifypass', ['type' => $type]);
    }

    private function validateApiResponse(array $result): void
    {
        $this->logger->info('美graphAPI:startverifyresponsedata', ['response' => $result]);

        if (! isset($result['code'])) {
            $this->logger->warning('美graphAPI:responseformatexception', ['response' => $result]);
            ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR);
        }

        if ($result['code'] !== 0) {
            $this->logger->warning('美graphAPI:interfacereturnerror', [
                'code' => $result['code'],
                'message' => $result['message'] ?? '',
                'response' => $result,
            ]);
            ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR, $result['message'] ?? '');
        }

        $this->logger->info('美graphAPI:responsedataverifypass');
    }

    // todo xhy itemfrontonlycanforcereturn 26 ,factorfornomethodtoimagescenariomakematch
    private function determineStyleId(array $styles): int
    {
        if (empty($styles['data']['style_list'])) {
            return self::STYLE_GENERAL;
        }

        return $styles['data']['style_list'][1]['id'];
    }
}
