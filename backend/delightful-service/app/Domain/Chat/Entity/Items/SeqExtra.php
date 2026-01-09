<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Entity\Items;

use App\Domain\Chat\DTO\Message\Trait\EditMessageOptionsTrait;
use App\Domain\Chat\Entity\AbstractEntity;

class SeqExtra extends AbstractEntity
{
    use EditMessageOptionsTrait;

    /**
     * 序列号所属conversation id.
     */
    protected string $topicId = '';

    /**
     * user发这条message时，他所登录的环境 id.（比如在 saas 生产填了某个私有化部署预发布环境的码）
     * 用于请求指定私有化部署的测试/预发布/生产环境.
     */
    protected ?int $delightfulEnvId = null;

    protected ?string $language = null;

    public function getDelightfulEnvId(): ?int
    {
        return $this->delightfulEnvId;
    }

    public function setDelightfulEnvId(?int $delightfulEnvId): self
    {
        $this->delightfulEnvId = $delightfulEnvId;
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

    // seqExtra 有些字段是不允许复制的
    public function getExtraCanCopyData(): array
    {
        return [
            'delightful_env_id' => $this->getDelightfulEnvId(),
            'topic_id' => $this->getTopicId(),
        ];
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(?string $language): self
    {
        $this->language = $language;
        return $this;
    }
}
