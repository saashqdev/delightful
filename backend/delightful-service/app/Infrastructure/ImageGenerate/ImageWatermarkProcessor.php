<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ImageGenerate;

use App\Domain\File\Repository\Persistence\CloudFileRepository;
use App\Domain\File\Service\FileDomainService;
use App\Domain\ImageGenerate\Contract\FontProviderInterface;
use App\Domain\ImageGenerate\Contract\ImageEnhancementProcessorInterface;
use App\Domain\ImageGenerate\ValueObject\WatermarkConfig;
use App\Infrastructure\Core\ValueObject\StorageBucketType;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Request\ImageGenerateRequest;
use Delightful\CloudFile\Kernel\Struct\UploadFile;
use Exception;
use Hyperf\Codec\Json;
use Hyperf\Di\Annotation\Inject;
use Psr\Log\LoggerInterface;

/**
 * imagewatermarkhandle器
 * 统onehandleeachtypeformatimagewatermarkadd.
 */
class ImageWatermarkProcessor
{
    public const WATERMARK_TEXT = '麦吉 AI generate';

    #[Inject]
    protected LoggerInterface $logger;

    #[Inject]
    protected FontProviderInterface $fontProvider;

    #[Inject]
    protected ImageEnhancementProcessorInterface $imageEnhancementProcessor;

    /**
     * forbase64formatimageaddwatermark.
     */
    public function addWatermarkToBase64(string $base64Image, ImageGenerateRequest $imageGenerateRequest): string
    {
        // detectoriginalformat
        $originalFormat = $this->extractBase64Format($base64Image);

        // decodingbase64image
        $imageData = $this->decodeBase64Image($base64Image);

        // double重detectensureformataccurate
        $detectedFormat = $this->detectImageFormat($imageData);
        $targetFormat = $originalFormat !== 'jpeg' ? $originalFormat : $detectedFormat;

        // use统onewatermarkhandlemethod
        if ($imageGenerateRequest->isAddWatermark()) {
            $imageData = $this->addWaterMarkHandler($imageData, $imageGenerateRequest, $targetFormat);
        }

        // immediatelyaddXMP隐typewatermark
        $implicitWatermark = $imageGenerateRequest->getImplicitWatermark();
        $xmpWatermarkedData = $this->imageEnhancementProcessor->enhanceImageData(
            $imageData,
            $implicitWatermark
        );

        // 重newencodingforbase64andupload
        $outputPrefix = $this->generateBase64Prefix($targetFormat);
        return $this->processBase64Images($outputPrefix . base64_encode($xmpWatermarkedData), $imageGenerateRequest);
    }

    /**
     * forURLformatimageaddwatermark
     * optional择returnformat:URL or base64.
     */
    public function addWatermarkToUrl(string $imageUrl, ImageGenerateRequest $imageGenerateRequest): string
    {
        $imageData = $this->downloadImage($imageUrl);

        if ($imageGenerateRequest->isAddWatermark()) {
            $imageData = $this->addWaterMarkHandler($imageData, $imageGenerateRequest);
        }

        // immediatelyaddXMP隐typewatermark
        $implicitWatermark = $imageGenerateRequest->getImplicitWatermark();
        $xmpWatermarkedData = $this->imageEnhancementProcessor->enhanceImageData(
            $imageData,
            $implicitWatermark
        );

        // according toactualoutputformatgeneratecorrectbase64front缀
        $outputPrefix = $this->generateBase64Prefix($imageData);
        return $this->processBase64Images($outputPrefix . base64_encode($xmpWatermarkedData), $imageGenerateRequest);
    }

    public function extractWatermarkInfo(string $imageUrl): ?array
    {
        try {
            $imageData = $this->downloadImage($imageUrl);
            return $this->imageEnhancementProcessor->extractEnhancementFromImageData($imageData);
        } catch (Exception $e) {
            $this->logger->error('Failed to extract watermark info', [
                'error' => $e->getMessage(),
                'url' => $imageUrl,
            ]);
            return null;
        }
    }

    protected function addWaterMarkHandler(string $imageData, ImageGenerateRequest $imageGenerateRequest, ?string $format = null): string
    {
        // detectimageformat,priorityusepass informat
        $detectedFormat = $format ?? $this->detectImageFormat($imageData);

        $image = imagecreatefromstring($imageData);
        if ($image === false) {
            throw new Exception('no法parseURLimagedata: ');
        }
        $watermarkConfig = $imageGenerateRequest->getWatermarkConfig();
        // addvisualwatermark
        $watermarkedImage = $this->addWatermarkToImageResource($image, $watermarkConfig);

        // usedetecttoformatconductno损output
        ob_start();
        $this->outputImage($watermarkedImage, $detectedFormat);
        $watermarkedData = ob_get_contents();
        ob_end_clean();

        // cleanupinside存
        imagedestroy($image);
        imagedestroy($watermarkedImage);
        return $watermarkedData;
    }

    /**
     * forimageresourceaddwatermark.
     * @param mixed $image
     */
    private function addWatermarkToImageResource($image, WatermarkConfig $config)
    {
        $width = imagesx($image);
        $height = imagesy($image);

        // createnewimageresourcebyavoidmodify原graph
        $watermarkedImage = imagecreatetruecolor($width, $height);
        imagecopy($watermarkedImage, $image, 0, 0, 0, 0, $width, $height);

        // addtextwatermark
        $this->addTextWatermark($watermarkedImage, $config, $width, $height);

        return $watermarkedImage;
    }

    /**
     * addtextwatermark.
     * @param mixed $image
     */
    private function addTextWatermark($image, WatermarkConfig $config, int $width, int $height): void
    {
        $text = $config->getLogotextContent();
        $fontSize = $this->calculateFontSize($width, $height);
        $fontColor = $this->createTransparentColor($image, $config->getOpacity());

        // calculatewatermarkposition
        [$x, $y] = $this->calculateWatermarkPosition($width, $height, $text, $fontSize, $config->getPosition());

        // priorityuseTTF字body,especiallyistoatmiddle文text
        $fontFile = $this->fontProvider->getFontPath();
        if ($fontFile !== null && ($this->fontProvider->containsChinese($text) || $this->fontProvider->supportsTTF())) {
            // useTTF字bodyrender,supportmiddle文
            // TTF字bodysizeneedadjust,usuallyratioinside置字bodysmallonethese
            $ttfFontSize = max(8, (int) ($fontSize * 0.8));

            // correctcalculateTTF字body基lineposition
            if (function_exists('imagettfbbox')) {
                // directlyusepass inYcoordinateasfor基lineposition
                $ttfY = $y;
            } else {
                // ifno法getside界框,directlyusepass inYcoordinate
                $ttfY = $y;
            }

            imagettftext($image, $ttfFontSize, 0, $x, $ttfY, $fontColor, $fontFile, $text);
        } else {
            // 降leveluseinside置字body(onlysupportASCIIcharacter)
            // inside置字bodyYcoordinateistexttop部,needfrom基linepositionconvert
            $builtinY = $y - (int) ($fontSize * 0.8); // from基linepositionconvertfortop部position
            imagestring($image, 5, $x, $builtinY, $text, $fontColor);

            // iftextcontainmiddle文butnothaveTTF字body,recordwarning
            if ($this->fontProvider->containsChinese($text)) {
                $this->logger->warning('Chinese text detected but TTF font not available, may display incorrectly');
            }
        }
    }

    /**
     * calculate字bodysize.
     */
    private function calculateFontSize(int $width, int $height): int
    {
        // according toimagesize动stateadjust字bodysize
        $size = min($width, $height) / 20;
        return max(12, min(36, (int) $size));
    }

    /**
     * create透明color.
     * @param mixed $image
     */
    private function createTransparentColor($image, float $opacity): int
    {
        // create白colorhalf透明watermark
        $alpha = (int) ((1 - $opacity) * 127);
        return imagecolorallocatealpha($image, 255, 255, 255, $alpha);
    }

    /**
     * calculatewatermarkposition.
     */
    private function calculateWatermarkPosition(int $width, int $height, string $text, int $fontSize, int $position): array
    {
        // moreprecisetextwidth估算
        $fontFile = $this->fontProvider->getFontPath();
        if ($fontFile !== null && $this->fontProvider->supportsTTF() && function_exists('imagettfbbox')) {
            // useTTF字bodycalculateactualtextside界框
            $ttfFontSize = max(8, (int) ($fontSize * 0.8));
            $bbox = imagettfbbox($ttfFontSize, 0, $fontFile, $text);
            $textWidth = (int) (($bbox[4] - $bbox[0]) * 1.2);  // increase20%securityside距
            $textHeight = (int) abs($bbox[1] - $bbox[7]); // use绝tovalueensureheightforjust

            // TTF字bodydown降部minute(descender)
            $descender = (int) abs($bbox[1]); // 基linebydown部minute
            $ascender = (int) abs($bbox[7]);  // 基linebyup部minute
            $totalTextHeight = $descender + $ascender;
        } else {
            // 降leveluse估算method
            // toatmiddle文character,eachcharacterwidth约equal字bodysize
            $chineseCharCount = mb_strlen($text, 'UTF-8');
            $textWidth = (int) ($chineseCharCount * $fontSize * 1.0); // increasesecurityside距
            $textHeight = $fontSize;
            $descender = (int) ($fontSize * 0.2); // inside置字body估算down降部minute
            $ascender = (int) ($fontSize * 0.8); // inside置字body估算up升部minute
            $totalTextHeight = $textHeight;
        }

        // 动stateside距:based on字bodysizecalculate,ensure足够nullbetween
        $margin = max(20, (int) ($fontSize * 0.8));

        switch ($position) {
            case 1: // leftupangle
                return [$margin, $margin + $ascender];
            case 2: // up方middle央
                return [max($margin, (int) (($width - $textWidth) / 2)), $margin + $ascender];
            case 3: // rightupangle
                return [max($margin, $width - $textWidth - $margin), $margin + $ascender];
            case 4: // left侧middle央
                return [$margin, (int) (($height + $ascender - $descender) / 2)];
            case 5: // middle央
                return [max($margin, (int) (($width - $textWidth) / 2)), (int) (($height + $ascender - $descender) / 2)];
            case 6: // right侧middle央
                return [max($margin, $width - $textWidth - $margin), (int) (($height + $ascender - $descender) / 2)];
            case 7: // leftdownangle
                return [$margin, $height - $margin - $descender];
            case 8: // down方middle央
                return [max($margin, (int) (($width - $textWidth) / 2)), $height - $margin - $descender];
            case 9: // rightdownangle
                return [max($margin, $width - $textWidth - $margin), $height - $margin - $descender];
            default: // defaultrightdownangle
                return [max($margin, $width - $textWidth - $margin), $height - $margin - $descender];
        }
    }

    /**
     * decodingbase64imagedata.
     */
    private function decodeBase64Image(string $base64Image): string
    {
        // 移exceptdata URLfront缀
        if (str_contains($base64Image, ',')) {
            $base64Image = substr($base64Image, strpos($base64Image, ',') + 1);
        }

        $imageData = base64_decode($base64Image);
        if ($imageData === false) {
            throw new Exception('invalidbase64imagedata');
        }

        return $imageData;
    }

    /**
     * downloadnetworkimage.
     */
    private function downloadImage(string $url): string
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'Delightful-Service/1.0',
            ],
        ]);

        $imageData = file_get_contents($url, false, $context);
        if ($imageData === false) {
            throw new Exception('no法downloadimage: ' . $url);
        }

        return $imageData;
    }

    /**
     * outputimage(no损version).
     * @param mixed $image
     * @param string $format goalformat (png/jpeg/webp/gif)
     */
    private function outputImage($image, string $format = 'auto'): void
    {
        // from动formatdetect
        if ($format === 'auto') {
            if ($this->fontProvider->hasTransparency($image)) {
                $format = 'png'; // have透明degreeusePNG
            } else {
                $format = 'jpeg'; // no透明degreeuseJPEGhighquality
            }
        }

        try {
            switch (strtolower($format)) {
                case 'png':
                    imagepng($image, null, 0); // PNGno损compress
                    break;
                case 'webp':
                    if (function_exists('imagewebp')) {
                        imagewebp($image, null, 100); // WebPno损模type
                    } else {
                        $this->logger->warning('WebP not supported, falling back to PNG');
                        imagepng($image, null, 0);
                    }
                    break;
                case 'gif':
                    // GIFlimitmore多,suggestion升levelforPNG
                    $this->logger->info('Converting GIF to PNG for better quality');
                    imagepng($image, null, 0);
                    break;
                case 'jpeg':
                case 'jpg':
                default:
                    if ($this->fontProvider->hasTransparency($image)) {
                        // JPEGnot supported透明degree,from动转PNG
                        $this->logger->info('JPEG does not support transparency, converting to PNG');
                        imagepng($image, null, 0);
                    } else {
                        imagejpeg($image, null, 100); // JPEGmosthighquality
                    }
                    break;
            }
        } catch (Exception $e) {
            // encodingfailo clockusePNG兜bottom
            $this->logger->error('Image encoding failed, falling back to PNG', [
                'format' => $format,
                'error' => $e->getMessage(),
            ]);
            imagepng($image, null, 0);
        }
    }

    /**
     * detectgraphlikedataformat.
     */
    private function detectImageFormat(string $imageData): string
    {
        $info = getimagesizefromstring($imageData);
        if ($info === false) {
            $this->logger->warning('Unable to detect image format, defaulting to jpeg');
            return 'jpeg';
        }

        return match ($info[2]) {
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_WEBP => 'webp',
            IMAGETYPE_GIF => 'gif',
            IMAGETYPE_JPEG => 'jpeg',
            default => 'jpeg',
        };
    }

    /**
     * frombase64front缀extractgraphlikeformat.
     */
    private function extractBase64Format(string $base64Image): string
    {
        if (str_contains($base64Image, ',')) {
            $prefix = substr($base64Image, 0, strpos($base64Image, ','));

            if (str_contains($prefix, 'image/png')) {
                return 'png';
            }
            if (str_contains($prefix, 'image/webp')) {
                return 'webp';
            }
            if (str_contains($prefix, 'image/gif')) {
                return 'gif';
            }
            if (str_contains($prefix, 'image/jpeg') || str_contains($prefix, 'image/jpg')) {
                return 'jpeg';
            }
        }

        // defaultreturnjpeg
        return 'jpeg';
    }

    /**
     * according toformatgeneratebase64front缀.
     */
    private function generateBase64Prefix(string $format): string
    {
        return match (strtolower($format)) {
            'png' => 'data:image/png;base64,',
            'webp' => 'data:image/webp;base64,',
            'gif' => 'data:image/gif;base64,',
            'jpeg', 'jpg' => 'data:image/jpeg;base64,',
            default => 'data:image/jpeg;base64,',
        };
    }

    private function processBase64Images(string $base64Image, ImageGenerateRequest $imageGenerateRequest): string
    {
        $organizationCode = CloudFileRepository::DEFAULT_ICON_ORGANIZATION_CODE;
        $fileDomainService = di(FileDomainService::class);
        try {
            $subDir = 'open';

            // directlyusealreadycontainXMPwatermarkbase64data
            $uploadFile = new UploadFile($base64Image, $subDir, '');

            $fileDomainService->uploadByCredential($organizationCode, $uploadFile, StorageBucketType::Public);

            $fileLink = $fileDomainService->getLink($organizationCode, $uploadFile->getKey(), StorageBucketType::Public);

            // settingobjectyuandataasfor备usesolution
            $validityPeriod = $imageGenerateRequest->getValidityPeriod();
            $metadataContent = [];
            if ($validityPeriod !== null) {
                $metadataContent['validity_period'] = (string) $validityPeriod;
            }
            $metadata = ['metadata' => Json::encode($metadataContent)];

            $fileDomainService->setHeadObjectByCredential($organizationCode, $uploadFile->getKey(), $metadata, StorageBucketType::Public);

            return $fileLink->getUrl();
        } catch (Exception $e) {
            $this->logger->error('Failed to process base64 image', [
                'error' => $e->getMessage(),
                'organization_code' => $organizationCode,
            ]);
            // If upload fails, keep the original base64 data
            $processedImage = $base64Image;
        }
        return $processedImage;
    }
}
