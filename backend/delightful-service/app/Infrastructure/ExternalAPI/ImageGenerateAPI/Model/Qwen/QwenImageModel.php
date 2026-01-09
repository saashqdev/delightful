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
    // most大round询retrycount
    private const MAX_RETRY_COUNT = 30;

    // round询retrybetween隔（second）
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
        // 通义thousand问notuseAK/SKauthentication，thismethodfornullimplement
    }

    public function setSK(string $sk)
    {
        // 通义thousand问notuseAK/SKauthentication，thismethodfornullimplement
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
        // 1. 预先createresponseobject
        $response = new OpenAIFormatResponse([
            'created' => time(),
            'provider' => $this->getProviderName(),
            'data' => [],
        ]);

        // 2. parametervalidate
        if (! $imageGenerateRequest instanceof QwenImageModelRequest) {
            $this->logger->error('Qwen OpenAIformat生graph：invalidrequesttype', ['class' => get_class($imageGenerateRequest)]);
            return $response; // returnnulldataresponse
        }

        // 3. andhairhandle - 直接操asresponseobject
        $count = $imageGenerateRequest->getGenerateNum();
        $parallel = new Parallel();
        $fromCoroutineId = Coroutine::id();

        for ($i = 0; $i < $count; ++$i) {
            $parallel->add(function () use ($imageGenerateRequest, $response, $fromCoroutineId) {
                CoContext::copy($fromCoroutineId);
                try {
                    // submittaskandround询result
                    $taskId = $this->submitAsyncTask($imageGenerateRequest);
                    $result = $this->pollTaskResult($taskId, $imageGenerateRequest);

                    $this->validateQwenResponse($result);

                    // success：settingimagedatatoresponseobject
                    $this->addImageDataToResponseQwen($response, $result, $imageGenerateRequest);
                } catch (Exception $e) {
                    // fail：settingerrorinfotoresponseobject（只settingfirsterror）
                    if (! $response->hasError()) {
                        $response->setProviderErrorCode($e->getCode());
                        $response->setProviderErrorMessage($e->getMessage());
                    }

                    $this->logger->error('Qwen OpenAIformat生graph：singlerequestfail', [
                        'error_code' => $e->getCode(),
                        'error_message' => $e->getMessage(),
                    ]);
                }
            });
        }

        $parallel->wait();

        // 4. recordfinalresult
        $this->logger->info('Qwen OpenAIformat生graph：andhairhandlecomplete', [
            '总request数' => $count,
            'successimage数' => count($response->getData()),
            'whetherhaveerror' => $response->hasError(),
            'error码' => $response->getProviderErrorCode(),
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
                        break; // 只取firstimageURL
                    }
                }
            }
        }

        return new ImageGenerateResponse(ImageGenerateType::URL, $imageUrls);
    }

    /**
     * generategraphlike核core逻辑，returnnativeresult.
     */
    private function generateImageRawInternal(ImageGenerateRequest $imageGenerateRequest): array
    {
        if (! $imageGenerateRequest instanceof QwenImageModelRequest) {
            $this->logger->error('通义thousand问文生graph：invalidrequesttype', ['class' => get_class($imageGenerateRequest)]);
            ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR);
        }

        // other文生graphis x ，阿withinis * ，保持up游one致，final传入alsois *
        $size = $imageGenerateRequest->getWidth() . 'x' . $imageGenerateRequest->getHeight();

        // validationimagesize
        $this->validateImageSize($size, $imageGenerateRequest->getModel());

        $count = $imageGenerateRequest->getGenerateNum();

        $this->logger->info('通义thousand问文生graph：start生graph', [
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
                    // submittask（带retry）
                    $taskId = $this->submitAsyncTask($imageGenerateRequest);
                    // round询result（带retry）
                    $result = $this->pollTaskResult($taskId, $imageGenerateRequest);

                    return [
                        'success' => true,
                        'output' => $result['output'],
                        'index' => $i,
                    ];
                } catch (Exception $e) {
                    $this->logger->error('通义thousand问文生graph：fail', [
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

        // get所haveandlinetaskresult
        $results = $parallel->wait();
        $rawResults = [];
        $errors = [];

        // handleresult，保持nativeformat
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
            // 优先usespecificerror码，ifallis通useerrorthenuse NO_VALID_IMAGE
            $finalErrorCode = ImageGenerateErrorCode::NO_VALID_IMAGE;
            $finalErrorMsg = '';

            foreach ($errors as $error) {
                if ($error['code'] !== ImageGenerateErrorCode::GENERAL_ERROR->value) {
                    $finalErrorCode = ImageGenerateErrorCode::from($error['code']);
                    $finalErrorMsg = $error['message'];
                    break;
                }
            }

            // ifnothave找tospecificerrormessage，usefirsterrormessage
            if (empty($finalErrorMsg) && ! empty($errors[0]['message'])) {
                $finalErrorMsg = $errors[0]['message'];
            }

            $this->logger->error('通义thousand问文生graph：所haveimagegenerate均fail', ['errors' => $errors]);
            ExceptionBuilder::throw($finalErrorCode, $finalErrorMsg);
        }

        // 按indexsortresult
        ksort($rawResults);
        $rawResults = array_values($rawResults);

        $this->logger->info('通义thousand问文生graph：generateend', [
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
                'n' => 1, // 通义thousand问eachtime只cangenerate1张image
                'model' => $request->getModel(),
                'watermark' => false, // closeAPIwatermark，use统onePHPwatermark
                'prompt_extend' => $request->isPromptExtend(),
            ];

            $response = $this->api->submitTask($params);

            // checkresponseformat
            if (! isset($response['output']['task_id'])) {
                $errorMsg = $response['message'] ?? 'unknownerror';
                $this->logger->warning('通义thousand问文生graph：responsemiddle缺少taskID', ['response' => $response]);
                ExceptionBuilder::throw(ImageGenerateErrorCode::RESPONSE_FORMAT_ERROR, $errorMsg);
            }

            $taskId = $response['output']['task_id'];

            $this->logger->info('通义thousand问文生graph：submittasksuccess', [
                'taskId' => $taskId,
            ]);

            return $taskId;
        } catch (Exception $e) {
            $this->logger->error('通义thousand问文生graph：tasksubmitexception', [
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
                    $this->logger->warning('通义thousand问文生graph：querytaskresponseformaterror', ['response' => $response]);
                    ExceptionBuilder::throw(ImageGenerateErrorCode::RESPONSE_FORMAT_ERROR);
                }

                $output = $response['output'];
                $taskStatus = $output['task_status'] ?? '';

                $this->logger->info('通义thousand问文生graph：taskstatus', [
                    'taskId' => $taskId,
                    'status' => $taskStatus,
                ]);

                switch ($taskStatus) {
                    case 'SUCCEEDED':
                        if (! empty($output['results'])) {
                            return $response;
                        }
                        $this->logger->error('通义thousand问文生graph：taskcompletebut缺少imagedata', ['response' => $response]);
                        ExceptionBuilder::throw(ImageGenerateErrorCode::MISSING_IMAGE_DATA);
                        // no break
                    case 'PENDING':
                    case 'RUNNING':
                        break;
                    case 'FAILED':
                        $errorMsg = $output['message'] ?? 'taskexecutefail';
                        $this->logger->error('通义thousand问文生graph：taskexecutefail', ['taskId' => $taskId, 'error' => $errorMsg]);
                        ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR, $errorMsg);
                        // no break
                    default:
                        $this->logger->error('通义thousand问文生graph：unknowntaskstatus', ['status' => $taskStatus, 'response' => $response]);
                        ExceptionBuilder::throw(ImageGenerateErrorCode::TASK_TIMEOUT_WITH_REASON);
                }

                ++$retryCount;
                sleep(self::RETRY_INTERVAL);
            } catch (Exception $e) {
                $this->logger->error('通义thousand问文生graph：querytaskexception', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'taskId' => $taskId,
                ]);

                ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR, $e->getMessage());
            }
        }

        $this->logger->error('通义thousand问文生graph：taskquerytimeout', ['taskId' => $taskId]);
        ExceptionBuilder::throw(ImageGenerateErrorCode::TASK_TIMEOUT);
    }

    /**
     * validationimagesizewhethermatch通义thousand问model规格
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
                // othermodel暂notvalidation
                break;
        }
    }

    /**
     * validationqwen-imagemodelfixedsizecolumn表.
     */
    private function validateQwenImageSize(string $size): void
    {
        // qwen-imagesupportfixedsizecolumn表
        $supportedSizes = [
            '1664x928',   // 16:9
            '1472x1140',  // 4:3
            '1328x1328',  // 1:1 (default)
            '1140x1472',  // 3:4
            '928x1664',   // 9:16
        ];

        if (! in_array($size, $supportedSizes, true)) {
            $this->logger->error('通义thousand问文生graph：qwen-imagenot supportedimagesize', [
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
     * validationwan2.2-t2i-flashmodel区betweensize.
     */
    private function validateWan22FlashSize(string $size): void
    {
        $dimensions = explode('x', $size);
        if (count($dimensions) !== 2) {
            $this->logger->error('通义thousand问文生graph：wan2.2-t2i-flashsizeformaterror', [
                'requested_size' => $size,
                'model' => 'wan2.2-t2i-flash',
            ]);

            ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR, 'image_generate.invalid_size_format');
        }

        $width = (int) $dimensions[0];
        $height = (int) $dimensions[1];

        // wan2.2-t2i-flashsupport512-1440like素区between
        $minSize = 512;
        $maxSize = 1440;

        if ($width < $minSize || $width > $maxSize || $height < $minSize || $height > $maxSize) {
            $this->logger->error('通义thousand问文生graph：wan2.2-t2i-flashsize超outsupportrange', [
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
     * for通义thousand问originaldataaddwatermark.
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
                // watermarkhandlefailo clock，recorderrorbutnot影响imagereturn
                $this->logger->error('通义thousand问imagewatermarkhandlefail', [
                    'index' => $index,
                    'error' => $e->getMessage(),
                ]);
                // continuehandledownone张image，currentimage保持originalstatus
            }
        }

        return $rawData;
    }

    /**
     * validate通义thousand问APIresponsedataformat.
     */
    private function validateQwenResponse(array $result): void
    {
        if (empty($result['output']) || ! is_array($result['output'])) {
            throw new Exception('通义thousand问responsedataformaterror：缺少outputfield');
        }

        $output = $result['output'];
        if (empty($output['results']) || ! is_array($output['results'])) {
            throw new Exception('通义thousand问responsedataformaterror：缺少resultsfield');
        }

        // checkfirstresultwhetherhaveURL
        if (empty($output['results'][0]['url'])) {
            throw new Exception('通义thousand问responsedataformaterror：缺少imageURL');
        }
    }

    /**
     * will通义thousand问imagedataaddtoOpenAIresponseobjectmiddle.
     */
    private function addImageDataToResponseQwen(
        OpenAIFormatResponse $response,
        array $qwenResult,
        ImageGenerateRequest $imageGenerateRequest
    ): void {
        // useRedislockensureandhairsecurity
        $lockOwner = $this->lockResponse($response);
        try {
            // from通义thousand问responsemiddleextractdata
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
                        $this->logger->error('Qwenaddimagedata：URLwatermarkhandlefail', [
                            'error' => $e->getMessage(),
                            'url' => $resultItem['url'],
                        ]);
                        // watermarkhandlefailo clockuseoriginalURL
                        $currentData[] = [
                            'url' => $resultItem['url'],
                        ];
                    }
                    break; // 只取firstimage
                }
            }

            // 累计usageinfo
            if (! empty($qwenResult['usage']) && is_array($qwenResult['usage'])) {
                $currentUsage->addGeneratedImages($qwenResult['usage']['image_count'] ?? 1);
            // 通义thousand问nothavetokeninfo，保持defaultvalue
            } else {
                // ifnothaveusageinfo，defaultincrease1张image
                $currentUsage->addGeneratedImages(1);
            }

            // updateresponseobject
            $response->setData($currentData);
            $response->setUsage($currentUsage);
        } finally {
            // ensurelockone定willberelease
            $this->unlockResponse($response, $lockOwner);
        }
    }
}
