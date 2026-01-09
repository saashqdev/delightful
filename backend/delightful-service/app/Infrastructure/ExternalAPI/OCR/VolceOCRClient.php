<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\OCR;

use App\Infrastructure\Core\Exception\OCRException;
use App\Infrastructure\Util\FileType;
use Hyperf\Codec\Json;
use Hyperf\Logger\LoggerFactory;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Volc\Service\Visual;

class VolceOCRClient implements OCRClientInterface
{
    private const int DEFAULT_TIMEOUT = 60;

    private LoggerInterface $logger;

    public function __construct(LoggerFactory $loggerFactory)
    {
        $this->logger = $loggerFactory->get('volce_ocr');
    }

    public function ocr(?string $url = null): string
    {
        // configuration特定 OCR customer端
        $client = Visual::getInstance();
        $client->setAccessKey(config('volce_cv.ocr_pdf.ak'));
        $client->setSecretKey(config('volce_cv.ocr_pdf.sk'));
        $client->setAPI('OCRPdf', '2021-08-23');

        $formParams = ['version' => 'v3', 'page_num' => 100];
        $formParams['image_url'] = $url;
        $isPdfOrImage = $this->isPdfOrImage($url);
        if (empty($isPdfOrImage)) {
            throw new InvalidArgumentException('not support file type, support file type is pdf or image');
        }
        $options = [
            'form_params' => $formParams,
            'timeout' => self::DEFAULT_TIMEOUT,
            'file_type' => $isPdfOrImage,
        ];
        $response = $client->CallAPI('OCRPdf', $options);
        $content = $response->getContents();
        $this->logger->info('火山OCRresponse: ' . $content);
        $result = Json::decode($content);
        $code = $result['code'] ?? 0; // ifnothave 'code'，thenusedefaulterrorcode
        if ($code !== 10000) {
            $message = $result['Message'] ?? '火山OCR遇toerror,message not存in'; // ifnothave 'message'，thenusedefaultmessage
            $this->logger->error(sprintf(
                '火山OCR遇toerror:%s,',
                $message,
            ));
            throw new OCRException($message, $code);
        }
        return $result['data']['markdown'];
    }

    public function isPdfOrImage(string $url): ?string
    {
        $type = FileType::getType($url);
        if ($type === 'pdf') {
            return 'pdf';
        }
        if (in_array($type, ['jpg', 'jpeg', 'png', 'bmp'], true)) {
            return 'image';
        }
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);
        // get HTTP head部info
        $headers = get_headers($url, true, $context);

        // checkwhethersuccessgethead部info
        if ($headers === false || ! isset($headers['Content-Type'])) {
            return null; // no法getfiletype
        }

        // parse Content-Type
        $contentType = is_array($headers['Content-Type']) ? $headers['Content-Type'][0] : $headers['Content-Type'];

        // checkfiletypewhetherfor PDF orimage
        if ($contentType === 'application/pdf') {
            return 'pdf';
        }
        if (in_array($contentType, ['image/jpeg', 'image/jpg', 'image/png', 'image/bmp'], true)) {
            return 'image';
        }

        return null; // 既notis PDF alsonotisfinger定imageformat
    }
}
