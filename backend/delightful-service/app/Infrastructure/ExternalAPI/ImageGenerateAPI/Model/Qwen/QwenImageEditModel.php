<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\ImageGenerateAPI\Model\Qwen;

use App\ErrorCode\ImageGenerateErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\AbstractImageGenerate;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\ImageGenerate;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\ImageGenerateModelType;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\ImageGenerateType;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Request\ImageGenerateRequest;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Request\QwenImageEditRequest;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Response\ImageGenerateResponse;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Response\ImageUsage;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Response\OpenAIFormatResponse;
use Exception;
use Hyperf\RateLimit\Annotation\RateLimit;
use Hyperf\Retry\Annotation\Retry;

class QwenImageEditModel extends AbstractImageGenerate
{
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
        // general meaningthousandquestionnotuseAK/SKauthentication,thismethodfornullimplement
    }

    public function setSK(string $sk)
    {
        // general meaningthousandquestionnotuseAK/SKauthentication,thismethodfornullimplement
    }

    public function setApiKey(string $apiKey)
    {
        $this->api->setApiKey($apiKey);
    }

    public function generateImageRawWithWatermark(ImageGenerateRequest $imageGenerateRequest): array
    {
        $rawData = $this->generateImageRaw($imageGenerateRequest);

        return $this->processQwenEditRawDataWithWatermark($rawData, $imageGenerateRequest);
    }

    /**
     * generategraphlikeandreturnOpenAIformatresponse - QwenEditversion.
     */
    public function generateImageOpenAIFormat(ImageGenerateRequest $imageGenerateRequest): OpenAIFormatResponse
    {
        // 1. in advancecreateresponseobject
        $response = new OpenAIFormatResponse([
            'created' => time(),
            'provider' => $this->getProviderName(),
            'data' => [],
        ]);

        // 2. parametervalidate
        if (! $imageGenerateRequest instanceof QwenImageEditRequest) {
            $this->logger->error('QwenEdit OpenAIformatgenerategraph:invalidrequesttype', ['class' => get_class($imageGenerateRequest)]);
            return $response; // returnnulldataresponse
        }

        // 3. synchandlegraphlikeedit(singlegraph)
        try {
            $result = $this->callSyncEditAPI($imageGenerateRequest);
            $this->validateQwenEditResponse($result);

            // success:settingimagedatatoresponseobject
            $this->addImageDataToResponseQwenEdit($response, $result, $imageGenerateRequest);
        } catch (Exception $e) {
            // fail:settingerrorinfotoresponseobject
            $response->setProviderErrorCode($e->getCode());
            $response->setProviderErrorMessage($e->getMessage());

            $this->logger->error('QwenEdit OpenAIformatgenerategraph:requestfail', [
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
            ]);
        }

        // 4. recordfinalresult
        $this->logger->info('QwenEdit OpenAIformatgenerategraph:handlecomplete', [
            'successimagecount' => count($response->getData()),
            'whetherhaveerror' => $response->hasError(),
            'errorcode' => $response->getProviderErrorCode(),
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

        // fromnativeresultmiddleextractimageURL - adaptnewresponseformat output.choices
        $imageUrls = [];
        foreach ($rawResults as $index => $result) {
            $output = $result['output'];
            if (! empty($output['choices'])) {
                foreach ($output['choices'] as $choice) {
                    if (! empty($choice['message']['content'])) {
                        foreach ($choice['message']['content'] as $content) {
                            if (isset($content['image']) && ! empty($content['image'])) {
                                $imageUrls[$index] = $content['image'];
                                break 2; // onlygetfirstimageURL
                            }
                        }
                    }
                }
            }
        }

        return new ImageGenerateResponse(ImageGenerateType::URL, $imageUrls);
    }

    /**
     * generategraphlike核corelogic,returnnativeresult - synccall.
     */
    private function generateImageRawInternal(ImageGenerateRequest $imageGenerateRequest): array
    {
        if (! $imageGenerateRequest instanceof QwenImageEditRequest) {
            $this->logger->error('general meaningthousandquestiongraphlikeedit:invalidrequesttype', ['class' => get_class($imageGenerateRequest)]);
            ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR);
        }

        // validationrequiredwantparameter
        $this->validateEditRequest($imageGenerateRequest);

        $this->logger->info('general meaningthousandquestiongraphlikeedit:startedit', [
            'prompt' => $imageGenerateRequest->getPrompt(),
            'image_count' => count($imageGenerateRequest->getImageUrls()),
        ]);

        // directlyhandlesinglerequest,graphlikeeditonlyhandleone张image
        try {
            $result = $this->callSyncEditAPI($imageGenerateRequest);
            $rawResults = [
                [
                    'success' => true,
                    'output' => $result['output'],
                ],
            ];
        } catch (Exception $e) {
            $this->logger->error('general meaningthousandquestiongraphlikeedit:fail', [
                'error' => $e->getMessage(),
            ]);
            ExceptionBuilder::throw(ImageGenerateErrorCode::from($e->getCode()) ?? ImageGenerateErrorCode::GENERAL_ERROR, $e->getMessage());
        }

        return $rawResults;
    }

    /**
     * validationgraphlikeeditrequestparameter.
     */
    private function validateEditRequest(QwenImageEditRequest $request): void
    {
        // checkwhetherhaveinputgraphlike
        if (empty($request->getImageUrls())) {
            $this->logger->error('general meaningthousandquestiongraphlikeedit:missinginputgraphlike');
            ExceptionBuilder::throw(ImageGenerateErrorCode::MISSING_IMAGE_DATA, 'image_generate.reference_images_required');
        }
    }

    #[Retry(
        maxAttempts: self::GENERATE_RETRY_COUNT,
        base: self::GENERATE_RETRY_TIME
    )]
    #[RateLimit(create: 4, consume: 1, capacity: 0, key: ImageGenerate::IMAGE_GENERATE_KEY_PREFIX . ImageGenerate::IMAGE_GENERATE_SUBMIT_KEY_PREFIX . ImageGenerateModelType::QwenImageEdit->value, waitTimeout: 60)]
    private function callSyncEditAPI(QwenImageEditRequest $request): array
    {
        try {
            $params = [
                'prompt' => $request->getPrompt(),
                'image_urls' => $request->getImageUrls(),
                'model' => $request->getModel(),
            ];

            $response = $this->api->submitEditTask($params);

            // checkresponseformat - adaptnewsyncresponseformat
            if (! isset($response['output']['choices'])) {
                $errorMsg = $response['message'] ?? 'unknownerror';
                $this->logger->warning('general meaningthousandquestiongraphlikeedit:responseformaterror', ['response' => $response]);
                ExceptionBuilder::throw(ImageGenerateErrorCode::RESPONSE_FORMAT_ERROR, $errorMsg);
            }

            // checkwhetherhavegraphlikedata
            $choices = $response['output']['choices'];
            if (empty($choices)) {
                $this->logger->error('general meaningthousandquestiongraphlikeedit:responsemiddlemissinggraphlikedata', ['response' => $response]);
                ExceptionBuilder::throw(ImageGenerateErrorCode::MISSING_IMAGE_DATA);
            }

            $this->logger->info('general meaningthousandquestiongraphlikeedit:callsuccess', [
                'choices_count' => count($choices),
            ]);

            return $response;
        } catch (Exception $e) {
            $this->logger->error('general meaningthousandquestiongraphlikeedit:callexception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR, $e->getMessage());
        }
    }

    /**
     * forgeneral meaningthousandquestioneditmodetypeoriginaldataaddwatermark - adaptnewchoicesformat.
     */
    private function processQwenEditRawDataWithWatermark(array $rawData, ImageGenerateRequest $imageGenerateRequest): array
    {
        foreach ($rawData as $index => &$result) {
            if (! isset($result['output']['choices']) || ! is_array($result['output']['choices'])) {
                continue;
            }

            foreach ($result['output']['choices'] as $choiceIndex => &$choice) {
                if (! isset($choice['message']['content']) || ! is_array($choice['message']['content'])) {
                    continue;
                }

                foreach ($choice['message']['content'] as $contentIndex => &$content) {
                    if (! isset($content['image'])) {
                        continue;
                    }

                    try {
                        // handleURLformatimage
                        $content['image'] = $this->watermarkProcessor->addWatermarkToUrl($content['image'], $imageGenerateRequest);
                    } catch (Exception $e) {
                        // watermarkhandlefailo clock,recorderrorbutnotimpactimagereturn
                        $this->logger->error('general meaningthousandquestiongraphlikeeditwatermarkhandlefail', [
                            'index' => $index,
                            'choiceIndex' => $choiceIndex,
                            'contentIndex' => $contentIndex,
                            'error' => $e->getMessage(),
                        ]);
                        // continuehandledownone张image,currentimagemaintainoriginalstatus
                    }
                }
            }
        }

        return $rawData;
    }

    /**
     * validategeneral meaningthousandquestioneditAPIresponsedataformat.
     */
    private function validateQwenEditResponse(array $result): void
    {
        if (empty($result['output']['choices']) || ! is_array($result['output']['choices'])) {
            throw new Exception('general meaningthousandquestioneditresponsedataformaterror:missingchoicesdata');
        }

        $hasValidImage = false;
        foreach ($result['output']['choices'] as $choice) {
            if (! empty($choice['message']['content']) && is_array($choice['message']['content'])) {
                foreach ($choice['message']['content'] as $content) {
                    if (! empty($content['image'])) {
                        $hasValidImage = true;
                        break 2;
                    }
                }
            }
        }

        if (! $hasValidImage) {
            throw new Exception('general meaningthousandquestioneditresponsedataformaterror:missinggraphlikedata');
        }
    }

    /**
     * willgeneral meaningthousandquestioneditimagedataaddtoOpenAIresponseobjectmiddle.
     */
    private function addImageDataToResponseQwenEdit(
        OpenAIFormatResponse $response,
        array $qwenResult,
        ImageGenerateRequest $imageGenerateRequest
    ): void {
        // fromgeneral meaningthousandquestioneditresponsemiddleextractdata
        if (empty($qwenResult['output']['choices']) || ! is_array($qwenResult['output']['choices'])) {
            return;
        }

        $currentData = $response->getData();
        $currentUsage = $response->getUsage() ?? new ImageUsage();

        foreach ($qwenResult['output']['choices'] as $choice) {
            if (empty($choice['message']['content']) || ! is_array($choice['message']['content'])) {
                continue;
            }

            foreach ($choice['message']['content'] as $content) {
                if (empty($content['image'])) {
                    continue;
                }

                // handlewatermark
                $processedUrl = $content['image'];
                try {
                    $processedUrl = $this->watermarkProcessor->addWatermarkToUrl($content['image'], $imageGenerateRequest);
                } catch (Exception $e) {
                    $this->logger->error('QwenEditaddimagedata:watermarkhandlefail', [
                        'error' => $e->getMessage(),
                        'url' => $content['image'],
                    ]);
                    // watermarkhandlefailo clockuseoriginalURL
                }

                $currentData[] = [
                    'url' => $processedUrl,
                ];
            }
        }

        // accumulatedusageinfo - general meaningthousandquestioneditusageformatadapt
        if (! empty($qwenResult['usage']) && is_array($qwenResult['usage'])) {
            $currentUsage->addGeneratedImages(1); // editgenerate1张image
            $currentUsage->promptTokens += $qwenResult['usage']['input_tokens'] ?? 0;
            $currentUsage->completionTokens += $qwenResult['usage']['output_tokens'] ?? 0;
            $currentUsage->totalTokens += $qwenResult['usage']['total_tokens'] ?? 0;
        }

        // updateresponseobject
        $response->setData($currentData);
        $response->setUsage($currentUsage);
    }
}
