<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\ImageGenerateAPI\Model\Volcengine;

use App\ErrorCode\ImageGenerateErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\AbstractImageGenerate;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\ImageGenerate;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\ImageGenerateModelType;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\ImageGenerateType;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Request\ImageGenerateRequest;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Request\VolcengineModelRequest;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Response\ImageGenerateResponse;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Response\ImageUsage;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Response\OpenAIFormatResponse;
use App\Infrastructure\Util\Context\CoContext;
use App\Infrastructure\Util\SSRF\SSRFUtil;
use Exception;
use Hyperf\Codec\Json;
use Hyperf\Coroutine\Parallel;
use Hyperf\Engine\Coroutine;
use Hyperf\RateLimit\Annotation\RateLimit;
use Hyperf\Retry\Annotation\Retry;

class VolcengineModel extends AbstractImageGenerate
{
    // 最大轮询retry次数
    private const MAX_RETRY_COUNT = 30;

    // 轮询retry间隔（秒）
    private const RETRY_INTERVAL = 2;

    // 图生图quantity限制
    private const IMAGE_TO_IMAGE_IMAGE_COUNT = 1;

    private VolcengineAPI $api;

    private string $textToImageModelVersion = 'general_v2.1_L';

    private string $textToImageReqScheduleConf = 'general_v20_9B_pe';

    // 图生图configuration
    private string $imageToImageReqKey = 'byteedit_v2.0';

    public function __construct(array $serviceProviderConfig)
    {
        $this->api = new VolcengineAPI($serviceProviderConfig['ak'], $serviceProviderConfig['sk']);
    }

    public function generateImageRaw(ImageGenerateRequest $imageGenerateRequest): array
    {
        return $this->generateImageRawInternal($imageGenerateRequest);
    }

    public function setAK(string $ak)
    {
        $this->api->setAk($ak);
    }

    public function setSK(string $sk)
    {
        $this->api->setSk($sk);
    }

    public function setApiKey(string $apiKey)
    {
        // TODO: Implement setApiKey() method.
    }

    public function generateImageRawWithWatermark(ImageGenerateRequest $imageGenerateRequest): array
    {
        $rawData = $this->generateImageRaw($imageGenerateRequest);

        return $this->processVolcengineRawDataWithWatermark($rawData, $imageGenerateRequest);
    }

    /**
     * generate图像并returnOpenAIformatresponse - V2一体化version.
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
        if (! $imageGenerateRequest instanceof VolcengineModelRequest) {
            $this->logger->error('Volcengine OpenAIformat生图：invalid的requesttype', ['class' => get_class($imageGenerateRequest)]);
            return $response; // returnnulldataresponse
        }

        // 3. 判断是图生图还是文生图
        $isImageToImage = ! empty($imageGenerateRequest->getReferenceImage());
        $count = $isImageToImage ? self::IMAGE_TO_IMAGE_IMAGE_COUNT : $imageGenerateRequest->getGenerateNum();

        // 4. 并发handle - 直接操作responseobject
        $parallel = new Parallel();
        $fromCoroutineId = Coroutine::id();

        for ($i = 0; $i < $count; ++$i) {
            $parallel->add(function () use ($imageGenerateRequest, $isImageToImage, $response, $fromCoroutineId) {
                CoContext::copy($fromCoroutineId);
                try {
                    $result = $this->requestImageGenerationV2($imageGenerateRequest, $isImageToImage);
                    $this->validateVolcengineResponse($result);

                    // success：settingimagedata到responseobject
                    $this->addImageDataToResponse($response, $result, $imageGenerateRequest);
                } catch (Exception $e) {
                    // fail：settingerrorinfo到responseobject（只settingfirsterror）
                    if (! $response->hasError()) {
                        $response->setProviderErrorCode($e->getCode());
                        $response->setProviderErrorMessage($e->getMessage());
                    }

                    $this->logger->error('Volcengine OpenAIformat生图：单个requestfail', [
                        'error_code' => $e->getCode(),
                        'error_message' => $e->getMessage(),
                    ]);
                }
            });
        }

        $parallel->wait();

        // 5. recordfinalresult
        $this->logger->info('Volcengine OpenAIformat生图：并发handlecomplete', [
            '总request数' => $count,
            'successimage数' => count($response->getData()),
            '是否有error' => $response->hasError(),
            'error码' => $response->getProviderErrorCode(),
            'errormessage' => $response->getProviderErrorMessage(),
        ]);

        return $response;
    }

    public function getProviderName(): string
    {
        return 'volcengine';
    }

    /**
     * generate图像的核心逻辑，return ImageGenerateResponse.
     */
    protected function generateImageInternal(ImageGenerateRequest $imageGenerateRequest): ImageGenerateResponse
    {
        if (! $imageGenerateRequest instanceof VolcengineModelRequest) {
            $this->logger->error('火山文生图：invalid的requesttype', ['class' => get_class($imageGenerateRequest)]);
            ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR);
        }

        // 判断是图生图还是文生图
        $isImageToImage = ! empty($imageGenerateRequest->getReferenceImage());
        $count = $isImageToImage ? self::IMAGE_TO_IMAGE_IMAGE_COUNT : $imageGenerateRequest->getGenerateNum();

        $this->logger->info('火山文生图：start生图', [
            'prompt' => $imageGenerateRequest->getPrompt(),
            'negativePrompt' => $imageGenerateRequest->getNegativePrompt(),
            'width' => $imageGenerateRequest->getWidth(),
            'height' => $imageGenerateRequest->getHeight(),
            'req_key' => $imageGenerateRequest->getModel(),
            'textToImageModelVersion' => $this->textToImageModelVersion,
            'textToImageReqScheduleConf' => $this->textToImageReqScheduleConf,
        ]);

        // use Parallel 并行handle
        $parallel = new Parallel();
        for ($i = 0; $i < $count; ++$i) {
            $fromCoroutineId = Coroutine::id();
            $parallel->add(function () use ($imageGenerateRequest, $isImageToImage, $i, $fromCoroutineId) {
                CoContext::copy($fromCoroutineId);
                try {
                    // submittask（带retry）
                    $taskId = $this->submitAsyncTask($imageGenerateRequest, $isImageToImage);
                    // 轮询result（带retry）
                    $result = $this->pollTaskResult($taskId, $imageGenerateRequest);

                    return [
                        'success' => true,
                        'data' => $result['data'],
                        'index' => $i,
                    ];
                } catch (Exception $e) {
                    $this->logger->error('火山文生图：fail', [
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

        // get所有并行task的result
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
            // 优先use具体的error码，如果都是通用error则use NO_VALID_IMAGE
            $finalErrorCode = ImageGenerateErrorCode::NO_VALID_IMAGE;
            $finalErrorMsg = '';

            foreach ($errors as $error) {
                if ($error['code'] !== ImageGenerateErrorCode::GENERAL_ERROR->value) {
                    $finalErrorCode = ImageGenerateErrorCode::from($error['code']);
                    $finalErrorMsg = $error['message'];
                    break;
                }
            }

            // 如果没有找到具体errormessage，usefirsterrormessage
            if (empty($finalErrorMsg) && ! empty($errors[0]['message'])) {
                $finalErrorMsg = $errors[0]['message'];
            }

            $this->logger->error('火山文生图：所有imagegenerate均fail', ['errors' => $errors]);
            ExceptionBuilder::throw($finalErrorCode, $finalErrorMsg);
        }

        // 按索引sortresult
        ksort($rawResults);
        $rawResults = array_values($rawResults);

        $this->logger->info('火山文生图：generateend', [
            'imagequantity' => $count,
        ]);

        // 从nativeresult中提取imageURL
        $imageUrls = [];
        foreach ($rawResults as $index => $result) {
            $data = $result['data'];
            if (! empty($data['binary_data_base64'])) {
                $imageUrls[$index] = $data['binary_data_base64'][0];
            } elseif (! empty($data['image_urls'])) {
                $imageUrls[$index] = $data['image_urls'][0];
            }
        }

        return new ImageGenerateResponse(ImageGenerateType::URL, $imageUrls);
    }

    /**
     * generate图像的核心逻辑，returnnativeresult.
     */
    private function generateImageRawInternal(ImageGenerateRequest $imageGenerateRequest): array
    {
        if (! $imageGenerateRequest instanceof VolcengineModelRequest) {
            $this->logger->error('火山文生图：invalid的requesttype', ['class' => get_class($imageGenerateRequest)]);
            ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR);
        }

        // 判断是图生图还是文生图
        $isImageToImage = ! empty($imageGenerateRequest->getReferenceImage());
        $count = $isImageToImage ? self::IMAGE_TO_IMAGE_IMAGE_COUNT : $imageGenerateRequest->getGenerateNum();

        $this->logger->info('火山文生图：start生图', [
            'prompt' => $imageGenerateRequest->getPrompt(),
            'negativePrompt' => $imageGenerateRequest->getNegativePrompt(),
            'width' => $imageGenerateRequest->getWidth(),
            'height' => $imageGenerateRequest->getHeight(),
            'req_key' => $imageGenerateRequest->getModel(),
            'textToImageModelVersion' => $this->textToImageModelVersion,
            'textToImageReqScheduleConf' => $this->textToImageReqScheduleConf,
        ]);

        // use Parallel 并行handle
        $parallel = new Parallel();
        for ($i = 0; $i < $count; ++$i) {
            $fromCoroutineId = Coroutine::id();
            $parallel->add(function () use ($imageGenerateRequest, $isImageToImage, $i, $fromCoroutineId) {
                CoContext::copy($fromCoroutineId);
                try {
                    // submittask（带retry）
                    $taskId = $this->submitAsyncTask($imageGenerateRequest, $isImageToImage);
                    // 轮询result（带retry）
                    $result = $this->pollTaskResult($taskId, $imageGenerateRequest);

                    return [
                        'success' => true,
                        'data' => $result['data'],
                        'index' => $i,
                    ];
                } catch (Exception $e) {
                    $this->logger->error('火山文生图：fail', [
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

        $results = $parallel->wait();

        // checkresult
        $rawResults = [];
        $errors = [];
        $finalErrorCode = ImageGenerateErrorCode::GENERAL_ERROR;
        $finalErrorMsg = 'imagegeneratefail';

        foreach ($results as $result) {
            if ($result['success'] === true) {
                $rawResults[$result['index']] = $result;
            } else {
                $errors[] = [
                    'index' => $result['index'],
                    'code' => $result['error_code'],
                    'message' => $result['error_msg'],
                ];
                if (! empty($result['error_code'])) {
                    $finalErrorCode = $result['error_code'];
                    $finalErrorMsg = $result['error_msg'];
                }
            }
        }

        // check是否有success的imagegenerate
        if (empty($rawResults)) {
            $this->logger->error('火山文生图：所有imagegenerate均fail', ['errors' => $errors]);
            ExceptionBuilder::throw($finalErrorCode, $finalErrorMsg);
        }

        // 按索引sortresult
        ksort($rawResults);
        $rawResults = array_values($rawResults);

        $this->logger->info('火山文生图：generateend', [
            'imagequantity' => count($rawResults),
        ]);

        return $rawResults;
    }

    #[Retry(
        maxAttempts: self::GENERATE_RETRY_COUNT,
        base: self::GENERATE_RETRY_TIME
    )]
    #[RateLimit(create: 4, consume: 1, capacity: 0, key: ImageGenerate::IMAGE_GENERATE_KEY_PREFIX . ImageGenerate::IMAGE_GENERATE_SUBMIT_KEY_PREFIX . ImageGenerateModelType::Volcengine->value, waitTimeout: 60)]
    private function submitAsyncTask(VolcengineModelRequest $request, bool $isImageToImage): string
    {
        $prompt = $request->getPrompt();
        $width = (int) $request->getWidth();
        $height = (int) $request->getHeight();

        try {
            $body = [
                'return_url' => true,
                'prompt' => $prompt,
            ];
            if ($isImageToImage) {
                // 图生图configuration
                if (empty($request->getReferenceImage())) {
                    $this->logger->error('火山图生图：缺少源image');
                    ExceptionBuilder::throw(ImageGenerateErrorCode::MISSING_IMAGE_DATA, 'image_generate.image_to_image_missing_source');
                }
                $this->validateImageToImageAspectRatio($request->getReferenceImage());

                $body['image_urls'] = $request->getReferenceImage();
                $body['req_key'] = $this->imageToImageReqKey;
            } else {
                $body['req_key'] = $request->getModel();
                $body['width'] = $width;
                $body['height'] = $height;
                $body['use_sr'] = $request->getUseSr();
            }

            $response = $this->api->submitTask($body);

            if (! isset($response['code'])) {
                $this->logger->warning('火山文生图：responseformaterror', ['response' => $response]);
                ExceptionBuilder::throw(ImageGenerateErrorCode::RESPONSE_FORMAT_ERROR);
            }

            if ($response['code'] !== 10000) {
                $errorMsg = $response['message'] ?? '';
                $errorCode = match ($response['code']) {
                    50411 => ImageGenerateErrorCode::INPUT_IMAGE_AUDIT_FAILED,
                    50511 => ImageGenerateErrorCode::OUTPUT_IMAGE_AUDIT_FAILED_WITH_REASON,
                    50412, 50413 => ImageGenerateErrorCode::INPUT_TEXT_AUDIT_FAILED,
                    default => ImageGenerateErrorCode::GENERAL_ERROR,
                };

                $this->logger->warning('火山文生图：tasksubmitfail', [
                    'code' => $response['code'],
                    'message' => $response['message'] ?? '',
                ]);

                ExceptionBuilder::throw($errorCode, $errorMsg);
            }

            if (! isset($response['data']['task_id'])) {
                $this->logger->warning('火山文生图：response中缺少taskID', ['response' => $response]);
                ExceptionBuilder::throw(ImageGenerateErrorCode::RESPONSE_FORMAT_ERROR);
            }

            $taskId = $response['data']['task_id'];

            $this->logger->info('火山文生图：submittasksuccess', [
                'taskId' => $taskId,
                'isImageToImage' => $isImageToImage,
            ]);

            return $taskId;
        } catch (Exception $e) {
            $this->logger->error('火山文生图：tasksubmitexception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR);
        }
    }

    #[RateLimit(create: 18, consume: 1, capacity: 0, key: ImageGenerate::IMAGE_GENERATE_KEY_PREFIX . self::IMAGE_GENERATE_POLL_KEY_PREFIX . ImageGenerateModelType::Volcengine->value, waitTimeout: 60)]
    #[Retry(
        maxAttempts: self::GENERATE_RETRY_COUNT,
        base: self::GENERATE_RETRY_TIME
    )]
    private function pollTaskResult(string $taskId, VolcengineModelRequest $imageGenerateRequest): array
    {
        $model = $imageGenerateRequest->getModel();
        $reqKey = $model;
        $retryCount = 0;

        $reqJson = ['return_url' => true];

        $reqJsonString = Json::encode($reqJson);

        while ($retryCount < self::MAX_RETRY_COUNT) {
            try {
                $params = [
                    'task_id' => $taskId,
                    'req_key' => $reqKey,
                    'req_json' => $reqJsonString,
                ];

                $response = $this->api->getTaskResult($params);

                if (! isset($response['code'])) {
                    $this->logger->warning('火山文生图：querytaskresponseformaterror', ['response' => $response]);
                    ExceptionBuilder::throw(ImageGenerateErrorCode::RESPONSE_FORMAT_ERROR);
                }

                if ($response['code'] !== 10000) {
                    $errorMsg = $response['message'] ?? '';
                    $errorCode = match ($response['code']) {
                        50411 => ImageGenerateErrorCode::INPUT_IMAGE_AUDIT_FAILED,
                        50511 => ImageGenerateErrorCode::OUTPUT_IMAGE_AUDIT_FAILED_WITH_REASON,
                        50412, 50413 => ImageGenerateErrorCode::INPUT_TEXT_AUDIT_FAILED,
                        50512 => ImageGenerateErrorCode::OUTPUT_TEXT_AUDIT_FAILED,
                        default => ImageGenerateErrorCode::GENERAL_ERROR,
                    };

                    $this->logger->warning('火山文生图：querytaskfail', [
                        'code' => $response['code'],
                        'message' => $response['message'] ?? '',
                    ]);

                    ExceptionBuilder::throw($errorCode, $errorMsg);
                }

                if (! isset($response['data']) || ! isset($response['data']['status'])) {
                    $this->logger->warning('火山文生图：responseformaterror', ['response' => $response]);
                    ExceptionBuilder::throw(ImageGenerateErrorCode::RESPONSE_FORMAT_ERROR);
                }

                $data = $response['data'];
                $status = $data['status'];

                $this->logger->info('火山文生图：taskstatus', [
                    'taskId' => $taskId,
                    'status' => $status,
                ]);

                switch ($status) {
                    case 'done':
                        if (! empty($data['binary_data_base64']) || ! empty($data['image_urls'])) {
                            return $response;
                        }
                        $this->logger->error('火山文生图：taskcomplete但缺少imagedata', ['response' => $response]);
                        ExceptionBuilder::throw(ImageGenerateErrorCode::MISSING_IMAGE_DATA);
                        // no break
                    case 'in_queue':
                    case 'generating':
                        break;
                    case 'not_found':
                        $this->logger->error('火山文生图：task未找到或已expire', ['taskId' => $taskId]);
                        ExceptionBuilder::throw(ImageGenerateErrorCode::TASK_TIMEOUT_WITH_REASON);
                        // no break
                    default:
                        $this->logger->error('火山文生图：未知的taskstatus', ['status' => $status, 'response' => $response]);
                        ExceptionBuilder::throw(ImageGenerateErrorCode::TASK_TIMEOUT_WITH_REASON);
                }

                ++$retryCount;
                sleep(self::RETRY_INTERVAL);
            } catch (Exception $e) {
                $this->logger->error('火山文生图：querytaskexception', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'taskId' => $taskId,
                ]);

                ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR);
            }
        }

        $this->logger->error('火山文生图：taskquerytimeout', ['taskId' => $taskId]);
        ExceptionBuilder::throw(ImageGenerateErrorCode::TASK_TIMEOUT);
    }

    /**
     * V2version：组合submittask和轮询result，用于OpenAIformatgenerate.
     */
    private function requestImageGenerationV2(VolcengineModelRequest $imageGenerateRequest, bool $isImageToImage): array
    {
        // submittask
        $taskId = $this->submitAsyncTask($imageGenerateRequest, $isImageToImage);

        // 轮询result
        return $this->pollTaskResult($taskId, $imageGenerateRequest);
    }

    /**
     * validate火山引擎APIresponsedataformat.
     */
    private function validateVolcengineResponse(array $result): void
    {
        if (empty($result['data']) || ! is_array($result['data'])) {
            throw new Exception('火山引擎responsedataformaterror：缺少datafield');
        }

        $data = $result['data'];
        // 优先check image_urls，然后check binary_data_base64
        $hasValidImageData = (! empty($data['image_urls']) && ! empty($data['image_urls'][0]))
                            || (! empty($data['binary_data_base64']) && ! empty($data['binary_data_base64'][0]));

        if (! $hasValidImageData) {
            throw new Exception('火山引擎responsedataformaterror：缺少imagedata');
        }
    }

    /**
     * 将火山引擎imagedata添加到OpenAIresponseobject中.
     */
    private function addImageDataToResponse(
        OpenAIFormatResponse $response,
        array $volcengineResult,
        ImageGenerateRequest $imageGenerateRequest
    ): void {
        // useRedislockensure并发安全
        $lockOwner = $this->lockResponse($response);
        try {
            // 从火山引擎response中提取data
            if (empty($volcengineResult['data']) || ! is_array($volcengineResult['data'])) {
                return;
            }

            $data = $volcengineResult['data'];
            $currentData = $response->getData();
            $currentUsage = $response->getUsage() ?? new ImageUsage();

            // 优先handle URL formatimage，参考现有逻辑只取firstimage
            if (! empty($data['image_urls']) && ! empty($data['image_urls'][0])) {
                $imageUrl = $data['image_urls'][0];
                try {
                    // handle水印
                    $processedUrl = $this->watermarkProcessor->addWatermarkToUrl($imageUrl, $imageGenerateRequest);
                    $currentData[] = [
                        'url' => $processedUrl,
                    ];
                } catch (Exception $e) {
                    $this->logger->error('Volcengine添加imagedata：URL水印handlefail', [
                        'error' => $e->getMessage(),
                        'url' => $imageUrl,
                    ]);
                    // 水印handlefail时useoriginalURL
                    $currentData[] = [
                        'url' => $imageUrl,
                    ];
                }
            } elseif (! empty($data['binary_data_base64']) && ! empty($data['binary_data_base64'][0])) {
                // 备选：handle base64 formatimage，只取firstimage
                $base64Image = $data['binary_data_base64'][0];
                try {
                    // handle水印
                    $processedImage = $this->watermarkProcessor->addWatermarkToBase64($base64Image, $imageGenerateRequest);
                    $currentData[] = [
                        'b64_json' => $processedImage,
                    ];
                } catch (Exception $e) {
                    $this->logger->error('Volcengine添加imagedata：base64水印handlefail', [
                        'error' => $e->getMessage(),
                    ]);
                    // 水印handlefail时useoriginaldata
                    $currentData[] = [
                        'b64_json' => $base64Image,
                    ];
                }
            }

            // 累计usageinfo（如果有的话）
            if (! empty($volcengineResult['usage']) && is_array($volcengineResult['usage'])) {
                $currentUsage->addGeneratedImages($volcengineResult['usage']['generated_images'] ?? 1);
                $currentUsage->completionTokens += $volcengineResult['usage']['output_tokens'] ?? 0;
                $currentUsage->totalTokens += $volcengineResult['usage']['total_tokens'] ?? 0;
            } else {
                // 如果没有usageinfo，default增加1张image
                $currentUsage->addGeneratedImages(1);
            }

            // updateresponseobject
            $response->setData($currentData);
            $response->setUsage($currentUsage);
        } finally {
            // ensurelock一定will被释放
            $this->unlockResponse($response, $lockOwner);
        }
    }

    private function validateImageToImageAspectRatio(array $referenceImages)
    {
        if (empty($referenceImages)) {
            $this->logger->error('火山图生图：参考image列表为null');
            ExceptionBuilder::throw(ImageGenerateErrorCode::MISSING_IMAGE_DATA, '缺少参考image');
        }

        // Get dimensions of the first reference image
        $referenceImageUrl = $referenceImages[0];
        $imageDimensions = $this->getImageDimensions($referenceImageUrl);

        if (! $imageDimensions) {
            $this->logger->warning('火山图生图：无法get参考图size，skip长宽比例校验', ['image_url' => $referenceImageUrl]);
            return; // Skip validation and continue execution
        }

        $width = $imageDimensions['width'];
        $height = $imageDimensions['height'];

        // Image-to-image aspect ratio limit: long side to short side ratio cannot exceed 3:1
        $maxAspectRatio = 3.0;
        $minDimension = min($width, $height);
        $maxDimension = max($width, $height);

        if ($minDimension <= 0) {
            $this->logger->warning('火山图生图：imagesizeinvalid，skip长宽比例校验', ['width' => $width, 'height' => $height]);
            return; // Skip validation and continue execution
        }

        $aspectRatio = $maxDimension / $minDimension;

        if ($aspectRatio > $maxAspectRatio) {
            $this->logger->error('火山图生图：长宽比例超出限制', [
                'width' => $width,
                'height' => $height,
                'aspect_ratio' => $aspectRatio,
                'max_allowed' => $maxAspectRatio,
                'image_url' => $referenceImageUrl,
            ]);
            ExceptionBuilder::throw(ImageGenerateErrorCode::INVALID_ASPECT_RATIO);
        }
    }

    /**
     * Get image dimension information.
     * @param string $imageUrl Image URL
     * @return null|array ['width' => int, 'height' => int] or null
     */
    private function getImageDimensions(string $imageUrl): ?array
    {
        try {
            // Get image information
            $imageUrl = SSRFUtil::getSafeUrl($imageUrl, replaceIp: false);
            $imageInfo = getimagesize($imageUrl);

            if ($imageInfo === false) {
                return null;
            }

            return [
                'width' => $imageInfo[0],
                'height' => $imageInfo[1],
            ];
        } catch (Exception $e) {
            $this->logger->warning('火山图生图：getimagesizefail', [
                'image_url' => $imageUrl,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * 为火山引擎originaldata添加水印.
     */
    private function processVolcengineRawDataWithWatermark(array $rawData, ImageGenerateRequest $imageGenerateRequest): array
    {
        foreach ($rawData as $index => &$result) {
            if (! isset($result['data'])) {
                continue;
            }

            $data = &$result['data'];

            try {
                // handle base64 formatimage
                if (! empty($data['binary_data_base64'])) {
                    foreach ($data['binary_data_base64'] as $i => &$base64Image) {
                        $base64Image = $this->watermarkProcessor->addWatermarkToBase64($base64Image, $imageGenerateRequest);
                    }
                    unset($base64Image);
                }

                // handle URL formatimage
                if (! empty($data['image_urls'])) {
                    foreach ($data['image_urls'] as $i => &$imageUrl) {
                        $imageUrl = $this->watermarkProcessor->addWatermarkToUrl($imageUrl, $imageGenerateRequest);
                    }
                    unset($imageUrl);
                }
            } catch (Exception $e) {
                // 水印handlefail时，recorderror但不影响imagereturn
                $this->logger->error('火山引擎image水印handlefail', [
                    'index' => $index,
                    'error' => $e->getMessage(),
                ]);
                // continuehandle下一张image，currentimage保持originalstatus
            }
        }

        return $rawData;
    }
}
