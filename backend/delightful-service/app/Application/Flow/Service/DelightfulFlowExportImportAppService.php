<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Flow\Service;

use App\Domain\Agent\Entity\DelightfulAgentEntity;
use App\Domain\Agent\Service\DelightfulAgentDomainService;
use App\Domain\Flow\Entity\DelightfulFlowEntity;
use App\Domain\Flow\Entity\ValueObject\Code;
use App\Domain\Flow\Entity\ValueObject\FlowDataIsolation;
use App\Domain\Flow\Entity\ValueObject\NodeType;
use App\Domain\Flow\Entity\ValueObject\Query\DelightfulFlowToolSetQuery;
use App\Domain\Flow\Entity\ValueObject\Type;
use App\Domain\Flow\Factory\DelightfulFlowFactory;
use App\Domain\Flow\Factory\DelightfulFlowToolSetFactory;
use App\Domain\Flow\Service\DelightfulFlowDomainService;
use App\Domain\Flow\Service\DelightfulFlowToolSetDomainService;
use App\ErrorCode\FlowErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Core\ValueObject\Page;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use DateTime;
use Hyperf\Contract\ConfigInterface;
use Throwable;

class DelightfulFlowExportImportAppService
{
    public function __construct(
        protected DelightfulFlowDomainService $delightfulFlowDomainService,
        protected DelightfulFlowToolSetDomainService $delightfulFlowToolSetDomainService,
        protected ConfigInterface $config
    ) {
    }

    /**
     * exportassistantprocess
     * 递归exportprocess相关的所有节点，include子process和工具process.
     */
    public function exportFlow(FlowDataIsolation $dataIsolation, string $flowCode): array
    {
        // get主process
        $mainFlow = $this->delightfulFlowDomainService->getByCode($dataIsolation, $flowCode);
        if (! $mainFlow) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'flow.common.not_found', ['label' => $flowCode]);
        }

        // ensure是主process
        if (! $mainFlow->getType()->isMain()) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'flow.export.not_main_flow', ['label' => $flowCode]);
        }

        // check是否存在循环dependency
        if ($this->checkCircularDependency($dataIsolation, $flowCode)) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'flow.export.circular_dependency_detected');
        }

        // 准备exportdata结构
        $exportData = [
            'main_flow' => $mainFlow->toArray(),
            'sub_flows' => [],
            'tool_flows' => [],
            'tool_sets' => [],
        ];

        // 已handle的processencoding，防止重复handle
        $processedFlowCodes = [$flowCode];
        $processedToolSetIds = [];

        // 递归handle主process中的子process和工具
        $this->processFlowForExport($dataIsolation, $mainFlow, $exportData, $processedFlowCodes, $processedToolSetIds);

        return $exportData;
    }

    /**
     * importassistantprocess
     * 遇到重复的工具或processwillcreate新实例，并passname区分.
     */
    public function importFlow(FlowDataIsolation $dataIsolation, array $importData, string $agentId = ''): DelightfulFlowEntity
    {
        // import主process
        $mainFlowData = $importData['main_flow'] ?? null;
        if (! $mainFlowData) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'flow.import.missing_main_flow');
        }

        // storage新旧ID映射关系
        $idMapping = [
            'flows' => [], // 老ID => 新ID
            'tool_sets' => [], // 老ID => 新ID
            'nodes' => [], // 老ID => 新ID
        ];

        // import报告，recordcreate、重命名和errorinfo
        $importReport = [
            'created' => [],
            'renamed' => [],
            'errors' => [],
        ];

        // 1. 先import工具集
        if (! empty($importData['tool_sets'])) {
            foreach ($importData['tool_sets'] as $toolSetId => $toolSetData) {
                try {
                    $newToolSetId = $this->importToolSet($dataIsolation, $toolSetData, $idMapping, $importReport);
                    $idMapping['tool_sets'][$toolSetId] = $newToolSetId;
                } catch (Throwable $e) {
                    $importReport['errors'][] = "import工具集 {$toolSetData['name']} fail: {$e->getMessage()}";
                }
            }
        }

        // 2. import工具process
        if (! empty($importData['tool_flows'])) {
            foreach ($importData['tool_flows'] as $toolFlowId => $toolFlowData) {
                try {
                    $newFlowId = $this->importSingleFlow($dataIsolation, $toolFlowData, $idMapping, $importReport);
                    $idMapping['flows'][$toolFlowId] = $newFlowId;
                } catch (Throwable $e) {
                    $importReport['errors'][] = "import工具process {$toolFlowData['name']} fail: {$e->getMessage()}";
                }
            }
        }

        // 3. import子process
        if (! empty($importData['sub_flows'])) {
            foreach ($importData['sub_flows'] as $subFlowId => $subFlowData) {
                try {
                    $newFlowId = $this->importSingleFlow($dataIsolation, $subFlowData, $idMapping, $importReport);
                    $idMapping['flows'][$subFlowId] = $newFlowId;
                } catch (Throwable $e) {
                    $importReport['errors'][] = "import子process {$subFlowData['name']} fail: {$e->getMessage()}";
                }
            }
        }

        // 4. 最后import主process，并关联到指定assistant（如果提供了agentId）
        try {
            // 如果提供了agentId，setting到主processdata中
            if (! empty($agentId)) {
                $mainFlowData['agent_id'] = $agentId;
            }

            $newMainFlowId = $this->importSingleFlow($dataIsolation, $mainFlowData, $idMapping, $importReport);
            $idMapping['flows'][$mainFlowData['code']] = $newMainFlowId;
        } catch (Throwable $e) {
            $importReport['errors'][] = "import主process {$mainFlowData['name']} fail: {$e->getMessage()}";
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'flow.import.main_flow_failed', ['error' => $e->getMessage()]);
        }

        // 5. get并returnimport后的主process实体
        $mainFlow = $this->delightfulFlowDomainService->getByCode($dataIsolation, $newMainFlowId);
        if (! $mainFlow) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'flow.import.failed', ['label' => $newMainFlowId]);
        }
        // 关联process与assistant
        if (! empty($agentId)) {
            $this->associateFlowWithAgent($dataIsolation, $mainFlow->getCode(), $agentId);
        }
        return $mainFlow;
    }

    /**
     * validate是否存在循环dependency
     * use深度优先search检测循环quote.
     */
    public function checkCircularDependency(FlowDataIsolation $dataIsolation, string $flowCode, array $visited = []): bool
    {
        // 如果currentprocess已在accesspath中，instruction形成了循环
        if (in_array($flowCode, $visited)) {
            return true; // 发现循环dependency
        }

        // 将currentprocess添加到accesspath
        $visited[] = $flowCode;

        // getprocess实体
        $flow = $this->delightfulFlowDomainService->getByCode($dataIsolation, $flowCode);
        if (! $flow) {
            return false; // process不存在，不构成循环
        }

        // 遍历所有节点checkdependency
        foreach ($flow->getNodes() as $node) {
            // check子process节点
            if ($node->getNodeType() === NodeType::Sub->value) {
                $subFlowId = $node->getParams()['sub_flow_id'] ?? '';
                if ($subFlowId && $this->checkCircularDependency($dataIsolation, $subFlowId, $visited)) {
                    return true; // 子process中存在循环dependency
                }
            }

            // checkLLM节点中的工具quote
            if ($node->getNodeType() === NodeType::LLM->value || $node->getNodeType() === NodeType::Tool->value) {
                $params = $node->getParams();
                if (isset($params['option_tools']) && is_array($params['option_tools'])) {
                    foreach ($params['option_tools'] as $optionTool) {
                        $toolId = $optionTool['tool_id'] ?? '';
                        $toolSetId = $optionTool['tool_set_id'] ?? '';

                        // 内置工具跳过循环dependencycheck
                        if ($toolId && ! $this->isBuiltInTool($toolId, $toolSetId) && $this->checkCircularDependency($dataIsolation, $toolId, $visited)) {
                            return true; // 工具quote中存在循环dependency
                        }
                    }
                }
            }
        }

        return false; // 没有检测到循环dependency
    }

    /**
     * exportprocess和assistantinfo
     * containprocess的所有data以及assistant的基本info.
     */
    public function exportFlowWithAgent(FlowDataIsolation $dataIsolation, string $flowCode, DelightfulAgentEntity $agent): array
    {
        // getprocessdata
        $flowData = $this->exportFlow($dataIsolation, $flowCode);

        // 添加assistantinfo
        $agentData = [
            'id' => $agent->getId(),
            'name' => $agent->getAgentName(),
            'description' => $agent->getAgentDescription(),
            'flow_code' => $agent->getFlowCode(),
            'avatar' => $agent->getAgentAvatar(),
            'instruct' => $agent->getInstructs(),
            // canaccording toneed添加其他assistantinfo
        ];

        return [
            'agent' => $agentData,
            'flow' => $flowData,
            'export_time' => date('Y-m-d H:i:s'),
            'export_version' => '1.0.0',
        ];
    }

    /**
     * importprocess和assistantinfo
     * 从export的data中createnewprocess和assistant，并建立关联.
     */
    public function importFlowWithAgent(FlowDataIsolation $dataIsolation, array $importData): array
    {
        $agentData = $importData['agent'] ?? [];
        $flowData = $importData['flow'] ?? [];

        if (empty($flowData) || empty($agentData)) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'flow.import.missing_data');
        }

        // 1. 先importprocess
        $mainFlow = $this->importFlow($dataIsolation, $flowData);

        // 2. createnewassistant并关联process
        $agentDomainService = di(DelightfulAgentDomainService::class);

        $agentEntity = new DelightfulAgentEntity();
        $agentEntity->setId('');
        $agentEntity->setAgentName($agentData['name'] ?? ('import的assistant_' . date('YmdHis')));
        $agentEntity->setAgentDescription($agentData['description'] ?? '');
        $agentEntity->setAgentAvatar($agentData['avatar'] ?? '');
        $agentEntity->setOrganizationCode($dataIsolation->getCurrentOrganizationCode());
        $agentEntity->setFlowCode($mainFlow->getCode());
        $agentEntity->setStatus(0);
        $agentEntity->setCreatedUid($dataIsolation->getCurrentUserId());

        try {
            $savedAgent = $agentDomainService->saveAgent($agentEntity);
            $agentDomainService->updateInstruct($dataIsolation->getCurrentOrganizationCode(), $savedAgent->getId(), $agentData['instruct'], $dataIsolation->getCurrentUserId(), false);
            return [
                'agent_id' => $savedAgent->getId(),
                'agent_name' => $savedAgent->getAgentName(),
                'flow_id' => $mainFlow->getCode(),
                'flow_name' => $mainFlow->getName(),
            ];
        } catch (Throwable $e) {
            // 如果createassistantfail，但process已import，仍returnprocessinfo
            return [
                'agent_id' => null,
                'agent_error' => $e->getMessage(),
                'flow_id' => $mainFlow->getCode(),
                'flow_name' => $mainFlow->getName(),
            ];
        }
    }

    /**
     * import单个process
     * generate新ID并checkname重复.
     */
    private function importSingleFlow(FlowDataIsolation $dataIsolation, array $flowData, array &$idMapping, array &$importReport): string
    {
        // recordoriginalname和ID
        $originalName = $flowData['name'] ?? '';
        $originalCode = $flowData['code'] ?? '';

        // generate新ID
        $flowData['code'] = Code::DelightfulFlow->gen();

        // check是否存在同名process，如果存在则重命名
        $flowType = isset($flowData['type']) ? Type::from($flowData['type']) : Type::Main;
        $newName = $this->generateUniqueName($dataIsolation, $originalName, $flowType);
        if ($newName !== $originalName) {
            $flowData['name'] = $newName;
            $importReport['renamed'][] = "process '{$originalName}' 重命名为 '{$newName}'";
        }

        // update节点ID映射
        $this->updateNodeIdsMapping($flowData, $idMapping);

        // handle工具集IDquote
        if (! empty($flowData['tool_set_id']) && $flowData['tool_set_id'] !== 'not_grouped') {
            $oldToolSetId = $flowData['tool_set_id'];
            $newToolSetId = $idMapping['tool_sets'][$oldToolSetId] ?? $oldToolSetId;
            $flowData['tool_set_id'] = $newToolSetId;
        }

        // updateprocess节点中的quote关系
        $this->updateFlowReferences($flowData, $idMapping);

        // updateorganizationinfo
        $flowData['organization_code'] = $dataIsolation->getCurrentOrganizationCode();

        // 保留agentId字段，如果存在的话
        $agentId = $flowData['agent_id'] ?? '';
        // createprocess实体并save
        $flowEntity = DelightfulFlowFactory::arrayToEntity($flowData);
        $flowEntity->setCreator($dataIsolation->getCurrentUserId());
        $flowEntity->setOrganizationCode($dataIsolation->getCurrentOrganizationCode());

        // settingagentId（如果存在）
        if (! empty($agentId)) {
            $flowEntity->setAgentId($agentId);
        }

        // ensuresetting为新建process
        $flowEntity->setId(0); // settingID为0表示新建
        $flowEntity->setId(null);
        $savedFlow = $this->delightfulFlowDomainService->create($dataIsolation, $flowEntity);
        $importReport['created'][] = "createprocess: {$savedFlow->getName()} (ID: {$savedFlow->getCode()})";

        return $savedFlow->getCode();
    }

    /**
     * import工具集
     * generate新ID并checkname重复.
     */
    private function importToolSet(FlowDataIsolation $dataIsolation, array $toolSetData, array &$idMapping, array &$importReport): string
    {
        // recordoriginalname和ID
        $originalName = $toolSetData['name'] ?? '';
        $originalCode = $toolSetData['code'] ?? '';

        // generate新ID
        $toolSetData['code'] = Code::DelightfulFlowToolSet->gen();

        // check是否存在同名工具集，如果存在则重命名
        $newName = $this->generateUniqueToolSetName($dataIsolation, $originalName);
        if ($newName !== $originalName) {
            $toolSetData['name'] = $newName;
            $importReport['renamed'][] = "工具集 '{$originalName}' 重命名为 '{$newName}'";
        }

        // updateorganizationinfo
        $toolSetData['organization_code'] = $dataIsolation->getCurrentOrganizationCode();
        $toolSetData['created_uid'] = $dataIsolation->getCurrentUserId();
        $toolSetData['updated_uid'] = $dataIsolation->getCurrentUserId();

        // 移除可能影响create逻辑的字段
        unset($toolSetData['created_at'], $toolSetData['updated_at'], $toolSetData['id']);

        // settingcreate实体必要的字段
        $toolSetData['id'] = 0; // ensuresetting为新建
        $toolSetData['created_at'] = new DateTime();
        $toolSetData['updated_at'] = new DateTime();

        // use工厂methodcreate工具集实体
        $toolSetEntity = DelightfulFlowToolSetFactory::arrayToEntity($toolSetData);

        // save工具集
        $savedToolSet = $this->delightfulFlowToolSetDomainService->create($dataIsolation, $toolSetEntity);
        $importReport['created'][] = "create工具集: {$savedToolSet->getName()} (ID: {$savedToolSet->getCode()})";

        // record新旧ID的映射关系
        $idMapping['tool_sets'][$originalCode] = $savedToolSet->getCode();

        return $savedToolSet->getCode();
    }

    /**
     * generate唯一的processname
     * 当检测到同名process时，添加(n)后缀
     */
    private function generateUniqueName(FlowDataIsolation $dataIsolation, string $name, Type $type): string
    {
        $newName = $name;
        $counter = 1;

        // 工具不用重名，因为工具集不一样
        if ($type === Type::Tools) {
            return $name;
        }
        // check是否存在同名process
        while ($this->delightfulFlowDomainService->getByName($dataIsolation, $newName, $type)) {
            $newName = "{$name}__{$counter}";
            ++$counter;
        }

        return $newName;
    }

    /**
     * generate唯一的工具集name
     * 当检测到同名工具集时，添加(n)后缀
     */
    private function generateUniqueToolSetName(FlowDataIsolation $dataIsolation, string $name): string
    {
        $newName = $name;
        $counter = 1;

        // usequeryobjectcheck是否存在同名工具集
        while (true) {
            $query = new DelightfulFlowToolSetQuery();
            $query->setName($newName);
            $result = $this->delightfulFlowToolSetDomainService->queries($dataIsolation, $query, new Page(1, 100));

            $exists = false;
            foreach ($result['list'] as $toolSet) {
                if ($toolSet->getName() === $newName) {
                    $exists = true;
                    break;
                }
            }

            if ($exists) {
                $newName = "{$name}({$counter})";
                ++$counter;
            } else {
                break;
            }
        }

        return $newName;
    }

    /**
     * update节点ID映射
     * 为所有节点generate新ID并维护映射关系.
     */
    private function updateNodeIdsMapping(array &$flowData, array &$idMapping): void
    {
        if (empty($flowData['nodes'])) {
            return;
        }

        foreach ($flowData['nodes'] as &$nodeData) {
            $oldNodeId = $nodeData['node_id'] ?? '';
            if (! $oldNodeId) {
                continue;
            }

            // generatenew节点ID
            $newNodeId = IdGenerator::getUniqueId32();
            $idMapping['nodes'][$oldNodeId] = $newNodeId;
            $nodeData['node_id'] = $newNodeId;
        }
    }

    /**
     * updateprocess中的quote关系
     * include节点quote、子processquote、工具quote等.
     */
    private function updateFlowReferences(array &$flowData, array $idMapping): void
    {
        if (empty($flowData['nodes'])) {
            return;
        }

        foreach ($flowData['nodes'] as &$nodeData) {
            $nodeType = $nodeData['node_type'] ?? 0;

            // update节点parameter中的quote
            if (! empty($nodeData['params'])) {
                // 子process节点
                if ($nodeType === NodeType::Sub->value) {
                    // update子processquote
                    if (isset($nodeData['params']['sub_flow_id'])) {
                        $oldSubFlowId = $nodeData['params']['sub_flow_id'];
                        $nodeData['params']['sub_flow_id'] = $idMapping['flows'][$oldSubFlowId] ?? $oldSubFlowId;
                    }
                }

                // handletype26的工具节点直接quote
                if ($nodeType === NodeType::Tool->value) {
                    if (isset($nodeData['params']['tool_id'])) {
                        $oldToolId = $nodeData['params']['tool_id'];
                        $newToolId = $idMapping['flows'][$oldToolId] ?? $oldToolId;
                        $nodeData['params']['tool_id'] = $newToolId;
                    }
                }

                // 工具节点或LLM节点
                if ($nodeType === NodeType::Tool->value || $nodeType === NodeType::LLM->value) {
                    // update工具quote
                    if (isset($nodeData['params']['option_tools']) && is_array($nodeData['params']['option_tools'])) {
                        foreach ($nodeData['params']['option_tools'] as &$optionTool) {
                            if (isset($optionTool['tool_id'])) {
                                $oldToolId = $optionTool['tool_id'];
                                $optionTool['tool_id'] = $idMapping['flows'][$oldToolId] ?? $oldToolId;
                            }
                            if (isset($optionTool['tool_set_id']) && $optionTool['tool_set_id'] !== 'not_grouped') {
                                $oldToolSetId = $optionTool['tool_set_id'];
                                $optionTool['tool_set_id'] = $idMapping['tool_sets'][$oldToolSetId] ?? $oldToolSetId;
                            }
                        }
                    }
                }

                // handleparameter中的表达式
                $this->updateExpressionReferences($nodeData['params'], $idMapping);
            }

            // 通用handle input 字段 (如果存在且为array)
            if (isset($nodeData['input']) && is_array($nodeData['input'])) {
                $this->processSpecialNodeFieldValue($nodeData['input'], $idMapping);
            }

            // 通用handle output 字段 (如果存在且为array)
            if (isset($nodeData['output']) && is_array($nodeData['output'])) {
                $this->processSpecialNodeFieldValue($nodeData['output'], $idMapping);
            }

            // update前置节点quote
            if (isset($nodeData['prev_nodes']) && is_array($nodeData['prev_nodes'])) {
                $prevNodes = [];
                foreach ($nodeData['prev_nodes'] as $prevNodeId) {
                    $newPrevNodeId = $idMapping['nodes'][$prevNodeId] ?? $prevNodeId;
                    $prevNodes[] = $newPrevNodeId;
                }
                $nodeData['prev_nodes'] = $prevNodes;
            }

            // update后续节点quote
            if (isset($nodeData['next_nodes']) && is_array($nodeData['next_nodes'])) {
                $nextNodes = [];
                foreach ($nodeData['next_nodes'] as $nextNodeId) {
                    $newNextNodeId = $idMapping['nodes'][$nextNodeId] ?? $nextNodeId;
                    $nextNodes[] = $newNextNodeId;
                }
                $nodeData['next_nodes'] = $nextNodes;
            }
        }

        // handleedges中的source和targetquote
        if (isset($flowData['edges']) && is_array($flowData['edges'])) {
            foreach ($flowData['edges'] as &$edge) {
                // updatesourcequote
                if (isset($edge['source'])) {
                    $oldSourceId = $edge['source'];
                    $newSourceId = $idMapping['nodes'][$oldSourceId] ?? $oldSourceId;
                    $edge['source'] = $newSourceId;
                }

                // updatetargetquote
                if (isset($edge['target'])) {
                    $oldTargetId = $edge['target'];
                    $newTargetId = $idMapping['nodes'][$oldTargetId] ?? $oldTargetId;
                    $edge['target'] = $newTargetId;
                }

                // updatesourceHandle中可能contain的节点IDquote
                if (isset($edge['sourceHandle']) && is_string($edge['sourceHandle'])) {
                    foreach ($idMapping['nodes'] as $oldId => $newId) {
                        // ensureoldId是stringtype
                        $oldIdStr = (string) $oldId;
                        $newIdStr = (string) $newId;

                        // use正则表达式ensure只替换完整的ID
                        if (preg_match('/^' . preg_quote($oldIdStr, '/') . '_/', $edge['sourceHandle'])) {
                            $edge['sourceHandle'] = preg_replace('/^' . preg_quote($oldIdStr, '/') . '/', $newIdStr, $edge['sourceHandle']);
                        }
                    }
                }

                // updateedge的ID（如果有）
                if (isset($edge['id'])) {
                    $edge['id'] = IdGenerator::getUniqueId32();
                }
            }
        }
    }

    /**
     * 递归handlearray中的表达式quote
     * 查找并update所有contain节点ID的表达式字段.
     */
    private function updateExpressionReferences(array &$data, array $idMapping): void
    {
        foreach ($data as &$item) {
            if (is_array($item)) {
                // 递归handle嵌套array
                $this->updateExpressionReferences($item, $idMapping);
            } elseif (is_string($item)) {
                // 跳过指令quote（instructions.*）
                if (strpos($item, 'instructions.') === 0) {
                    continue;
                }

                // check是否contain节点IDquote（format如：nodeId.fieldName）
                foreach ($idMapping['nodes'] as $oldNodeId => $newNodeId) {
                    // ensureID是stringtype
                    $oldNodeIdStr = (string) $oldNodeId;
                    $newNodeIdStr = (string) $newNodeId;

                    // use正则表达式ensure只替换完整的节点ID
                    if (preg_match('/^' . preg_quote($oldNodeIdStr, '/') . '\./', $item)) {
                        $fieldName = substr($item, strlen($oldNodeIdStr));
                        $item = $newNodeIdStr . $fieldName;
                        break; // 找到匹配后退出循环
                    }
                }
            }
        }

        // handleobject形式的表达式value（如form结构中的field）
        if (isset($data['field'])) {
            $field = $data['field'];
            if (is_string($field)) {
                // 跳过指令quote
                if (strpos($field, 'instructions.') === 0) {
                    return;
                }

                // check是否contain节点IDquote
                foreach ($idMapping['nodes'] as $oldNodeId => $newNodeId) {
                    $oldNodeIdStr = (string) $oldNodeId;
                    $newNodeIdStr = (string) $newNodeId;

                    if (preg_match('/^' . preg_quote($oldNodeIdStr, '/') . '\./', $field)) {
                        $fieldName = substr($field, strlen($oldNodeIdStr));
                        $data['field'] = $newNodeIdStr . $fieldName;
                        break;
                    }
                }
            }
        }
    }

    /**
     * 判断是否为内置工具
     * 内置工具不need重新create，can直接use.
     */
    private function isBuiltInTool(string $toolId, string $toolSetId): bool
    {
        // 常见的内置工具集前缀
        $builtInToolSetPrefixes = [
            'file_box',      // file盒工具集
            'search_engine', // search引擎工具集
            'web_browse',    // 网页浏览工具集
            'system',        // 系统工具集
            'knowledge',     // knowledge base工具集
        ];

        // 判断是否属于内置工具集
        foreach ($builtInToolSetPrefixes as $prefix) {
            if ($toolSetId === $prefix || strpos($toolSetId, $prefix . '_') === 0) {
                return true;
            }
        }

        // 判断工具ID是否以工具集ID开头，这是内置工具的常见模式
        if (! empty($toolSetId) && strpos($toolId, $toolSetId . '_') === 0) {
            return true;
        }

        // getconfiguration中的内置工具列表（如果有）
        $builtInTools = $this->config->get('flow.built_in_tools', []);
        if (in_array($toolId, $builtInTools)) {
            return true;
        }

        return false;
    }

    /**
     * 关联process与assistant
     * 在importprocess后将其与指定的assistant关联.
     */
    private function associateFlowWithAgent(FlowDataIsolation $dataIsolation, string $flowCode, string $agentId): void
    {
        if (empty($agentId) || empty($flowCode)) {
            return;
        }

        $agentDomainService = di(DelightfulAgentDomainService::class);
        // settingprocesscode并saveassistant
        $agentDomainService->associateFlowWithAgent($agentId, $flowCode);
    }

    /**
     * handle特殊的表达式value字段.
     */
    private function processSpecialNodeFieldValue(array &$value, array $idMapping): void
    {
        foreach ($value as $key => &$item) {
            if (is_array($item)) {
                // 递归handle嵌套array
                $this->processSpecialNodeFieldValue($item, $idMapping);
            } elseif (is_string($item)) {
                // handlestring中的节点IDquote
                $this->updateStringNodeReference($item, $idMapping);
            }
        }

        // 特殊handleconst_valuearray中的object
        if (isset($value['const_value']) && is_array($value['const_value'])) {
            foreach ($value['const_value'] as &$constItem) {
                if (is_array($constItem)) {
                    // handleobject形式的const_value项
                    if (isset($constItem['value']) && is_string($constItem['value'])) {
                        $this->updateStringNodeReference($constItem['value'], $idMapping);
                    }
                    // 递归handle其他字段
                    $this->processSpecialNodeFieldValue($constItem, $idMapping);
                } elseif (is_string($constItem)) {
                    // handlestring形式的const_value项
                    $this->updateStringNodeReference($constItem, $idMapping);
                }
            }
        }

        // handleexpression_value中的quote
        if (isset($value['expression_value']) && is_array($value['expression_value'])) {
            $this->processExpressionValue($value['expression_value'], $idMapping);
        }
    }

    /**
     * updatestring中的节点IDquote.
     */
    private function updateStringNodeReference(string &$str, array $idMapping): void
    {
        // 跳过指令quote（instructions.*）
        if (strpos($str, 'instructions.') === 0) {
            return;
        }

        // check是否contain节点IDquote（format如：nodeId.fieldName）
        foreach ($idMapping['nodes'] as $oldNodeId => $newNodeId) {
            $oldNodeIdStr = (string) $oldNodeId;
            $newNodeIdStr = (string) $newNodeId;

            // use正则表达式ensure只替换完整的节点ID
            if (preg_match('/^' . preg_quote($oldNodeIdStr, '/') . '\./', $str)) {
                $fieldName = substr($str, strlen($oldNodeIdStr));
                $str = $newNodeIdStr . $fieldName;
                break; // 找到匹配后退出循环
            }
        }
    }

    /**
     * handle表达式value中的节点quote.
     */
    private function processExpressionValue(array &$expressionValue, array $idMapping): void
    {
        foreach ($expressionValue as &$item) {
            if (is_array($item)) {
                // 递归handle嵌套array
                $this->processExpressionValue($item, $idMapping);
            } elseif (is_string($item)) {
                // handlestring中的节点IDquote
                $this->updateStringNodeReference($item, $idMapping);
            }
        }

        // handleobject形式的表达式value（如form结构中的field）
        if (isset($expressionValue['field'])) {
            $field = $expressionValue['field'];
            if (is_string($field)) {
                $this->updateStringNodeReference($field, $idMapping);
                $expressionValue['field'] = $field;
            }
        }

        // handle嵌套的value字段
        if (isset($expressionValue['value']) && is_array($expressionValue['value'])) {
            $this->processExpressionValue($expressionValue['value'], $idMapping);
        }

        // handleconst_valuetype的嵌套结构
        if (isset($expressionValue['const_value']) && is_array($expressionValue['const_value'])) {
            $this->processExpressionValue($expressionValue['const_value'], $idMapping);
        }

        // handleexpression_valuetype的嵌套结构
        if (isset($expressionValue['expression_value']) && is_array($expressionValue['expression_value'])) {
            $this->processExpressionValue($expressionValue['expression_value'], $idMapping);
        }

        // handleform结构中的fieldarray
        if (isset($expressionValue['form']) && is_array($expressionValue['form'])) {
            foreach ($expressionValue['form'] as &$formItem) {
                if (isset($formItem['field']) && is_string($formItem['field'])) {
                    $this->updateStringNodeReference($formItem['field'], $idMapping);
                }

                // 递归handleformItem中的其他可能字段
                if (is_array($formItem)) {
                    $this->updateExpressionReferences($formItem, $idMapping);
                }
            }
        }
    }

    /**
     * 递归handleprocess中的子process和工具quote.
     */
    private function processFlowForExport(
        FlowDataIsolation $dataIsolation,
        DelightfulFlowEntity $flow,
        array &$exportData,
        array &$processedFlowCodes,
        array &$processedToolSetIds
    ): void {
        // 1. handle工具集
        $this->processToolSet($dataIsolation, $flow, $exportData, $processedToolSetIds);

        // 2. handle子process节点
        $this->processSubFlowNodes($dataIsolation, $flow, $exportData, $processedFlowCodes, $processedToolSetIds);

        // 3. handle工具节点
        $this->processToolNodes($dataIsolation, $flow, $exportData, $processedFlowCodes, $processedToolSetIds);
    }

    /**
     * handle工具集.
     */
    private function processToolSet(
        FlowDataIsolation $dataIsolation,
        DelightfulFlowEntity $flow,
        array &$exportData,
        array &$processedToolSetIds
    ): void {
        $toolSetId = $flow->getToolSetId();
        // 跳过官方工具(not_grouped)和已handle的工具集
        if (empty($toolSetId) || $toolSetId === 'not_grouped' || in_array($toolSetId, $processedToolSetIds)) {
            return;
        }

        // get工具集info
        $toolSet = $this->delightfulFlowToolSetDomainService->getByCode($dataIsolation, $toolSetId);
        // mark为已handle
        $processedToolSetIds[] = $toolSetId;

        // 添加到exportdata中
        $exportData['tool_sets'][$toolSetId] = $toolSet->toArray();
    }

    /**
     * handle子process节点.
     */
    private function processSubFlowNodes(
        FlowDataIsolation $dataIsolation,
        DelightfulFlowEntity $flow,
        array &$exportData,
        array &$processedFlowCodes,
        array &$processedToolSetIds
    ): void {
        foreach ($flow->getNodes() as $node) {
            // 如果是子process节点
            if ($node->getNodeType() === NodeType::Sub->value) {
                $subFlowId = $node->getParams()['sub_flow_id'] ?? '';
                // 跳过nullID和已handle的子process
                if (! $subFlowId || in_array($subFlowId, $processedFlowCodes)) {
                    continue;
                }

                // get子process
                $subFlow = $this->delightfulFlowDomainService->getByCode($dataIsolation, $subFlowId);
                if (! $subFlow || $subFlow->getType() !== Type::Sub) {
                    // 子process不存在或type不correct，跳过但不报错
                    continue;
                }

                // mark为已handle
                $processedFlowCodes[] = $subFlowId;

                // 添加到exportdata中
                $exportData['sub_flows'][$subFlowId] = $subFlow->toArray();

                // 递归handle子process中的子process和工具
                $this->processFlowForExport($dataIsolation, $subFlow, $exportData, $processedFlowCodes, $processedToolSetIds);
            }
        }
    }

    /**
     * handle工具节点和LLM节点中的option_tools.
     */
    private function processToolNodes(
        FlowDataIsolation $dataIsolation,
        DelightfulFlowEntity $flow,
        array &$exportData,
        array &$processedFlowCodes,
        array &$processedToolSetIds
    ): void {
        foreach ($flow->getNodes() as $node) {
            // handle节点type26（直接在params中有tool_id的工具节点）
            if ($node->getNodeType() === NodeType::Tool->value) {
                $params = $node->getParams();
                $toolId = $params['tool_id'] ?? '';

                if (! $toolId || in_array($toolId, $processedFlowCodes)) {
                    continue;
                }

                // get工具process
                $toolFlow = $this->delightfulFlowDomainService->getByCode($dataIsolation, $toolId);
                if (! $toolFlow) {
                    continue;
                }

                // mark为已handle
                $processedFlowCodes[] = $toolId;

                // 添加到exportdata中
                $exportData['tool_flows'][$toolId] = $toolFlow->toArray();

                // 递归handle
                $this->processFlowForExport($dataIsolation, $toolFlow, $exportData, $processedFlowCodes, $processedToolSetIds);
            }

            // 主要checkLLM和Tool节点
            if ($node->getNodeType() === NodeType::LLM->value || $node->getNodeType() === NodeType::Tool->value) {
                $params = $node->getParams();
                if (isset($params['option_tools']) && is_array($params['option_tools'])) {
                    foreach ($params['option_tools'] as $optionToolData) {
                        $toolId = $optionToolData['tool_id'] ?? '';
                        $toolSetId = $optionToolData['tool_set_id'] ?? '';

                        // 判断是否为内置工具，内置工具直接跳过
                        if ($this->isBuiltInTool($toolId, $toolSetId)) {
                            continue;
                        }

                        // handle工具集quote
                        if (! empty($toolSetId) && $toolSetId !== 'not_grouped' && ! in_array($toolSetId, $processedToolSetIds)) {
                            $toolSet = $this->delightfulFlowToolSetDomainService->getByCode($dataIsolation, $toolSetId);
                            $processedToolSetIds[] = $toolSetId;
                            $exportData['tool_sets'][$toolSetId] = $toolSet->toArray();
                        }

                        // handle工具processquote
                        if (! $toolId || in_array($toolId, $processedFlowCodes)) {
                            continue;
                        }

                        // get工具process
                        $toolFlow = $this->delightfulFlowDomainService->getByCode($dataIsolation, $toolId);
                        if (! $toolFlow || $toolFlow->getType() !== Type::Tools) {
                            // 工具process不存在或type不correct，跳过但不报错
                            continue;
                        }

                        // mark为已handle
                        $processedFlowCodes[] = $toolId;

                        // 添加到exportdata中
                        $exportData['tool_flows'][$toolId] = $toolFlow->toArray();

                        // 递归handle工具process中的子process和其他工具
                        $this->processFlowForExport($dataIsolation, $toolFlow, $exportData, $processedFlowCodes, $processedToolSetIds);
                    }
                }
            }
        }
    }
}
