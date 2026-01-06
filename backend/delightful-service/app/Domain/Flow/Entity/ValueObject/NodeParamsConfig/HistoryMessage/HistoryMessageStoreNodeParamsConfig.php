<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\HistoryMessage;

use App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\DelightfulFlowMessageType;
use App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\NodeParamsConfig;
use Delightful\FlowExprEngine\Component;
use Delightful\FlowExprEngine\ComponentFactory;
use Delightful\FlowExprEngine\Structure\StructureType;

class HistoryMessageStoreNodeParamsConfig extends NodeParamsConfig
{
    private DelightfulFlowMessageType $type;

    private ?Component $content = null;

    private ?Component $link = null;

    private ?Component $linkDesc = null;

    public function getType(): DelightfulFlowMessageType
    {
        return $this->type;
    }

    public function getContent(): ?Component
    {
        return $this->content;
    }

    public function getLink(): ?Component
    {
        return $this->link;
    }

    public function getLinkDesc(): ?Component
    {
        return $this->linkDesc;
    }

    public function validate(): array
    {
        $data = DelightfulFlowMessageType::validateParams($this->node->getParams());
        $this->type = $data['type'];
        $this->content = $data['content'];
        $this->link = $data['link'];
        $this->linkDesc = $data['link_desc'];
        return [
            'type' => $this->type->value,
            'content' => $this->content?->toArray(),
            'link' => $this->link?->toArray(),
            'link_desc' => $this->linkDesc?->toArray(),
        ];
    }

    public function generateTemplate(): void
    {
        $this->node->setParams([
            'type' => DelightfulFlowMessageType::Text,
            'content' => ComponentFactory::generateTemplate(StructureType::Value)->toArray(),
            'link' => ComponentFactory::generateTemplate(StructureType::Value)->toArray(),
            'link_desc' => ComponentFactory::generateTemplate(StructureType::Value)->toArray(),
        ]);
    }
}
