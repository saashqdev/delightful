<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Agent\Service\ThirdPlatformChat\FeiShuRobot\FeiShu\Image;

use Fan\Feishu\AccessToken\TenantAccessToken;
use Fan\Feishu\HasAccessToken;
use Fan\Feishu\Http\Client;
use Fan\Feishu\ProviderInterface;

class Image implements ProviderInterface
{
    use HasAccessToken;

    public function __construct(protected Client $client, protected TenantAccessToken $token)
    {
    }

    /**
     * uploadimage.
     *
     * @param string $imageUrl imageURL
     * @param string $imageType imagetype,optionalvalue:message,avatar
     * @return string imagekey
     */
    public function uploadByUrl(string $imageUrl, string $imageType = 'message'): string
    {
        // downloadimagecontent
        $imageContent = file_get_contents($imageUrl);
        if ($imageContent === false) {
            return '';
        }

        // getimagetype
        $imageInfo = getimagesizefromstring($imageContent);
        $mimeType = $imageInfo['mime'] ?? 'image/jpeg';

        // buildrequest
        $response = $this->request('POST', 'open-apis/im/v1/images', [
            'multipart' => [
                [
                    'name' => 'image_type',
                    'contents' => $imageType,
                ],
                [
                    'name' => 'image',
                    'contents' => $imageContent,
                    'filename' => 'image.' . $this->getExtensionFromMimeType($mimeType),
                    'headers' => [
                        'Content-Type' => $mimeType,
                    ],
                ],
            ],
        ]);

        return $response['data']['image_key'] ?? '';
    }

    public static function getName(): string
    {
        return 'image';
    }

    /**
     * according toMIMEtypegetfileextension名.
     *
     * @param string $mimeType MIMEtype
     * @return string fileextension名
     */
    private function getExtensionFromMimeType(string $mimeType): string
    {
        $map = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/bmp' => 'bmp',
            'image/webp' => 'webp',
        ];

        return $map[$mimeType] ?? 'jpg';
    }
}
