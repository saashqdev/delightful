<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Agent\Service\ThirdPlatformChat\FeiShuRobot\FeiShu\File;

use Fan\Feishu\AccessToken\TenantAccessToken;
use Fan\Feishu\Exception\TokenInvalidException;
use Fan\Feishu\HasAccessToken;
use Fan\Feishu\Http\Client;
use Fan\Feishu\ProviderInterface;

class File implements ProviderInterface
{
    use HasAccessToken;

    public function __construct(protected Client $client, protected TenantAccessToken $token)
    {
    }

    /**
     * getIMfile.
     *
     * @param string $messageId messageID
     * @param string $fileKey fileKey
     * @param string $type filetype
     * @return string filepath
     */
    public function getIMFile(string $messageId, string $fileKey, string $type = 'file'): string
    {
        if (empty($fileKey)) {
            return '';
        }
        $retry = true;
        try {
            retry:
            $method = 'GET';
            $uri = 'open-apis/im/v1/messages/' . $messageId . '/resources/' . $fileKey;
            $options = [
                'query' => [
                    'type' => $type,
                ],
            ];
            $response = $this->client->client($this->token)->request($method, $uri, $options);
        } catch (TokenInvalidException) {
            $this->token->getToken(true);
            /* @phpstan-ignore-next-line */
            if ($retry) {
                $retry = false;
                goto retry;
            }
            throw new TokenInvalidException('Token invalid');
        }
        // responseisone二enter制file，saveto本ground
        $localFile = tempnam(sys_get_temp_dir(), 'feishu_file_');
        // according to header middle content-type setting本groundfile名andextension名
        $contentType = $response->getHeader('Content-Type')[0] ?? '';
        $localFile = match ($contentType) {
            'image/jpeg', 'image/jpg' => $localFile . '.jpg',
            'image/png' => $localFile . '.png',
            'image/gif' => $localFile . '.gif',
            'image/webp' => $localFile . '.webp',
            'image/bmp' => $localFile . '.bmp',
            default => $localFile,
        };

        file_put_contents($localFile, $response->getBody()->getContents());
        return $localFile;
    }

    public static function getName(): string
    {
        return 'file';
    }
}
