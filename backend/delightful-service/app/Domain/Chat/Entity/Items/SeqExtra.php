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
     * 序columnnumber所属conversation id.
     */
    protected string $topicId = '';

    /**
     * userhairthisitemmessageo clock，他所loginenvironment id.（such asin saas 生产填someprivatedeploy预publishenvironment码）
     * useatrequestfinger定privatedeploytest/预publish/生产environment.
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

    // seqExtra havethesefieldisnotallowcopy
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
