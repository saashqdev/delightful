<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Flow\ExecuteManager\NodeRunner;

use App\Application\Flow\ExecuteManager\Attachment\AbstractAttachment;
use App\Application\Flow\ExecuteManager\Attachment\Attachment;
use App\Application\Flow\ExecuteManager\Attachment\ExternalAttachment;
use App\Application\Flow\ExecuteManager\ExecutionData\ExecutionData;
use App\Application\Flow\ExecuteManager\ExecutionData\ExecutionDataCollector;
use App\Application\Flow\ExecuteManager\Memory\FlowMemoryManager;
use App\Application\ModelGateway\Mapper\ModelGatewayMapper;
use App\Application\ModelGateway\Service\LLMAppService;
use App\Application\Permission\Service\OperationPermissionAppService;
use App\Domain\Chat\Service\DelightfulChatFileDomainService;
use App\Domain\Contact\Service\DelightfulUserDomainService;
use App\Domain\File\Service\FileDomainService;
use App\Domain\Flow\Entity\ValueObject\MemoryType;
use App\Domain\Flow\Entity\ValueObject\Node;
use App\Domain\Flow\Entity\ValueObject\NodeDebugResult;
use App\Domain\Flow\Service\DelightfulFlowDomainService;
use App\Domain\ModelGateway\Service\MsgLogDomainService;
use App\ErrorCode\FlowErrorCode;
use App\Infrastructure\Core\Contract\Flow\NodeRunnerInterface;
use App\Infrastructure\Core\Dag\VertexResult;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\SSRF\Exception\SSRFException;
use App\Infrastructure\Util\SSRF\SSRFUtil;
use Delightful\CloudFile\Kernel\Struct\UploadFile;
use Delightful\CloudFile\Kernel\Utils\EasyFileTools;
use Delightful\FlowExprEngine\ComponentFactory;
use Hyperf\Context\Context;
use Hyperf\Coroutine\Parallel;
use Hyperf\Engine\Coroutine;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Throwable;

abstract class NodeRunner implements NodeRunnerInterface
{
    protected Node $node;

    protected FlowMemoryManager $flowMemoryManager;

    protected DelightfulFlowDomainService $delightfulFlowDomainService;

    protected FileDomainService $fileDomainService;

    protected OperationPermissionAppService $operationPermissionAppService;

    protected LoggerInterface $logger;

    protected CacheInterface $cache;

    protected DelightfulChatFileDomainService $delightfulChatFileDomainService;

    protected MsgLogDomainService $msgLogDomainService;

    protected DelightfulUserDomainService $userDomainService;

    protected ModelGatewayMapper $modelGatewayMapper;

    protected LLMAppService $llmAppService;

    protected string $organizationCode = '';

    public function __construct(Node $node)
    {
        $this->logger = di(LoggerFactory::class)->get('NodeRunner');
        $this->cache = di(CacheInterface::class);
        $this->flowMemoryManager = di(FlowMemoryManager::class);
        $this->delightfulFlowDomainService = di(DelightfulFlowDomainService::class);
        $this->fileDomainService = di(FileDomainService::class);
        $this->operationPermissionAppService = di(OperationPermissionAppService::class);
        $this->delightfulChatFileDomainService = di(DelightfulChatFileDomainService::class);
        $this->msgLogDomainService = di(MsgLogDomainService::class);
        $this->userDomainService = di(DelightfulUserDomainService::class);
        $this->modelGatewayMapper = di(ModelGatewayMapper::class);
        $this->llmAppService = di(LLMAppService::class);

        $this->node = $node;
        // initialize运lineresult
        if (! $this->node->getNodeDebugResult()) {
            $this->node->setNodeDebugResult(new NodeDebugResult($this->node->getNodeVersion()));
        }
    }

    public function execute(VertexResult $vertexResult, ExecutionData $executionData, array $frontResults = []): void
    {
        // sectionpoint运linemost大count限制，防止死loop
        $max = 10000;
        $executeNum = $executionData->getExecuteNum($this->node->getNodeId());
        if ($executeNum >= $max) {
            ExceptionBuilder::throw(FlowErrorCode::ExecuteFailed, 'flow.node.execute_num_limit');
        }
        $this->organizationCode = $executionData->getDataIsolation()->getCurrentOrganizationCode();

        // ignoreexecute
        if ($this->node->getNodeParamsConfig()->isSkipExecute()) {
            return;
        }
        $throw = (bool) ($frontResults['isThrowException'] ?? true);

        $debugResult = $this->node->getNodeDebugResult();
        $debugResult->setStartTime(microtime(true));
        try {
            if (ExecutionDataCollector::isMaxNodeExecuteCountReached($executionData->getId())) {
                $this->logger->warning('NodeExecuteCountReached', [
                    'executor_id' => $executionData->getId(),
                    'node_id' => $this->node->getNodeId(),
                    'node_name' => $this->node->getName(),
                    'max_count' => ExecutionDataCollector::MAX_COUNT,
                ]);
                ExceptionBuilder::throw(
                    FlowErrorCode::ExecuteFailed,
                    'flow.executor.node_execute_count_reached',
                    ['node_id' => $this->node->getNodeId(), 'max_count' => ExecutionDataCollector::MAX_COUNT]
                );
            }

            $debugResult->setParams($this->node->getParams());
            if ($this->node->hasCallback()) {
                $callback = $this->node->getCallback();
                $callback($vertexResult, $executionData, $frontResults);
            } else {
                $this->node->validate();
                // 提frontget本time的result，ifhave，then直接use
                $nextExecuteNum = $executeNum + 1;
                $historyVertexResult = $executionData->getNodeHistoryVertexResult($this->node->getNodeId(), $nextExecuteNum);
                if ($historyVertexResult) {
                    $vertexResult->copy($historyVertexResult);
                    $vertexResult->addDebugLog('history_vertex_result', $nextExecuteNum);
                } else {
                    $this->run($vertexResult, $executionData, $frontResults);
                }
            }
            $debugResult->setSuccess(true);
            $debugResult->setInput($vertexResult->getInput() ?? []);
            $debugResult->setOutput($vertexResult->getResult() ?? []);

            ExecutionDataCollector::incrementNodeExecuteCount($executionData->getId());
        } catch (Throwable $throwable) {
            $debugResult->setSuccess(false);
            $debugResult->setErrorCode((int) $throwable->getCode());
            $debugResult->setErrorMessage($throwable->getMessage());
            // 出现exceptiono clocknot运lineback续sectionpoint
            $vertexResult->setChildrenIds([]);
            $this->logger->warning('NodeRunnerFailed', [
                'node_id' => $this->node->getNodeId(),
                'node_version' => $this->node->getNodeVersion(),
                'message' => $throwable->getMessage(),
                'file' => $throwable->getFile(),
                'line' => $throwable->getLine(),
                'trace' => $throwable->getTraceAsString(),
            ]);
            // default是要抛exception的
            if ($throw) {
                throw $throwable;
            }
        } finally {
            $debugResult->setDebugLog($vertexResult->getDebugLog());
            $debugResult->setEndTime(microtime(true));
            $debugResult->setChildrenIds($vertexResult->getChildrenIds());
            $debugResult->addLoopDebugResult($debugResult);

            // recordsectionpointcount的result
            $executionData->increaseExecuteNum($this->node->getNodeId(), $vertexResult);
        }
    }

    protected function getModelName(string $paramKey, ExecutionData $executionData): string
    {
        if (empty($paramKey)) {
            return '';
        }
        $modelName = $this->node->getParams()[$paramKey] ?? '';
        if (is_array($modelName)) {
            $modelComponent = ComponentFactory::fastCreate($modelName);
            if (! $modelComponent?->isValue()) {
                ExceptionBuilder::throw(FlowErrorCode::ExecuteValidateFailed, 'flow.component.format_error', ['label' => $paramKey]);
            }
            $modelComponent->getValue()->getExpressionValue()->setIsStringTemplate(true);
            $modelName = $modelComponent->getValue()->getResult($executionData->getExpressionFieldData());
        }
        if (empty($modelName)) {
            ExceptionBuilder::throw(FlowErrorCode::ExecuteValidateFailed, 'flow.model.empty');
        }
        return $modelName;
    }

    protected function formatJson(string $json): array
    {
        $response = trim($json);
        // if $response by ```json 开headthen去except
        if (str_starts_with($response, '```json')) {
            $response = substr($response, 7);
        }
        // if $response by ``` 结tailthen去except
        if (str_ends_with($response, '```')) {
            $response = substr($response, 0, -3);
        }
        $response = trim($response, '\\');
        $response = str_replace('\\\\\"', '\"', $response);
        // if $response 本身then是 JSON format的，那么直接return
        $data = json_decode(trim($response), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }
        return $data;
    }

    protected function isNodeDebug(ExecutionData $executionData): bool
    {
        return $this->node->getDebug() && $executionData->isDebug();
    }

    abstract protected function run(VertexResult $vertexResult, ExecutionData $executionData, array $frontResults): void;

    /**
     * todo 这within暂notimplement重复upload的issue，均when做新fileupload
     * recordprocess所产生的file，均willmeanwhileuploadto云端，back续sectionpointneeduseo clockfromexecuteprocessdatamiddle优先匹配.
     * @return AbstractAttachment[]
     * @throws SSRFException
     */
    protected function recordFlowExecutionAttachments(ExecutionData $executionData, array $attachments, bool $lazyUpload = false): array
    {
        if (empty($attachments)) {
            return [];
        }
        $flowExecutionAttachments = [];

        $parallel = new Parallel(20);
        foreach ($attachments as $attachment) {
            if (! is_string($attachment)) {
                continue;
            }
            // if已经存in，直接addtoresultmiddle
            $path = get_path_by_url($attachment);
            if ($attachmentObj = $executionData->getAttachmentRecord($path)) {
                $flowExecutionAttachments[] = $attachmentObj;
                continue;
            }
            // if是一link，那么need对 url conduct限制
            if (EasyFileTools::isUrl($attachment)) {
                SSRFUtil::getSafeUrl($attachment, replaceIp: false);
            }

            $fromCoroutineId = Coroutine::id();
            $parallel->add(function () use ($fromCoroutineId, $lazyUpload, $attachment, $executionData, &$flowExecutionAttachments) {
                Context::copy($fromCoroutineId, ['request-id', 'x-b3-trace-id']);
                try {
                    if (! $lazyUpload) {
                        $uploadFile = new UploadFile($attachment, 'flow-execute/' . $executionData->getAgentId());
                        $this->fileDomainService->uploadByCredential($executionData->getDataIsolation()->getCurrentOrganizationCode(), $uploadFile);
                        $url = $this->fileDomainService->getLink($executionData->getDataIsolation()->getCurrentOrganizationCode(), $uploadFile->getKey())->getUrl();

                        $attachmentObj = new Attachment(
                            name: $uploadFile->getName(),
                            url: $url,
                            ext: $uploadFile->getExt(),
                            size: $uploadFile->getSize(),
                            originAttachment: $attachment,
                        );
                    } else {
                        $attachmentObj = new ExternalAttachment($attachment);
                    }

                    $flowExecutionAttachments[] = $attachmentObj;
                    $executionData->addAttachmentRecord($attachmentObj);
                } catch (Throwable $throwable) {
                    $this->logger->error('upload_attachment_error', [
                        'error' => $throwable->getMessage(),
                        'file' => $throwable->getFile(),
                        'line' => $throwable->getLine(),
                        'trace' => $throwable->getTraceAsString(),
                        'attachment' => $attachment,
                    ]);
                }
            });
        }
        $parallel->wait(false);

        return $flowExecutionAttachments;
    }

    protected function executeNodeIntroverted(VertexResult $vertexResult, Node $nextNode, ExecutionData $executionData, array $frontResults = []): void
    {
        $nextNodeVertexResult = new VertexResult();
        NodeRunnerFactory::make($nextNode)->execute($nextNodeVertexResult, $executionData, $frontResults);
        $vertexResult->addDebugLog('IntrovertedExecuteNode.' . $nextNode->getNodeId(), [
            'node_id' => $nextNode->getNodeId(),
            'node_type' => $nextNode->getNodeTypeName(),
            'debug' => $nextNodeVertexResult->getDebugLog(),
            'result' => $nextNodeVertexResult->getResult(),
        ]);
        foreach ($nextNodeVertexResult->getDebugLog() as $key => $value) {
            $vertexResult->addDebugLog($key, $value);
        }

        $nextNode->getNodeParamsConfig()->setSkipExecute(true);
    }

    protected function getMemoryType(ExecutionData $executionData): MemoryType
    {
        if ($executionData->getExecutionType()->isFlowMemory()) {
            return MemoryType::Chat;
        }
        if ($executionData->getExecutionType()->isImChat()) {
            return MemoryType::IMChat;
        }
        return MemoryType::None;
    }
}
