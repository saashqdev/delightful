<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\ImageGenerateAPI\Model\Volcengine;

use App\ErrorCode\ImageGenerateErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\AbstractImageGenerate;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\ImageGenerateType;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Request\ImageGenerateRequest;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Request\VolcengineModelRequest;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Response\ImageGenerateResponse;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Response\ImageUsage;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Response\OpenAIFormatResponse;
use Exception;
use Hyperf\Codec\Json;

class VolcengineImageGenerateV3Model extends AbstractImageGenerate
{
    // most大round询retrycount
    private const MAX_RETRY_COUNT = 30;

    // round询retrybetween隔（second）
    private const RETRY_INTERVAL = 2;

    private VolcengineAPI $api;

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

        return $this->processVolcengineV3RawDataWithWatermark($rawData, $imageGenerateRequest);
    }

    /**
     * generategraphlikeandreturnOpenAIformatresponse - V3version.
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
            $this->logger->error('VolcengineV3 OpenAIformat生graph：invalidrequesttype', ['class' => get_class($imageGenerateRequest)]);
            return $response; // returnnulldataresponse
        }

        // 3. synchandleimagegenerate
        $count = $imageGenerateRequest->getGenerateNum();

        for ($i = 0; $i < $count; ++$i) {
            try {
                // submittaskandround询result
                $taskId = $this->submitAsyncTask($imageGenerateRequest);
                $result = $this->pollTaskResult($taskId, $imageGenerateRequest);

                $this->validateVolcengineV3Response($result);

                // success：settingimagedatatoresponseobject
                $this->addImageDataToResponseV3($response, $result, $imageGenerateRequest);
            } catch (Exception $e) {
                // fail：settingerrorinfotoresponseobject（只settingfirsterror）
                if (! $response->hasError()) {
                    $response->setProviderErrorCode($e->getCode());
                    $response->setProviderErrorMessage($e->getMessage());
                }

                $this->logger->error('VolcengineV3 OpenAIformat生graph：singlerequestfail', [
                    'error_code' => $e->getCode(),
                    'error_message' => $e->getMessage(),
                    'index' => $i,
                ]);
            }
        }

        // 4. recordfinalresult
        $this->logger->info('VolcengineV3 OpenAIformat生graph：handlecomplete', [
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

    protected function generateImageInternal(ImageGenerateRequest $imageGenerateRequest): ImageGenerateResponse
    {
        $rawResults = $this->generateImageRawInternal($imageGenerateRequest);

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

        // 按索引sortresult
        ksort($imageUrls);
        $imageUrls = array_values($imageUrls);

        $this->logger->info('火山文生graph：generateend', [
            'generateimage' => $imageUrls,
            'imagequantity' => count($rawResults),
        ]);

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
        $count = $imageGenerateRequest->getGenerateNum();

        $this->logger->info('火山文生graph：start生graph', [
            'prompt' => $imageGenerateRequest->getPrompt(),
            'negativePrompt' => $imageGenerateRequest->getNegativePrompt(),
            'width' => $imageGenerateRequest->getWidth(),
            'height' => $imageGenerateRequest->getHeight(),
            'req_key' => $imageGenerateRequest->getModel(),
        ]);

        // usesyncmethodhandle
        $rawResults = [];
        $errors = [];

        for ($i = 0; $i < $count; ++$i) {
            try {
                // submittask（带retry）
                $taskId = $this->submitAsyncTask($imageGenerateRequest);
                // round询result（带retry）
                $result = $this->pollTaskResult($taskId, $imageGenerateRequest);

                $rawResults[] = [
                    'success' => true,
                    'data' => $result['data'],
                    'index' => $i,
                ];
            } catch (Exception $e) {
                $this->logger->error('火山文生graph：fail', [
                    'error' => $e->getMessage(),
                    'index' => $i,
                ]);
                $errors[] = [
                    'code' => $e->getCode(),
                    'message' => $e->getMessage(),
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

        // 按索引sortresult
        ksort($rawResults);
        return array_values($rawResults);
    }

    private function submitAsyncTask(VolcengineModelRequest $request): string
    {
        $prompt = $request->getPrompt();
        $width = (int) $request->getWidth();
        $height = (int) $request->getHeight();

        try {
            $body = [
                'return_url' => true,
                'prompt' => $prompt,
                'width' => $width,
                'height' => $height,
                'req_key' => $request->getModel(),
            ];

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

    private function pollTaskResult(string $taskId, VolcengineModelRequest $imageGenerateRequest): array
    {
        $reqKey = $imageGenerateRequest->getModel();
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
     * validate火山engineV3 APIresponsedataformat.
     */
    private function validateVolcengineV3Response(array $result): void
    {
        if (empty($result['data']) || ! is_array($result['data'])) {
            throw new Exception('火山engineV3responsedataformaterror：缺少datafield');
        }

        $data = $result['data'];
        // 优先check image_urls，然backcheck binary_data_base64
        $hasValidImageData = (! empty($data['image_urls']) && ! empty($data['image_urls'][0]))
                            || (! empty($data['binary_data_base64']) && ! empty($data['binary_data_base64'][0]));

        if (! $hasValidImageData) {
            throw new Exception('火山engineV3responsedataformaterror：缺少imagedata');
        }
    }

    /**
     * will火山engineV3imagedataaddtoOpenAIresponseobjectmiddle.
     */
    private function addImageDataToResponseV3(
        OpenAIFormatResponse $response,
        array $volcengineResult,
        ImageGenerateRequest $imageGenerateRequest
    ): void {
        // uselockensureandhairsecurity（althoughV3usesync，but保持one致property）
        $lockOwner = $this->lockResponse($response);
        try {
            // from火山engineV3responsemiddleextractdata
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
                    $this->logger->error('VolcengineV3addimagedata：URLwatermarkhandlefail', [
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
                    $this->logger->error('VolcengineV3addimagedata：base64watermarkhandlefail', [
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

    /**
     * for火山engineV3originaldataaddwatermark.
     */
    private function processVolcengineV3RawDataWithWatermark(array $rawData, ImageGenerateRequest $imageGenerateRequest): array
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
                $this->logger->error('火山engineV3imagewatermarkhandlefail', [
                    'index' => $index,
                    'error' => $e->getMessage(),
                ]);
                // continuehandledownone张image，currentimage保持originalstatus
            }
        }

        return $rawData;
    }
}
