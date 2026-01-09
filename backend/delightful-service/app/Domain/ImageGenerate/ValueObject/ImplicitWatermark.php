<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\ImageGenerate\ValueObject;

use App\Infrastructure\Util\Aes\AesUtil;
use DateTime;

use function Hyperf\Config\config;

// 隐typewatermark
class ImplicitWatermark
{
    protected string $userId;

    protected string $organizationCode;

    protected DateTime $createdAt;

    protected string $topicId = '';

    protected string $sign = '';

    protected string $agentId = '';

    public function __construct()
    {
        $this->createdAt = new DateTime();
        // settingdefaultsignature,insettinguserinformationbackwillfrom动encrypt
        $this->sign = 'be_delightful';
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function setUserId(string $userId): self
    {
        $currentSign = $this->getSign(); // 先decryptgetoriginalsignature
        $this->userId = $userId;
        if (! empty($currentSign)) {
            $this->setSign($currentSign); // 重newencryptsignature
        }
        return $this;
    }

    public function getOrganizationCode(): string
    {
        return $this->organizationCode;
    }

    public function setOrganizationCode(string $organizationCode): self
    {
        $currentSign = $this->getSign(); // 先decryptgetoriginalsignature
        $this->organizationCode = $organizationCode;
        if (! empty($currentSign)) {
            $this->setSign($currentSign); // 重newencryptsignature
        }
        return $this;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getTopicId(): string
    {
        return $this->topicId;
    }

    public function setTopicId(string $topicId): self
    {
        $this->topicId = $topicId;
        return $this;
    }

    public function getSign(): string
    {
        $decrypted = AesUtil::decode($this->_getAesKey(), $this->sign);
        return $decrypted !== false ? $decrypted : $this->sign;
    }

    public function setSign(string $sign): self
    {
        $encrypted = AesUtil::encode($this->_getAesKey(), $sign);
        $this->sign = $encrypted !== false ? $encrypted : $sign;
        return $this;
    }

    public function getAgentId(): string
    {
        return $this->agentId;
    }

    public function setAgentId(string $agentId): self
    {
        $this->agentId = $agentId;
        return $this;
    }

    public function toArray(): array
    {
        $data = [
            'userId' => $this->userId,
            'organizationCode' => $this->organizationCode,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
            'sign' => $this->sign, // usedecryptbacksignature
        ];

        if ($this->topicId !== '') {
            $data['topicId'] = $this->topicId;
        }

        if ($this->agentId !== '') {
            $data['agentId'] = $this->agentId;
        }

        return $data;
    }

    /**
     * Get AES key with salt (userId + organizationCode).
     */
    private function _getAesKey(): string
    {
        $baseKey = config('image_generate.watermark_aes_key', '');
        $salt = ($this->userId ?? '') . ($this->organizationCode ?? '');
        return $baseKey . $salt;
    }
}
