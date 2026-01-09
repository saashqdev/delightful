<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\ImageGenerateAPI\Model\Qwen;

use App\ErrorCode\ImageGenerateErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\AbstractImageGenerate;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\ImageGenerateModelType;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\ImageGenerateType;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Request\ImageGenerateRequest;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Request\QwenImageModelRequest;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Response\ImageGenerateResponse;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Response\ImageUsage;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Response\OpenAIFormatResponse;
use App\Infrastructure\Util\Context\CoContext;
use Exception;
use Hyperf\Coroutine\Parallel;
use Hyperf\Engine\Coroutine;
use Hyperf\RateLimit\Annotation\RateLimit;
use Hyperf\Retry\Annotation\Retry;

class QwenImageModel extends AbstractImageGenerate
{
    // mostbigroundretrycount
    private const MAX_RETRY_COUNT = 30;

    // roundretrybetween(second)
    private const RETRY_INTERVAL = 2;

    private QwenImageAPI $api;

    public function __construct(array $serviceProviderConfig)
    {
        $apiKey = $serviceProviderConfig['api_key'];
        if (empty($apiKey)) {
            ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR, 'image_generate.api_call_failed');
        }

        $this->api = new QwenImageAPI($apiKey);
    }

    public function generateImageRaw(ImageGenerateRequest $imageGenerateRequest): array
    {
        return $this->generateImageRawInternal($imageGenerateRequest);
    }

    public function setAK(string $ak)
    {
        // thousandnotuseAK/SKauthentication,thismethodfornullimplement
    }

    public function setSK(string $sk)
    {
        // thousandnotuseAK/SKauthentication,thismethodfornullimplement
    }

    public function setApiKey(string $apiKey)
    {
        $this->api->setApiKey($apiKey);
    }

    public function generateImageRawWithWatermark(ImageGenerateRequest $imageGenerateRequest): array
    {
        $rawData = $this->generateImageRaw($imageGenerateRequest);

        return $this->processQwenRawDataWithWatermark($rawData, $imageGenerateRequest);
    }

    /**
     * generategraphlikeandreturnOpenAIformatresponse - Qwenversion.
     */
    public function generateImageOpenAIFormat(ImageGenerateRequest $imageGenerateRequest): OpenAIFormatResponse
    {
        // 1. createresponseobject
        $response = new OpenAIFormatResponse([
            'created' => time(),
            'provider' => $this->getProviderName(),
            'data' => [],
        ]);

        // 2. parametervalidate
        if (! $imageGenerateRequest instanceof QwenImageModelRequest) {
            $this->logger->error('Qwen OpenAIformatgraph:invalidrequesttype', ['class' => get_class($imageGenerateRequest)]);
            return $response; // returnnulldataresponse
        }

        // 3. andhairhandle - asresponseobject
        $count = $imageGenerateRequest->getGenerateNum();
        $parallel = new Parallel();
        $fromCoroutineId = Coroutine::id();

        for ($i = 0; $i < $count; ++$i) {
            $parallel->add(function () use ($imageGenerateRequest, $response, $fromCoroutineId) {
                CoContext::copy($fromCoroutineId);
                try {
                    // submittaskandroundresult
                    $taskId = $this->submitAsyncTask($imageGenerateRequest);
                    $result = $this->pollTaskResult($taskId, $imageGenerateRequest);

                    $this->validateQwenResponse($result);

                    // success:settingimagedatatoresponseobject
                    $this->addImageDataToResponseQwen($response, $result, $imageGenerateRequest);
                } catch (Exception $e) {
                    // fail:settingerrorinfotoresponseobject(onlysettingfirsterror)
                    if (! $response->hasError()) {
                        $response->setProviderErrorCode($e->getCode());
                        $response->setProviderErrorMessage($e->getMessage());
                    }

                    $this->logger->error('Qwen OpenAIformatgraph:singlerequestfail', [
                        'error_code' => $e->getCode(),
                        'error_message' => $e->getMessage(),
                    ]);
                }
            });
        }

        $parallel->wait();

        // 4. recordfinalresult
        $this->logger->info('Qwen OpenAIformatgraph:andhairhandlecomplete', [
            'request' => $count,
            'successimage' => count($response->getData()),
            'whetherhaveerror' => $response->hasError(),
            'error' => $response->getProviderErrorCode(),
            'errormessage' => $response->getProviderErrorMessage(),
        ]);

        return $response;
    }

    public function getProviderName(): string
    {
        return 'qwen';
    }

    protected function generateImageInternal(ImageGenerateRequest $imageGenerateRequest): ImageGenerateResponse
    {
        $rawResults = $this->generateImageRawInternal($imageGenerateRequest);

        // fromnativeresultmiddleextractimageURL
        $imageUrls = [];
        foreach ($rawResults as $index => $result) {
            $output = $result['output'];
            if (! empty($output['results'])) {
                foreach ($output['results'] as $resultItem) {
                    if (! empty($resultItem['url'])) {
                        $imageUrls[$index] = $resultItem['url'];
                        break; // onlyfirstimageURL
                    }
                }
            }
        }

        return new ImageGenerateResponse(ImageGenerateType::URL, $imageUrls);
    }

    /**
     * generategraphlikecore,returnnativeresult.
     */
    private function generateImageRawInternal(ImageGenerateRequest $imageGenerateRequest): array
    {
        if (! $imageGenerateRequest instanceof QwenImageModelRequest) {
            $this->logger->error('thousandgraph:invalidrequesttype', ['class' => get_class($imageGenerateRequest)]);
            ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR);
        }

        // othergraphis x ,withinis * ,maintainupone,finalalsois *
        $size = $imageGenerateRequest->getWidth() . 'x' . $imageGenerateRequest->getHeight();

        // validationimagesize
        $this->validateImageSize($size, $imageGenerateRequest->getModel());

        $count = $imageGenerateRequest->getGenerateNum();

        $this->logger->info('thousandgraph:startgraph', [
            'prompt' => $imageGenerateRequest->getPrompt(),
            'size' => $size,
            'count' => $count,
        ]);

        // use Parallel andlinehandle
        $parallel = new Parallel();
        for ($i = 0; $i < $count; ++$i) {
            $fromCoroutineId = Coroutine::id();
            $parallel->add(function () use ($imageGenerateRequest, $i, $fromCoroutineId) {
                CoContext::copy($fromCoroutineId);
                try {
                    // submittask(retry)
                    $taskId = $this->submitAsyncTask($imageGenerateRequest);
                    // roundresult(retry)
                    $result = $this->pollTaskResult($taskId, $imageGenerateRequest);

                    return [
                        'success' => true,
                        'output' => $result['output'],
                        'index' => $i,
                    ];
                } catch (Exception $e) {
                    $this->logger->error('thousandgraph:fail', [
                        'error' => $e->getMessage(),
                        'index' => $i,
                    ]);
                    return [
                        'success' => false,
                        'error_code' => $e->getCode(),
                        'error_msg' => $e->getMessage(),
                        'index' => $i,
                    ];
                }
            });
        }

        // gethaveandlinetaskresult
        $results = $parallel->wait();
        $rawResults = [];
        $errors = [];

        // handleresult,maintainnativeformat
        foreach ($results as $result) {
            if ($result['success']) {
                $rawResults[$result['index']] = $result;
            } else {
                $errors[] = [
                    'code' => $result['error_code'] ?? ImageGenerateErrorCode::GENERAL_ERROR->value,
                    'message' => $result['error_msg'] ?? '',
                ];
            }
        }

        if (empty($rawResults)) {
            // usespecificerror,ifallisuseerrorthenuse NO_VALID_IMAGE
            $finalErrorCode = ImageGenerateErrorCode::NO_VALID_IMAGE;
            $finalErrorMsg = '';

            foreach ($errors as $error) {
                if ($error['code'] !== ImageGenerateErrorCode::GENERAL_ERROR->value) {
                    $finalErrorCode = ImageGenerateErrorCode::from($error['code']);
                    $finalErrorMsg = $error['message'];
                    break;
                }
            }

            // ifnothavetospecificerrormessage,usefirsterrormessage
            if (empty($finalErrorMsg) && ! empty($errors[0]['message'])) {
                $finalErrorMsg = $errors[0]['message'];
            }

            $this->logger->error('thousandgraph:haveimagegeneratefail', ['errors' => $errors]);
            ExceptionBuilder::throw($finalErrorCode, $finalErrorMsg);
        }

        // indexsortresult
        ksort($rawResults);
        $rawResults = array_values($rawResults);

        $this->logger->info('thousandgraph:generateend', [
            'imagequantity' => $count,
        ]);

        return $rawResults;
    }

    #[Retry(
        maxAttempts: self::GENERATE_RETRY_COUNT,
        base: self::GENERATE_RETRY_TIME
    )]
    #[RateLimit(create: 4, consume: 1, capacity: 0, key: self::IMAGE_GENERATE_KEY_PREFIX . self::IMAGE_GENERATE_SUBMIT_KEY_PREFIX . ImageGenerateModelType::QwenImage->value, waitTimeout: 60)]
    private function submitAsyncTask(QwenImageModelRequest $request): string
    {
        $prompt = $request->getPrompt();

        try {
            $params = [
                'prompt' => $prompt,
                'size' => $request->getWidth() . '*' . $request->getHeight(),
                'n' => 1, // thousandeachtimeonlycangenerate1image
                'model' => $request->getModel(),
                'watermark' => false, // closeAPIwatermark,useonePHPwatermark
                'prompt_extend' => $request->isPromptExtend(),
            ];

            $response = $this->api->submitTask($params);

            // checkresponseformat
            if (! isset($response['output']['task_id'])) {
                $errorMsg = $response['message'] ?? 'unknownerror';
                $this->logger->warning('thousandgraph:responsemiddletaskID', ['response' => $response]);
                ExceptionBuilder::throw(ImageGenerateErrorCode::RESPONSE_FORMAT_ERROR, $errorMsg);
            }

            $taskId = $response['output']['task_id'];

            $this->logger->info('thousandgraph:submittasksuccess', [
                'taskId' => $taskId,
            ]);

            return $taskId;
        } catch (Exception $e) {
            $this->logger->error('thousandgraph:tasksubmitexception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR, $e->getMessage());
        }
    }

    #[RateLimit(create: 18, consume: 1, capacity: 0, key: self::IMAGE_GENERATE_KEY_PREFIX . self::IMAGE_GENERATE_POLL_KEY_PREFIX . ImageGenerateModelType::QwenImage->value, waitTimeout: 60)]
    #[Retry(
        maxAttempts: self::GENERATE_RETRY_COUNT,
        base: self::GENERATE_RETRY_TIME
    )]
    private function pollTaskResult(string $taskId, QwenImageModelRequest $imageGenerateRequest): array
    {
        $retryCount = 0;

        while ($retryCount < self::MAX_RETRY_COUNT) {
            try {
                $response = $this->api->getTaskResult($taskId);

                // checkresponseformat
                if (! isset($response['output'])) {
                    $this->logger->warning('thousandgraph:querytaskresponseformaterror', ['response' => $response]);
                    ExceptionBuilder::throw(ImageGenerateErrorCode::RESPONSE_FORMAT_ERROR);
                }

                $output = $response['output'];
                $taskStatus = $output['task_status'] ?? '';

                $this->logger->info('thousandgraph:taskstatus', [
                    'taskId' => $taskId,
                    'status' => $taskStatus,
                ]);

                switch ($taskStatus) {
                    case 'SUCCEEDED':
                        if (! empty($output['results'])) {
                            return $response;
                        }
                        $this->logger->error('thousandgraph:taskcompletebutimagedata', ['response' => $response]);
                        ExceptionBuilder::throw(ImageGenerateErrorCode::MISSING_IMAGE_DATA);
                        // no break
                    case 'PENDING':
                    case 'RUNNING':
                        break;
                    case 'FAILED':
                        $errorMsg = $output['message'] ?? 'taskexecutefail';
                        $this->logger->error('thousandgraph:taskexecutefail', ['taskId' => $taskId, 'error' => $errorMsg]);
                        ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR, $errorMsg);
                        // no break
                    default:
                        $this->logger->error('thousandgraph:unknowntaskstatus', ['status' => $taskStatus, 'response' => $response]);
                        ExceptionBuilder::throw(ImageGenerateErrorCode::TASK_TIMEOUT_WITH_REASON);
                }

                ++$retryCount;
                sleep(self::RETRY_INTERVAL);
            } catch (Exception $e) {
                $this->logger->error('thousandgraph:querytaskexception', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'taskId' => $taskId,
                ]);

                ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR, $e->getMessage());
            }
        }

        $this->logger->error('thousandgraph:taskquerytimeout', ['taskId' => $taskId]);
        ExceptionBuilder::throw(ImageGenerateErrorCode::TASK_TIMEOUT);
    }

    /**
     * validationimagesizewhethermatchthousandmodel
     */
    private function validateImageSize(string $size, string $model): void
    {
        switch ($model) {
            case 'qwen-image':
                $this->validateQwenImageSize($size);
                break;
            case 'wan2.2-t2i-flash':
                $this->validateWan22FlashSize($size);
                break;
            default:
                // othermodelnotvalidation
                break;
        }
    }

    /**
     * validationqwen-imagemodelfixedsizecolumn.
     */
    private function validateQwenImageSize(string $size): void
    {
        // qwen-imagesupportfixedsizecolumn
        $supportedSizes = [
            '1664x928',   // 16:9
            '1472x1140',  // 4:3
            '1328x1328',  // 1:1 (default)
            '1140x1472',  // 3:4
            '928x1664',   // 9:16
        ];

        if (! in_array($size, $supportedSizes, true)) {
            $this->logger->error('thousandgraph:qwen-imagenot supportedimagesize', [
                'requested_size' => $size,
                'supported_sizes' => $supportedSizes,
                'model' => 'qwen-image',
            ]);

            ExceptionBuilder::throw(
                ImageGenerateErrorCode::UNSUPPORTED_IMAGE_SIZE,
                'image_generate.unsupported_image_size',
                [
                    'size' => $size,
                    'supported_sizes' => implode('、', $supportedSizes),
                ]
            );
        }
    }

    /**
     * validationwan2.2-t2i-flashmodelbetweensize.
     */
    private function validateWan22FlashSize(string $size): void
    {
        $dimensions = explode('x', $size);
        if (count($dimensions) !== 2) {
            $this->logger->error('thousandgraph:wan2.2-t2i-flashsizeformaterror', [
                'requested_size' => $size,
                'model' => 'wan2.2-t2i-flash',
            ]);

            ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR, 'image_generate.invalid_size_format');
        }

        $width = (int) $dimensions[0];
        $height = (int) $dimensions[1];

        // wan2.2-t2i-flashsupport512-1440likebetween
        $minSize = 512;
        $maxSize = 1440;

        if ($width < $minSize || $width > $maxSize || $height < $minSize || $height > $maxSize) {
            $this->logger->error('thousandgraph:wan2.2-t2i-flashsizeoutsupportrange', [
                'requested_size' => $size,
                'width' => $width,
                'height' => $height,
                'min_size' => $minSize,
                'max_size' => $maxSize,
                'model' => 'wan2.2-t2i-flash',
            ]);

            ExceptionBuilder::throw(
                ImageGenerateErrorCode::UNSUPPORTED_IMAGE_SIZE_RANGE,
                'image_generate.unsupported_image_size_range',
                [
                    'size' => $size,
                    'min_size' => $minSize,
                    'max_size' => $maxSize,
                ]
            );
        }
    }

    /**
     * forthousandoriginaldataaddwatermark.
     */
    private function processQwenRawDataWithWatermark(array $rawData, ImageGenerateRequest $imageGenerateRequest): array
    {
        foreach ($rawData as $index => &$result) {
            if (! isset($result['output']['results'])) {
                continue;
            }

            try {
                // handle results arraymiddleimageURL
                foreach ($result['output']['results'] as $i => &$resultItem) {
                    if (! empty($resultItem['url'])) {
                        $resultItem['url'] = $this->watermarkProcessor->addWatermarkToUrl($resultItem['url'], $imageGenerateRequest);
                    }
                }
                unset($resultItem);
            } catch (Exception $e) {
                // watermarkhandlefailo clock,recorderrorbutnotimpactimagereturn
                $this->logger->error('thousandimagewatermarkhandlefail', [
                    'index' => $index,
                    'error' => $e->getMessage(),
                ]);
                // continuehandledownoneimage,currentimagemaintainoriginalstatus
            }
        }

        return $rawData;
    }

    /**
     * validatethousandAPIresponsedataformat.
     */
    private function validateQwenResponse(array $result): void
    {
        if (empty($result['output']) || ! is_array($result['output'])) {
            throw new Exception('thousandresponsedataformaterror:outputfield');
        }

        $output = $result['output'];
        if (empty($output['results']) || ! is_array($output['results'])) {
            throw new Exception('thousandresponsedataformaterror:resultsfield');
        }

        // checkfirstresultwhetherhaveURL
        if (empty($output['results'][0]['url'])) {
            throw new Exception('thousandresponsedataformaterror:imageURL');
        }
    }

    /**
     * willthousandimagedataaddtoOpenAIresponseobjectmiddle.
     */
    private function addImageDataToResponseQwen(
        OpenAIFormatResponse $response,
        array $qwenResult,
        ImageGenerateRequest $imageGenerateRequest
    ): void {
        // useRedislockensureandhairsecurity
        $lockOwner = $this->lockResponse($response);
        try {
            // fromthousandresponsemiddleextractdata
            if (empty($qwenResult['output']['results']) || ! is_array($qwenResult['output']['results'])) {
                return;
            }

            $results = $qwenResult['output']['results'];
            $currentData = $response->getData();
            $currentUsage = $response->getUsage() ?? new ImageUsage();

            // handle results arraymiddlefirstimageURL
            foreach ($results as $resultItem) {
                if (! empty($resultItem['url'])) {
                    try {
                        // handlewatermark
                        $processedUrl = $this->watermarkProcessor->addWatermarkToUrl($resultItem['url'], $imageGenerateRequest);
                        $currentData[] = [
                            'url' => $processedUrl,
                        ];
                    } catch (Exception $e) {
                        $this->logger->error('Qwenaddimagedata:URLwatermarkhandlefail', [
                            'error' => $e->getMessage(),
                            'url' => $resultItem['url'],
                        ]);
                        // watermarkhandlefailo clockuseoriginalURL
                        $currentData[] = [
                            'url' => $resultItem['url'],
                        ];
                    }
                    break; // onlyfirstimage
                }
            }

            // usageinfo
            if (! empty($qwenResult['usage']) && is_array($qwenResult['usage'])) {
                $currentUsage->addGeneratedImages($qwenResult['usage']['image_count'] ?? 1);
            // thousandnothavetokeninfo,maintaindefaultvalue
            } else {
                // ifnothaveusageinfo,defaultincrease1image
                $currentUsage->addGeneratedImages(1);
            }

            // updateresponseobject
            $response->setData($currentData);
            $response->setUsage($currentUsage);
        } finally {
            // ensurelockonewillberelease
            $this->unlockResponse($response, $lockOwner);
        }
    }
}
