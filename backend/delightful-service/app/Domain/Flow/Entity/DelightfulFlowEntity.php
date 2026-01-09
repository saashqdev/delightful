<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Entity;

use App\Domain\Flow\Entity\ValueObject\Code;
use App\Domain\Flow\Entity\ValueObject\ConstValue;
use App\Domain\Flow\Entity\ValueObject\Node;
use App\Domain\Flow\Entity\ValueObject\NodeInput;
use App\Domain\Flow\Entity\ValueObject\NodeOutput;
use App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\Start\StartNodeParamsConfig;
use App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\Start\Structure\TriggerType;
use App\Domain\Flow\Entity\ValueObject\Type;
use App\ErrorCode\FlowErrorCode;
use App\Infrastructure\Core\AbstractEntity;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use Closure;
use DateTime;
use Delightful\FlowExprEngine\Component;
use Delightful\FlowExprEngine\ComponentFactory;
use Delightful\FlowExprEngine\Structure\StructureType;
use Throwable;

class DelightfulFlowEntity extends AbstractEntity
{
    protected ?int $id = null;

    protected string $organizationCode;

    /**
     * 唯一encoding，仅increateo clockgenerate，use作给front端的id.
     */
    protected string $code;

    /**
     * processname（助理name）.
     */
    protected string $name;

    /**
     * processdescription （助理description）.
     */
    protected string $description;

    /**
     * process图标（助理avatar）.
     */
    protected string $icon = '';

    /**
     * processtype.
     */
    protected Type $type;

    protected string $toolSetId = '';

    /**
     * 仅front端need，process编row放to node sectionpointconfiguration的 next_nodes middle.
     */
    protected array $edges;

    /**
     * @var Node[]
     */
    protected array $nodes;

    protected ?Component $globalVariable = null;

    protected bool $enabled = false;

    protected string $versionCode = '';

    protected string $creator;

    protected DateTime $createdAt;

    protected string $modifier;

    protected DateTime $updatedAt;

    private bool $isCollectingNodes = false;

    /**
     * process的入口.
     */
    private ?NodeInput $input = null;

    /**
     * process的出口.
     */
    private ?NodeOutput $output = null;

    private ?NodeInput $customSystemInput = null;

    private ?array $nodesById = null;

    private ?array $parentNodesById = null;

    private ?Node $startNode = null;

    private ?Node $endNode = null;

    private int $userOperation = 0;

    /**
     * process的callbackfunction，ifhave该value，那么将直接execute该选择，而not是passNodeRunner来execute.
     */
    private ?Closure $callback = null;

    private ?array $callbackResult = null;

    /**
     * agent id.
     */
    private string $agentId = '';

    public function shouldCreate(): bool
    {
        return empty($this->code);
    }

    public function prepareTestRun(): void
    {
        // 试运line是要按照开启o clock计算
        $this->enabled = true;

        // process试运line其实只need nodes
        if (empty($this->nodes)) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'common.empty', ['label' => 'flow.fields.nodes']);
        }
        $this->collectNodes();
    }

    public function prepareForSaveNode(?DelightfulFlowEntity $delightfulFlowEntity): void
    {
        $this->nodeValidate();

        if ($delightfulFlowEntity) {
            $this->requiredValidate();

            $delightfulFlowEntity->setName($this->name);
            $delightfulFlowEntity->setDescription($this->description ?? '');
            $delightfulFlowEntity->setIcon($this->icon);
            $delightfulFlowEntity->setNodes($this->nodes);
            $delightfulFlowEntity->setEdges($this->edges);
            $delightfulFlowEntity->setModifier($this->creator);
            $delightfulFlowEntity->setUpdatedAt($this->createdAt);
        }
    }

    public function prepareForCreation(): void
    {
        $this->requiredValidate();

        $this->code = Code::DelightfulFlow->gen();
        $this->modifier = $this->creator;
        $this->updatedAt = $this->createdAt;
        $this->enabled = false;
        $this->nodes = [];
        $this->edges = [];
    }

    public function prepareForModification(DelightfulFlowEntity $delightfulFlow): void
    {
        $this->requiredValidate();

        $delightfulFlow->setName($this->name);
        $delightfulFlow->setDescription($this->description);
        $delightfulFlow->setIcon($this->icon);
        $delightfulFlow->setToolSetId($this->toolSetId);
        $delightfulFlow->setModifier($this->creator);
        $delightfulFlow->setUpdatedAt($this->createdAt);
    }

    public function prepareForChangeEnable(): void
    {
        $this->enabled = ! $this->enabled;
        if ($this->enabled) {
            // if是要开启，need检测whetherhave nodes configuration
            if (empty($this->nodes)) {
                ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'flow.node.cannot_enable_empty_nodes');
            }
        }
    }

    public function prepareForPublish(DelightfulFlowVersionEntity $delightfulFlowVersionEntity, string $publisher): void
    {
        $this->versionCode = $delightfulFlowVersionEntity->getCode();

        $delightfulFlow = $delightfulFlowVersionEntity->getDelightfulFlow();

        $this->name = $delightfulFlow->getName();
        $this->description = $delightfulFlow->getDescription();
        $this->icon = $delightfulFlow->getIcon();
        $this->edges = $delightfulFlow->getEdges();
        $this->nodes = $delightfulFlow->getNodes();
        $this->globalVariable = $delightfulFlow->getGlobalVariable();

        foreach ($this->nodes as $node) {
            $node->getNodeParamsConfig()->setValidateScene('publish');
        }

        $this->modifier = $publisher;
        $this->updatedAt = new DateTime('now');

        // publisho clockneed按照开启来process
        $enable = $this->enabled;
        $this->enabled = true;
        $this->nodeValidate(true);
        // 复原
        $this->enabled = $enable;
    }

    public function collectNodes(bool $refresh = false): void
    {
        if ($refresh) {
            $this->isCollectingNodes = false;
        }
        if ($this->isCollectingNodes) {
            return;
        }
        $this->clearCollectNodes();

        $this->nodesById = [];
        $this->parentNodesById = [];
        foreach ($this->nodes as $node) {
            if (array_key_exists($node->getNodeId(), $this->nodesById)) {
                ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'flow.node.duplication_node_id', ['node_id' => $node->getNodeId()]);
            }

            $this->nodesById[$node->getNodeId()] = $node;
            if ($node->getParentId()) {
                $this->parentNodesById[$node->getParentId()][] = $node;
            }

            if ($node->isStart() && ! $node->getParentId()) {
                // if已经have一了，那么是error的process，出现了多startsectionpoint
                if ($this->startNode) {
                    ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'flow.node.start.only_one');
                }
                $this->startNode = $node;
            }
            if ($node->isEnd() && ! $node->getParentId()) {
                // 多endsectionpointo clock，暂o clock取first，should要做become只能have一endsectionpoint
                if (! $this->endNode) {
                    $this->endNode = $node;
                }
            }
        }

        // 已经是publishstatus的才need检测
        if ($this->enabled) {
            //            if (! $this->startNode) {
            //                ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'flow.node.start.must_exist');
            //            }
            //            if (! $this->endNode && $this->type->needEndNode()) {
            //                ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'flow.node.end.must_exist');
            //            }
        }

        if ($this->type->canShowParams()) {
            if ($this->startNode) {
                /** @var StartNodeParamsConfig $startNodeParamsConfig */
                $startNodeParamsConfig = $this->startNode->getNodeParamsConfig();
                foreach ($startNodeParamsConfig->getBranches() as $branch) {
                    if ($branch->getTriggerType() === TriggerType::ParamCall) {
                        $input = new NodeInput();
                        $input->setForm($branch->getOutput()?->getForm());
                        $this->input = $input;

                        $customSystemInput = new NodeInput();
                        $customSystemInput->setForm($branch->getCustomSystemOutput()?->getForm());
                        $this->customSystemInput = $customSystemInput;
                    }
                }
            }
            $this->output = $this->endNode?->getOutput();
        }

        $this->isCollectingNodes = true;
    }

    public function getResult(bool $throw = true): array
    {
        if ($this->getCallbackResult()) {
            return $this->getCallbackResult();
        }
        $result = [];
        foreach ($this->nodes as $node) {
            $nodeDebugResult = $node->getNodeDebugResult();
            if ($nodeDebugResult && ! $nodeDebugResult->isSuccess()) {
                if ($throw) {
                    ExceptionBuilder::throw(FlowErrorCode::ExecuteFailed, $nodeDebugResult->getErrorMessage());
                }
                $result['error_information'] = $nodeDebugResult->getErrorMessage();
            }
            if ($node->isEnd() && $nodeDebugResult && $nodeDebugResult->hasExecute()) {
                // result优先，if已经存in，thennotneed了
                if (empty($result)) {
                    $result = $nodeDebugResult->getOutput() ?? [];
                }
            }
        }
        return $result;
    }

    public function getStartNode(): ?Node
    {
        if ($this->startNode) {
            return $this->startNode;
        }
        $this->collectNodes();
        return $this->startNode;
    }

    public function getEndNode(): ?Node
    {
        if ($this->endNode) {
            return $this->endNode;
        }
        $this->collectNodes();
        return $this->endNode;
    }

    public function prepareForDeletion()
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(null|int|string $id): void
    {
        $this->id = $id ? (int) $id : null;
    }

    public function getOrganizationCode(): string
    {
        return $this->organizationCode;
    }

    public function setOrganizationCode(string $organizationCode): void
    {
        $this->organizationCode = $organizationCode;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function setIcon(string $icon): void
    {
        $this->icon = $icon;
    }

    public function getType(): Type
    {
        return $this->type;
    }

    public function setType(Type $type): void
    {
        $this->type = $type;
    }

    public function getToolSetId(): string
    {
        return $this->toolSetId;
    }

    public function setToolSetId(string $toolSetId): void
    {
        $this->toolSetId = $toolSetId;
    }

    public function getEdges(): array
    {
        return $this->edges;
    }

    public function setEdges(array $edges): void
    {
        $this->edges = $edges;
    }

    public function getNodes(): array
    {
        return $this->nodes;
    }

    public function getNodeById(string $id): ?Node
    {
        $this->collectNodes();
        return $this->nodesById[$id] ?? null;
    }

    /**
     * @return Node[]
     */
    public function getNodesByParentId(string $parentId): array
    {
        $this->collectNodes();
        return $this->parentNodesById[$parentId] ?? [];
    }

    public function setNodes(array $nodes): void
    {
        $this->nodes = $nodes;
        $this->collectNodes(true);
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    public function getVersionCode(): string
    {
        return $this->versionCode;
    }

    public function setVersionCode(string $versionCode): void
    {
        $this->versionCode = $versionCode;
    }

    public function getCreator(): string
    {
        return $this->creator;
    }

    public function setCreator(string $creator): void
    {
        $this->creator = $creator;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getModifier(): string
    {
        return $this->modifier;
    }

    public function setModifier(string $modifier): void
    {
        $this->modifier = $modifier;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function getInput(): NodeInput
    {
        if ($this->input?->getFormComponent()) {
            return $this->input;
        }
        $input = new NodeInput();
        $input->setForm(ComponentFactory::generateTemplate(StructureType::Form));
        $this->input = $input;

        $this->collectNodes();
        return $this->input;
    }

    public function getOutput(): NodeOutput
    {
        if ($this->output?->getFormComponent()) {
            return $this->output;
        }
        $output = new NodeOutput();
        $output->setForm(ComponentFactory::generateTemplate(StructureType::Form));
        $this->output = $output;

        $this->collectNodes();
        return $this->output;
    }

    public function getCustomSystemInput(): NodeInput
    {
        if ($this->customSystemInput?->getFormComponent()) {
            return $this->customSystemInput;
        }
        $customSystemInput = new NodeInput();
        $customSystemInput->setForm(ComponentFactory::generateTemplate(StructureType::Form));
        $this->customSystemInput = $customSystemInput;

        $this->collectNodes();
        return $this->customSystemInput;
    }

    public function getGlobalVariable(): ?Component
    {
        return $this->globalVariable;
    }

    public function setGlobalVariable(?Component $globalVariable): void
    {
        $this->globalVariable = $globalVariable;
    }

    public function setEndNode(?Node $endNode): void
    {
        $this->endNode = $endNode;
    }

    public function getUserOperation(): int
    {
        return $this->userOperation;
    }

    public function setUserOperation(int $userOperation): void
    {
        $this->userOperation = $userOperation;
    }

    public function setInput(?NodeInput $input): void
    {
        $this->input = $input;
    }

    public function setOutput(?NodeOutput $output): void
    {
        $this->output = $output;
    }

    public function setCustomSystemInput(?NodeInput $customSystemInput): void
    {
        $this->customSystemInput = $customSystemInput;
    }

    public function hasCallback(): bool
    {
        return ! empty($this->callback);
    }

    public function getCallback(): ?Closure
    {
        return $this->callback;
    }

    public function setCallback(?Closure $callback): void
    {
        $this->callback = $callback;
    }

    public function getCallbackResult(): ?array
    {
        return $this->callbackResult;
    }

    public function setCallbackResult(?array $callbackResult): void
    {
        $this->callbackResult = $callbackResult;
    }

    public function getAgentId(): string
    {
        return $this->agentId;
    }

    public function setAgentId(string $agentId): void
    {
        $this->agentId = $agentId;
    }

    private function clearCollectNodes(): void
    {
        $this->nodesById = null;
        $this->parentNodesById = null;
        $this->startNode = null;
        $this->endNode = null;
        $this->input = null;
        $this->output = null;
    }

    private function requiredValidate(): void
    {
        $this->checkType();
        $this->checkOrganizationCode();
        $this->checkCreator();
        $this->checkName();
        $this->checkDescription();

        if (empty($this->toolSetId)) {
            $this->toolSetId = ConstValue::TOOL_SET_DEFAULT_CODE;
        }
    }

    private function checkType(): void
    {
        if (! isset($this->type)) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'common.empty', ['label' => 'flow.fields.flow_type']);
        }
    }

    private function checkOrganizationCode(): void
    {
        if (empty($this->organizationCode)) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'common.empty', ['label' => 'flow.fields.organization_code']);
        }
    }

    private function checkCreator(): void
    {
        if (empty($this->creator)) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'common.empty', ['label' => 'flow.fields.creator']);
        }
        if (empty($this->createdAt)) {
            $this->createdAt = new DateTime();
        }
    }

    private function checkName(): void
    {
        if (empty($this->name)) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'common.empty', ['label' => 'flow.fields.flow_name']);
        }

        if ($this->type === Type::Tools) {
            // name只能contain 字母、number、down划line
            if (! preg_match('/^[a-zA-Z0-9_]+$/', $this->name)) {
                ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'flow.tool.name.invalid_format');
            }
            // todo 要唯一
            // todo inside置tool名allowbeuse
        }
    }

    private function checkDescription(): void
    {
        if ($this->type === Type::Tools) {
            if (empty($this->description)) {
                ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'common.empty', ['label' => 'flow.fields.tool_description']);
            }
        }
    }

    private function nodeValidate(bool $strict = false): void
    {
        $this->collectNodes();

        foreach ($this->nodes as $node) {
            try {
                $node->validate($strict);
            } catch (Throwable $throwable) {
                ExceptionBuilder::throw(
                    FlowErrorCode::ValidateFailed,
                    'flow.node.validation_failed',
                    [
                        'node_id' => $node->getNodeId(),
                        'node_type' => $node->getNodeTypeName(),
                        'error' => $throwable->getMessage(),
                    ]
                );
            }
        }
    }
}
