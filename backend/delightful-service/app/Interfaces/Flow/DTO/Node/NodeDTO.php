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
     * sectionpointyuandata,canuseasgivefrontclientlocate,backclientonlystorageandshow,nothaveanylogic.
     */
    public array $meta = [];

    /**
     * sectionpointparameterconfiguration,itemfrontrely onarraycomedatapass.
     */
    public array $params = [];

    public array $nextNodes = [];

    public ?NodeInputDTO $input = null;

    public ?NodeOutputDTO $output = null;

    public ?NodeOutputDTO $systemOutput = null;

    /**
     * getsectionpointID.
     */
    public function getNodeId(): string
    {
        return $this->nodeId;
    }

    /**
     * setsectionpointID.
     */
    public function setNodeId(?string $nodeId): self
    {
        $this->nodeId = $nodeId ?? '';
        return $this;
    }

    /**
     * getwhetherfordebugmodetype.
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
     * setwhetherfordebugmodetype.
     */
    public function setDebug(?bool $debug): self
    {
        $this->debug = $debug ?? false;
        return $this;
    }

    /**
     * getsectionpointname.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * setsectionpointname.
     */
    public function setName(?string $name): self
    {
        $this->name = $name ?? '';
        return $this;
    }

    /**
     * getsectionpointdescription.
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * setsectionpointdescription.
     */
    public function setDescription(?string $description): self
    {
        $this->description = $description ?? '';
        return $this;
    }

    /**
     * getsectionpointtype.
     */
    public function getNodeType(): int
    {
        return $this->nodeType;
    }

    /**
     * setsectionpointtype.
     */
    public function setNodeType(null|int|string $nodeType): self
    {
        $this->nodeType = (int) ($nodeType ?? 0);
        return $this;
    }

    /**
     * getsectionpointversion.
     */
    public function getNodeVersion(): string
    {
        return $this->nodeVersion;
    }

    /**
     * setsectionpointversion.
     */
    public function setNodeVersion(?string $nodeVersion): self
    {
        $this->nodeVersion = $nodeVersion ?? '';
        return $this;
    }

    /**
     * getsectionpointyuandata.
     */
    public function getMeta(): array
    {
        return $this->meta;
    }

    /**
     * setsectionpointyuandata.
     */
    public function setMeta(?array $meta): self
    {
        $this->meta = $meta ?? [];
        return $this;
    }

    /**
     * getsectionpointparameterconfiguration.
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * setsectionpointparameterconfiguration.
     */
    public function setParams(?array $params): self
    {
        $this->params = $params ?? [];
        return $this;
    }

    /**
     * getdownonesectionpointlist.
     */
    public function getNextNodes(): array
    {
        return $this->nextNodes;
    }

    /**
     * setdownonesectionpointlist.
     */
    public function setNextNodes(?array $nextNodes): self
    {
        $this->nextNodes = $nextNodes ?? [];
        return $this;
    }

    /**
     * getsectionpointinput.
     */
    public function getInput(): ?NodeInputDTO
    {
        return $this->input;
    }

    /**
     * setsectionpointinput.
     */
    public function setInput(mixed $input): void
    {
        $this->input = DelightfulFlowNodeAssembler::createNodeInputDTOByMixed($input);
    }

    /**
     * getsectionpointoutput.
     */
    public function getOutput(): ?NodeOutputDTO
    {
        return $this->output;
    }

    /**
     * setsectionpointoutput.
     */
    public function setOutput(mixed $output): void
    {
        $this->output = DelightfulFlowNodeAssembler::createNodeOutputDTOByMixed($output);
    }

    /**
     * getsystemoutput.
     */
    public function getSystemOutput(): ?NodeOutputDTO
    {
        return $this->systemOutput;
    }

    /**
     * setsystemoutput.
     */
    public function setSystemOutput(null|array|NodeOutputDTO $systemOutput): void
    {
        $this->systemOutput = DelightfulFlowNodeAssembler::createNodeOutputDTOByMixed($systemOutput);
    }
}
