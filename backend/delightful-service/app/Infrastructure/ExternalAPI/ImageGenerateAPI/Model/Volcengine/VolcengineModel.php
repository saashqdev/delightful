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
    // most大round询retrycount
    private const MAX_RETRY_COUNT = 30;

    // round询retrybetween隔（second）
    private const RETRY_INTERVAL = 2;

    // graph生graphquantitylimit
    private const IMAGE_TO_IMAGE_IMAGE_COUNT = 1;

    private VolcengineAPI $api;

    private string $textToImageModelVersion = 'general_v2.1_L';

    private string $textToImageReqScheduleConf = 'general_v20_9B_pe';

    // graph生graphconfiguration
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
     * generategraphlikeandreturnOpenAIformatresponse - V2onebody化version.
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
            $this->logger->error('Volcengine OpenAIformat生graph：invalidrequesttype', ['class' => get_class($imageGenerateRequest)]);
            return $response; // returnnulldataresponse
        }

        // 3. 判断isgraph生graphalsois文生graph
        $isImageToImage = ! empty($imageGenerateRequest->getReferenceImage());
        $count = $isImageToImage ? self::IMAGE_TO_IMAGE_IMAGE_COUNT : $imageGenerateRequest->getGenerateNum();

        // 4. andhairhandle - 直接操asresponseobject
        $parallel = new Parallel();
        $fromCoroutineId = Coroutine::id();

        for ($i = 0; $i < $count; ++$i) {
            $parallel->add(function () use ($imageGenerateRequest, $isImageToImage, $response, $fromCoroutineId) {
                CoContext::copy($fromCoroutineId);
                try {
                    $result = $this->requestImageGenerationV2($imageGenerateRequest, $isImageToImage);
                    $this->validateVolcengineResponse($result);

                    // success：settingimagedatatoresponseobject
                    $this->addImageDataToResponse($response, $result, $imageGenerateRequest);
                } catch (Exception $e) {
                    // fail：settingerrorinfotoresponseobject（只settingfirsterror）
                    if (! $response->hasError()) {
                        $response->setProviderErrorCode($e->getCode());
                        $response->setProviderErrorMessage($e->getMessage());
                    }

                    $this->logger->error('Volcengine OpenAIformat生graph：singlerequestfail', [
                        'error_code' => $e->getCode(),
                        'error_message' => $e->getMessage(),
                    ]);
                }
            });
        }

        $parallel->wait();

        // 5. recordfinalresult
        $this->logger->info('Volcengine OpenAIformat生graph：andhairhandlecomplete', [
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
        return 'volcengine';
    }

    /**
     * generategraphlike核core逻辑，return ImageGenerateResponse.
     */
    protected function generateImageInternal(ImageGenerateRequest $imageGenerateRequest): ImageGenerateResponse
    {
        if (! $imageGenerateRequest instanceof VolcengineModelRequest) {
            $this->logger->error('火山文生graph：invalidrequesttype', ['class' => get_class($imageGenerateRequest)]);
            ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR);
        }

        // 判断isgraph生graphalsois文生graph
        $isImageToImage = ! empty($imageGenerateRequest->getReferenceImage());
        $count = $isImageToImage ? self::IMAGE_TO_IMAGE_IMAGE_COUNT : $imageGenerateRequest->getGenerateNum();

        $this->logger->info('火山文生graph：start生graph', [
            'prompt' => $imageGenerateRequest->getPrompt(),
            'negativePrompt' => $imageGenerateRequest->getNegativePrompt(),
            'width' => $imageGenerateRequest->getWidth(),
            'height' => $imageGenerateRequest->getHeight(),
            'req_key' => $imageGenerateRequest->getModel(),
            'textToImageModelVersion' => $this->textToImageModelVersion,
            'textToImageReqScheduleConf' => $this->textToImageReqScheduleConf,
        ]);

        // use Parallel andlinehandle
        $parallel = new Parallel();
        for ($i = 0; $i < $count; ++$i) {
            $fromCoroutineId = Coroutine::id();
            $parallel->add(function () use ($imageGenerateRequest, $isImageToImage, $i, $fromCoroutineId) {
                CoContext::copy($fromCoroutineId);
                try {
                    // submittask（带retry）
                    $taskId = $this->submitAsyncTask($imageGenerateRequest, $isImageToImage);
                    // round询result（带retry）
                    $result = $this->pollTaskResult($taskId, $imageGenerateRequest);

                    return [
                        'success' => true,
                        'data' => $result['data'],
                        'index' => $i,
                    ];
                } catch (Exception $e) {
                    $this->logger->error('火山文生graph：fail', [
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

            $this->logger->error('火山文生graph：所haveimagegenerate均fail', ['errors' => $errors]);
            ExceptionBuilder::throw($finalErrorCode, $finalErrorMsg);
        }

        // 按indexsortresult
        ksort($rawResults);
        $rawResults = array_values($rawResults);

        $this->logger->info('火山文生graph：generateend', [
            'imagequantity' => $count,
        ]);

        // fromnativeresultmiddleextractimageURL
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
     * generategraphlike核core逻辑，returnnativeresult.
     */
    private function generateImageRawInternal(ImageGenerateRequest $imageGenerateRequest): array
    {
        if (! $imageGenerateRequest instanceof VolcengineModelRequest) {
            $this->logger->error('火山文生graph：invalidrequesttype', ['class' => get_class($imageGenerateRequest)]);
            ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR);
        }

        // 判断isgraph生graphalsois文生graph
        $isImageToImage = ! empty($imageGenerateRequest->getReferenceImage());
        $count = $isImageToImage ? self::IMAGE_TO_IMAGE_IMAGE_COUNT : $imageGenerateRequest->getGenerateNum();

        $this->logger->info('火山文生graph：start生graph', [
            'prompt' => $imageGenerateRequest->getPrompt(),
            'negativePrompt' => $imageGenerateRequest->getNegativePrompt(),
            'width' => $imageGenerateRequest->getWidth(),
            'height' => $imageGenerateRequest->getHeight(),
            'req_key' => $imageGenerateRequest->getModel(),
            'textToImageModelVersion' => $this->textToImageModelVersion,
            'textToImageReqScheduleConf' => $this->textToImageReqScheduleConf,
        ]);

        // use Parallel andlinehandle
        $parallel = new Parallel();
        for ($i = 0; $i < $count; ++$i) {
            $fromCoroutineId = Coroutine::id();
            $parallel->add(function () use ($imageGenerateRequest, $isImageToImage, $i, $fromCoroutineId) {
                CoContext::copy($fromCoroutineId);
                try {
                    // submittask（带retry）
                    $taskId = $this->submitAsyncTask($imageGenerateRequest, $isImageToImage);
                    // round询result（带retry）
                    $result = $this->pollTaskResult($taskId, $imageGenerateRequest);

                    return [
                        'success' => true,
                        'data' => $result['data'],
                        'index' => $i,
                    ];
                } catch (Exception $e) {
                    $this->logger->error('火山文生graph：fail', [
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

        // checkwhetherhavesuccessimagegenerate
        if (empty($rawResults)) {
            $this->logger->error('火山文生graph：所haveimagegenerate均fail', ['errors' => $errors]);
            ExceptionBuilder::throw($finalErrorCode, $finalErrorMsg);
        }

        // 按indexsortresult
        ksort($rawResults);
        $rawResults = array_values($rawResults);

        $this->logger->info('火山文生graph：generateend', [
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
                // graph生graphconfiguration
                if (empty($request->getReferenceImage())) {
                    $this->logger->error('火山graph生graph：缺少源image');
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
                $this->logger->warning('火山文生graph：responseformaterror', ['response' => $response]);
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

                $this->logger->warning('火山文生graph：tasksubmitfail', [
                    'code' => $response['code'],
                    'message' => $response['message'] ?? '',
                ]);

                ExceptionBuilder::throw($errorCode, $errorMsg);
            }

            if (! isset($response['data']['task_id'])) {
                $this->logger->warning('火山文生graph：responsemiddle缺少taskID', ['response' => $response]);
                ExceptionBuilder::throw(ImageGenerateErrorCode::RESPONSE_FORMAT_ERROR);
            }

            $taskId = $response['data']['task_id'];

            $this->logger->info('火山文生graph：submittasksuccess', [
                'taskId' => $taskId,
                'isImageToImage' => $isImageToImage,
            ]);

            return $taskId;
        } catch (Exception $e) {
            $this->logger->error('火山文生graph：tasksubmitexception', [
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
                    $this->logger->warning('火山文生graph：querytaskresponseformaterror', ['response' => $response]);
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

                    $this->logger->warning('火山文生graph：querytaskfail', [
                        'code' => $response['code'],
                        'message' => $response['message'] ?? '',
                    ]);

                    ExceptionBuilder::throw($errorCode, $errorMsg);
                }

                if (! isset($response['data']) || ! isset($response['data']['status'])) {
                    $this->logger->warning('火山文生graph：responseformaterror', ['response' => $response]);
                    ExceptionBuilder::throw(ImageGenerateErrorCode::RESPONSE_FORMAT_ERROR);
                }

                $data = $response['data'];
                $status = $data['status'];

                $this->logger->info('火山文生graph：taskstatus', [
                    'taskId' => $taskId,
                    'status' => $status,
                ]);

                switch ($status) {
                    case 'done':
                        if (! empty($data['binary_data_base64']) || ! empty($data['image_urls'])) {
                            return $response;
                        }
                        $this->logger->error('火山文生graph：taskcompletebut缺少imagedata', ['response' => $response]);
                        ExceptionBuilder::throw(ImageGenerateErrorCode::MISSING_IMAGE_DATA);
                        // no break
                    case 'in_queue':
                    case 'generating':
                        break;
                    case 'not_found':
                        $this->logger->error('火山文生graph：task未找toor已expire', ['taskId' => $taskId]);
                        ExceptionBuilder::throw(ImageGenerateErrorCode::TASK_TIMEOUT_WITH_REASON);
                        // no break
                    default:
                        $this->logger->error('火山文生graph：unknowntaskstatus', ['status' => $status, 'response' => $response]);
                        ExceptionBuilder::throw(ImageGenerateErrorCode::TASK_TIMEOUT_WITH_REASON);
                }

                ++$retryCount;
                sleep(self::RETRY_INTERVAL);
            } catch (Exception $e) {
                $this->logger->error('火山文生graph：querytaskexception', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'taskId' => $taskId,
                ]);

                ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR);
            }
        }

        $this->logger->error('火山文生graph：taskquerytimeout', ['taskId' => $taskId]);
        ExceptionBuilder::throw(ImageGenerateErrorCode::TASK_TIMEOUT);
    }

    /**
     * V2version：group合submittaskandround询result，useatOpenAIformatgenerate.
     */
    private function requestImageGenerationV2(VolcengineModelRequest $imageGenerateRequest, bool $isImageToImage): array
    {
        // submittask
        $taskId = $this->submitAsyncTask($imageGenerateRequest, $isImageToImage);

        // round询result
        return $this->pollTaskResult($taskId, $imageGenerateRequest);
    }

    /**
     * validate火山engineAPIresponsedataformat.
     */
    private function validateVolcengineResponse(array $result): void
    {
        if (empty($result['data']) || ! is_array($result['data'])) {
            throw new Exception('火山engineresponsedataformaterror：缺少datafield');
        }

        $data = $result['data'];
        // 优先check image_urls，然backcheck binary_data_base64
        $hasValidImageData = (! empty($data['image_urls']) && ! empty($data['image_urls'][0]))
                            || (! empty($data['binary_data_base64']) && ! empty($data['binary_data_base64'][0]));

        if (! $hasValidImageData) {
            throw new Exception('火山engineresponsedataformaterror：缺少imagedata');
        }
    }

    /**
     * will火山engineimagedataaddtoOpenAIresponseobjectmiddle.
     */
    private function addImageDataToResponse(
        OpenAIFormatResponse $response,
        array $volcengineResult,
        ImageGenerateRequest $imageGenerateRequest
    ): void {
        // useRedislockensureandhairsecurity
        $lockOwner = $this->lockResponse($response);
        try {
            // from火山engineresponsemiddleextractdata
            if (empty($volcengineResult['data']) || ! is_array($volcengineResult['data'])) {
                return;
            }

            $data = $volcengineResult['data'];
            $currentData = $response->getData();
            $currentUsage = $response->getUsage() ?? new ImageUsage();

            // 优先handle URL formatimage，参考现have逻辑只取firstimage
            if (! empty($data['image_urls']) && ! empty($data['image_urls'][0])) {
                $imageUrl = $data['image_urls'][0];
                try {
                    // handlewatermark
                    $processedUrl = $this->watermarkProcessor->addWatermarkToUrl($imageUrl, $imageGenerateRequest);
                    $currentData[] = [
                        'url' => $processedUrl,
                    ];
                } catch (Exception $e) {
                    $this->logger->error('Volcengineaddimagedata：URLwatermarkhandlefail', [
                        'error' => $e->getMessage(),
                        'url' => $imageUrl,
                    ]);
                    // watermarkhandlefailo clockuseoriginalURL
                    $currentData[] = [
                        'url' => $imageUrl,
                    ];
                }
            } elseif (! empty($data['binary_data_base64']) && ! empty($data['binary_data_base64'][0])) {
                // 备选：handle base64 formatimage，只取firstimage
                $base64Image = $data['binary_data_base64'][0];
                try {
                    // handlewatermark
                    $processedImage = $this->watermarkProcessor->addWatermarkToBase64($base64Image, $imageGenerateRequest);
                    $currentData[] = [
                        'b64_json' => $processedImage,
                    ];
                } catch (Exception $e) {
                    $this->logger->error('Volcengineaddimagedata：base64watermarkhandlefail', [
                        'error' => $e->getMessage(),
                    ]);
                    // watermarkhandlefailo clockuseoriginaldata
                    $currentData[] = [
                        'b64_json' => $base64Image,
                    ];
                }
            }

            // 累计usageinfo（ifhave话）
            if (! empty($volcengineResult['usage']) && is_array($volcengineResult['usage'])) {
                $currentUsage->addGeneratedImages($volcengineResult['usage']['generated_images'] ?? 1);
                $currentUsage->completionTokens += $volcengineResult['usage']['output_tokens'] ?? 0;
                $currentUsage->totalTokens += $volcengineResult['usage']['total_tokens'] ?? 0;
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

    private function validateImageToImageAspectRatio(array $referenceImages)
    {
        if (empty($referenceImages)) {
            $this->logger->error('火山graph生graph：参考imagecolumn表fornull');
            ExceptionBuilder::throw(ImageGenerateErrorCode::MISSING_IMAGE_DATA, '缺少参考image');
        }

        // Get dimensions of the first reference image
        $referenceImageUrl = $referenceImages[0];
        $imageDimensions = $this->getImageDimensions($referenceImageUrl);

        if (! $imageDimensions) {
            $this->logger->warning('火山graph生graph：no法get参考graphsize，skip长宽ratio例validation', ['image_url' => $referenceImageUrl]);
            return; // Skip validation and continue execution
        }

        $width = $imageDimensions['width'];
        $height = $imageDimensions['height'];

        // Image-to-image aspect ratio limit: long side to short side ratio cannot exceed 3:1
        $maxAspectRatio = 3.0;
        $minDimension = min($width, $height);
        $maxDimension = max($width, $height);

        if ($minDimension <= 0) {
            $this->logger->warning('火山graph生graph：imagesizeinvalid，skip长宽ratio例validation', ['width' => $width, 'height' => $height]);
            return; // Skip validation and continue execution
        }

        $aspectRatio = $maxDimension / $minDimension;

        if ($aspectRatio > $maxAspectRatio) {
            $this->logger->error('火山graph生graph：长宽ratio例超outlimit', [
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
            $this->logger->warning('火山graph生graph：getimagesizefail', [
                'image_url' => $imageUrl,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * for火山engineoriginaldataaddwatermark.
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
                // watermarkhandlefailo clock，recorderrorbutnot影响imagereturn
                $this->logger->error('火山engineimagewatermarkhandlefail', [
                    'index' => $index,
                    'error' => $e->getMessage(),
                ]);
                // continuehandledownone张image，currentimage保持originalstatus
            }
        }

        return $rawData;
    }
}
