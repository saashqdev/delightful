<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Flow\DTO\Node;

use App\Interfaces\Flow\Assembler\Node\DelightfulFlowNodeAssembler;
use App\Interfaces\Flow\DTO\AbstractFlowDTO;

class NodeDTO extends AbstractFlowDTO
{
    public string $nodeId = '';

    public bool $debug = false;

    public string $name = '';

    public string $description = '';

    public int $nodeType = 0;

    public string $nodeVersion = '';

    /**
     * 节点元data，可用作给前端的定位，后端仅storage和展示，没有任何逻辑.
     */
    public array $meta = [];

    /**
     * 节点parameterconfiguration，目前依靠array来data传递.
     */
    public array $params = [];

    public array $nextNodes = [];

    public ?NodeInputDTO $input = null;

    public ?NodeOutputDTO $output = null;

    public ?NodeOutputDTO $systemOutput = null;

    /**
     * get节点ID.
     */
    public function getNodeId(): string
    {
        return $this->nodeId;
    }

    /**
     * set节点ID.
     */
    public function setNodeId(?string $nodeId): self
    {
        $this->nodeId = $nodeId ?? '';
        return $this;
    }

    /**
     * get是否为debug模式.
     */
    public function isDebug(): bool
    {
        return $this->debug;
    }

    public function getDebug(): bool
    {
        return $this->debug;
    }

    /**
     * set是否为debug模式.
     */
    public function setDebug(?bool $debug): self
    {
        $this->debug = $debug ?? false;
        return $this;
    }

    /**
     * get节点name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * set节点name.
     */
    public function setName(?string $name): self
    {
        $this->name = $name ?? '';
        return $this;
    }

    /**
     * get节点description.
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * set节点description.
     */
    public function setDescription(?string $description): self
    {
        $this->description = $description ?? '';
        return $this;
    }

    /**
     * get节点type.
     */
    public function getNodeType(): int
    {
        return $this->nodeType;
    }

    /**
     * set节点type.
     */
    public function setNodeType(null|int|string $nodeType): self
    {
        $this->nodeType = (int) ($nodeType ?? 0);
        return $this;
    }

    /**
     * get节点version.
     */
    public function getNodeVersion(): string
    {
        return $this->nodeVersion;
    }

    /**
     * set节点version.
     */
    public function setNodeVersion(?string $nodeVersion): self
    {
        $this->nodeVersion = $nodeVersion ?? '';
        return $this;
    }

    /**
     * get节点元data.
     */
    public function getMeta(): array
    {
        return $this->meta;
    }

    /**
     * set节点元data.
     */
    public function setMeta(?array $meta): self
    {
        $this->meta = $meta ?? [];
        return $this;
    }

    /**
     * get节点parameterconfiguration.
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * set节点parameterconfiguration.
     */
    public function setParams(?array $params): self
    {
        $this->params = $params ?? [];
        return $this;
    }

    /**
     * get下一节点list.
     */
    public function getNextNodes(): array
    {
        return $this->nextNodes;
    }

    /**
     * set下一节点list.
     */
    public function setNextNodes(?array $nextNodes): self
    {
        $this->nextNodes = $nextNodes ?? [];
        return $this;
    }

    /**
     * get节点input.
     */
    public function getInput(): ?NodeInputDTO
    {
        return $this->input;
    }

    /**
     * set节点input.
     */
    public function setInput(mixed $input): void
    {
        $this->input = DelightfulFlowNodeAssembler::createNodeInputDTOByMixed($input);
    }

    /**
     * get节点output.
     */
    public function getOutput(): ?NodeOutputDTO
    {
        return $this->output;
    }

    /**
     * set节点output.
     */
    public function setOutput(mixed $output): void
    {
        $this->output = DelightfulFlowNodeAssembler::createNodeOutputDTOByMixed($output);
    }

    /**
     * get系统output.
     */
    public function getSystemOutput(): ?NodeOutputDTO
    {
        return $this->systemOutput;
    }

    /**
     * set系统output.
     */
    public function setSystemOutput(null|array|NodeOutputDTO $systemOutput): void
    {
        $this->systemOutput = DelightfulFlowNodeAssembler::createNodeOutputDTOByMixed($systemOutput);
    }
}
