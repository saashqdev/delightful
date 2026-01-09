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
     * 递归exportprocess相关的所have节点，include子process和toolprocess.
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

        // checkwhether存in循环dependency
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

        // 递归handle主process中的子process和tool
        $this->processFlowForExport($dataIsolation, $mainFlow, $exportData, $processedFlowCodes, $processedToolSetIds);

        return $exportData;
    }

    /**
     * importassistantprocess
     * 遇to重复的toolorprocesswillcreate新实例，并passname区分.
     */
    public function importFlow(FlowDataIsolation $dataIsolation, array $importData, string $agentId = ''): DelightfulFlowEntity
    {
        // import主process
        $mainFlowData = $importData['main_flow'] ?? null;
        if (! $mainFlowData) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'flow.import.missing_main_flow');
        }

        // storage新旧IDmapping关系
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

        // 1. 先importtool集
        if (! empty($importData['tool_sets'])) {
            foreach ($importData['tool_sets'] as $toolSetId => $toolSetData) {
                try {
                    $newToolSetId = $this->importToolSet($dataIsolation, $toolSetData, $idMapping, $importReport);
                    $idMapping['tool_sets'][$toolSetId] = $newToolSetId;
                } catch (Throwable $e) {
                    $importReport['errors'][] = "importtool集 {$toolSetData['name']} fail: {$e->getMessage()}";
                }
            }
        }

        // 2. importtoolprocess
        if (! empty($importData['tool_flows'])) {
            foreach ($importData['tool_flows'] as $toolFlowId => $toolFlowData) {
                try {
                    $newFlowId = $this->importSingleFlow($dataIsolation, $toolFlowData, $idMapping, $importReport);
                    $idMapping['flows'][$toolFlowId] = $newFlowId;
                } catch (Throwable $e) {
                    $importReport['errors'][] = "importtoolprocess {$toolFlowData['name']} fail: {$e->getMessage()}";
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

        // 4. most后import主process，并associateto指定assistant（if提供了agentId）
        try {
            // if提供了agentId，settingto主processdata中
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
        // associateprocess与assistant
        if (! empty($agentId)) {
            $this->associateFlowWithAgent($dataIsolation, $mainFlow->getCode(), $agentId);
        }
        return $mainFlow;
    }

    /**
     * validatewhether存in循环dependency
     * use深度优先search检测循环quote.
     */
    public function checkCircularDependency(FlowDataIsolation $dataIsolation, string $flowCode, array $visited = []): bool
    {
        // ifcurrentprocess已inaccesspath中，instruction形成了循环
        if (in_array($flowCode, $visited)) {
            return true; // 发现循环dependency
        }

        // 将currentprocess添加toaccesspath
        $visited[] = $flowCode;

        // getprocess实体
        $flow = $this->delightfulFlowDomainService->getByCode($dataIsolation, $flowCode);
        if (! $flow) {
            return false; // processnot存in，not构成循环
        }

        // 遍历所have节点checkdependency
        foreach ($flow->getNodes() as $node) {
            // check子process节点
            if ($node->getNodeType() === NodeType::Sub->value) {
                $subFlowId = $node->getParams()['sub_flow_id'] ?? '';
                if ($subFlowId && $this->checkCircularDependency($dataIsolation, $subFlowId, $visited)) {
                    return true; // 子process中存in循环dependency
                }
            }

            // checkLLM节点中的toolquote
            if ($node->getNodeType() === NodeType::LLM->value || $node->getNodeType() === NodeType::Tool->value) {
                $params = $node->getParams();
                if (isset($params['option_tools']) && is_array($params['option_tools'])) {
                    foreach ($params['option_tools'] as $optionTool) {
                        $toolId = $optionTool['tool_id'] ?? '';
                        $toolSetId = $optionTool['tool_set_id'] ?? '';

                        // 内置toolskip循环dependencycheck
                        if ($toolId && ! $this->isBuiltInTool($toolId, $toolSetId) && $this->checkCircularDependency($dataIsolation, $toolId, $visited)) {
                            return true; // toolquote中存in循环dependency
                        }
                    }
                }
            }
        }

        return false; // nothave检测to循环dependency
    }

    /**
     * exportprocess和assistantinfo
     * containprocess的所havedataby及assistant的基本info.
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
     * fromexport的data中createnewprocess和assistant，并建立associate.
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

        // 2. createnewassistant并associateprocess
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
            // ifcreateassistantfail，butprocess已import，仍returnprocessinfo
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

        // checkwhether存in同名process，if存inthen重命名
        $flowType = isset($flowData['type']) ? Type::from($flowData['type']) : Type::Main;
        $newName = $this->generateUniqueName($dataIsolation, $originalName, $flowType);
        if ($newName !== $originalName) {
            $flowData['name'] = $newName;
            $importReport['renamed'][] = "process '{$originalName}' 重命名为 '{$newName}'";
        }

        // update节点IDmapping
        $this->updateNodeIdsMapping($flowData, $idMapping);

        // handletool集IDquote
        if (! empty($flowData['tool_set_id']) && $flowData['tool_set_id'] !== 'not_grouped') {
            $oldToolSetId = $flowData['tool_set_id'];
            $newToolSetId = $idMapping['tool_sets'][$oldToolSetId] ?? $oldToolSetId;
            $flowData['tool_set_id'] = $newToolSetId;
        }

        // updateprocess节点中的quote关系
        $this->updateFlowReferences($flowData, $idMapping);

        // updateorganizationinfo
        $flowData['organization_code'] = $dataIsolation->getCurrentOrganizationCode();

        // 保留agentIdfield，if存in的话
        $agentId = $flowData['agent_id'] ?? '';
        // createprocess实体并save
        $flowEntity = DelightfulFlowFactory::arrayToEntity($flowData);
        $flowEntity->setCreator($dataIsolation->getCurrentUserId());
        $flowEntity->setOrganizationCode($dataIsolation->getCurrentOrganizationCode());

        // settingagentId（if存in）
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
     * importtool集
     * generate新ID并checkname重复.
     */
    private function importToolSet(FlowDataIsolation $dataIsolation, array $toolSetData, array &$idMapping, array &$importReport): string
    {
        // recordoriginalname和ID
        $originalName = $toolSetData['name'] ?? '';
        $originalCode = $toolSetData['code'] ?? '';

        // generate新ID
        $toolSetData['code'] = Code::DelightfulFlowToolSet->gen();

        // checkwhether存in同名tool集，if存inthen重命名
        $newName = $this->generateUniqueToolSetName($dataIsolation, $originalName);
        if ($newName !== $originalName) {
            $toolSetData['name'] = $newName;
            $importReport['renamed'][] = "tool集 '{$originalName}' 重命名为 '{$newName}'";
        }

        // updateorganizationinfo
        $toolSetData['organization_code'] = $dataIsolation->getCurrentOrganizationCode();
        $toolSetData['created_uid'] = $dataIsolation->getCurrentUserId();
        $toolSetData['updated_uid'] = $dataIsolation->getCurrentUserId();

        // 移except可能影响create逻辑的field
        unset($toolSetData['created_at'], $toolSetData['updated_at'], $toolSetData['id']);

        // settingcreate实体必要的field
        $toolSetData['id'] = 0; // ensuresetting为新建
        $toolSetData['created_at'] = new DateTime();
        $toolSetData['updated_at'] = new DateTime();

        // use工厂methodcreatetool集实体
        $toolSetEntity = DelightfulFlowToolSetFactory::arrayToEntity($toolSetData);

        // savetool集
        $savedToolSet = $this->delightfulFlowToolSetDomainService->create($dataIsolation, $toolSetEntity);
        $importReport['created'][] = "createtool集: {$savedToolSet->getName()} (ID: {$savedToolSet->getCode()})";

        // record新旧ID的mapping关系
        $idMapping['tool_sets'][$originalCode] = $savedToolSet->getCode();

        return $savedToolSet->getCode();
    }

    /**
     * generate唯一的processname
     * when检测to同名process时，添加(n)后缀
     */
    private function generateUniqueName(FlowDataIsolation $dataIsolation, string $name, Type $type): string
    {
        $newName = $name;
        $counter = 1;

        // toolnotuse重名，因为tool集notsame
        if ($type === Type::Tools) {
            return $name;
        }
        // checkwhether存in同名process
        while ($this->delightfulFlowDomainService->getByName($dataIsolation, $newName, $type)) {
            $newName = "{$name}__{$counter}";
            ++$counter;
        }

        return $newName;
    }

    /**
     * generate唯一的tool集name
     * when检测to同名tool集时，添加(n)后缀
     */
    private function generateUniqueToolSetName(FlowDataIsolation $dataIsolation, string $name): string
    {
        $newName = $name;
        $counter = 1;

        // usequeryobjectcheckwhether存in同名tool集
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
     * update节点IDmapping
     * 为所have节点generate新ID并维护mapping关系.
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
     * include节点quote、子processquote、toolquoteetc.
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

                // handletype26的tool节点直接quote
                if ($nodeType === NodeType::Tool->value) {
                    if (isset($nodeData['params']['tool_id'])) {
                        $oldToolId = $nodeData['params']['tool_id'];
                        $newToolId = $idMapping['flows'][$oldToolId] ?? $oldToolId;
                        $nodeData['params']['tool_id'] = $newToolId;
                    }
                }

                // tool节点orLLM节点
                if ($nodeType === NodeType::Tool->value || $nodeType === NodeType::LLM->value) {
                    // updatetoolquote
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

            // 通usehandle input field (if存inand为array)
            if (isset($nodeData['input']) && is_array($nodeData['input'])) {
                $this->processSpecialNodeFieldValue($nodeData['input'], $idMapping);
            }

            // 通usehandle output field (if存inand为array)
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

                        // use正then表达式ensure只替换完整的ID
                        if (preg_match('/^' . preg_quote($oldIdStr, '/') . '_/', $edge['sourceHandle'])) {
                            $edge['sourceHandle'] = preg_replace('/^' . preg_quote($oldIdStr, '/') . '/', $newIdStr, $edge['sourceHandle']);
                        }
                    }
                }

                // updateedge的ID（ifhave）
                if (isset($edge['id'])) {
                    $edge['id'] = IdGenerator::getUniqueId32();
                }
            }
        }
    }

    /**
     * 递归handlearray中的表达式quote
     * 查找并update所havecontain节点ID的表达式field.
     */
    private function updateExpressionReferences(array &$data, array $idMapping): void
    {
        foreach ($data as &$item) {
            if (is_array($item)) {
                // 递归handle嵌套array
                $this->updateExpressionReferences($item, $idMapping);
            } elseif (is_string($item)) {
                // skip指令quote（instructions.*）
                if (strpos($item, 'instructions.') === 0) {
                    continue;
                }

                // checkwhethercontain节点IDquote（format如：nodeId.fieldName）
                foreach ($idMapping['nodes'] as $oldNodeId => $newNodeId) {
                    // ensureID是stringtype
                    $oldNodeIdStr = (string) $oldNodeId;
                    $newNodeIdStr = (string) $newNodeId;

                    // use正then表达式ensure只替换完整的节点ID
                    if (preg_match('/^' . preg_quote($oldNodeIdStr, '/') . '\./', $item)) {
                        $fieldName = substr($item, strlen($oldNodeIdStr));
                        $item = $newNodeIdStr . $fieldName;
                        break; // 找to匹配后exit循环
                    }
                }
            }
        }

        // handleobject形式的表达式value（如form结构中的field）
        if (isset($data['field'])) {
            $field = $data['field'];
            if (is_string($field)) {
                // skip指令quote
                if (strpos($field, 'instructions.') === 0) {
                    return;
                }

                // checkwhethercontain节点IDquote
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
     * 判断whether为内置tool
     * 内置toolnotneed重新create，can直接use.
     */
    private function isBuiltInTool(string $toolId, string $toolSetId): bool
    {
        // 常见的内置tool集前缀
        $builtInToolSetPrefixes = [
            'file_box',      // file盒tool集
            'search_engine', // searchenginetool集
            'web_browse',    // 网页浏览tool集
            'system',        // 系统tool集
            'knowledge',     // knowledge basetool集
        ];

        // 判断whether属at内置tool集
        foreach ($builtInToolSetPrefixes as $prefix) {
            if ($toolSetId === $prefix || strpos($toolSetId, $prefix . '_') === 0) {
                return true;
            }
        }

        // 判断toolIDwhetherbytool集ID开头，这是内置tool的常见模式
        if (! empty($toolSetId) && strpos($toolId, $toolSetId . '_') === 0) {
            return true;
        }

        // getconfiguration中的内置tool列表（ifhave）
        $builtInTools = $this->config->get('flow.built_in_tools', []);
        if (in_array($toolId, $builtInTools)) {
            return true;
        }

        return false;
    }

    /**
     * associateprocess与assistant
     * inimportprocess后将其与指定的assistantassociate.
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
     * handle特殊的表达式valuefield.
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
                    // 递归handle其他field
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
        // skip指令quote（instructions.*）
        if (strpos($str, 'instructions.') === 0) {
            return;
        }

        // checkwhethercontain节点IDquote（format如：nodeId.fieldName）
        foreach ($idMapping['nodes'] as $oldNodeId => $newNodeId) {
            $oldNodeIdStr = (string) $oldNodeId;
            $newNodeIdStr = (string) $newNodeId;

            // use正then表达式ensure只替换完整的节点ID
            if (preg_match('/^' . preg_quote($oldNodeIdStr, '/') . '\./', $str)) {
                $fieldName = substr($str, strlen($oldNodeIdStr));
                $str = $newNodeIdStr . $fieldName;
                break; // 找to匹配后exit循环
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

        // handle嵌套的valuefield
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

                // 递归handleformItem中的其他可能field
                if (is_array($formItem)) {
                    $this->updateExpressionReferences($formItem, $idMapping);
                }
            }
        }
    }

    /**
     * 递归handleprocess中的子process和toolquote.
     */
    private function processFlowForExport(
        FlowDataIsolation $dataIsolation,
        DelightfulFlowEntity $flow,
        array &$exportData,
        array &$processedFlowCodes,
        array &$processedToolSetIds
    ): void {
        // 1. handletool集
        $this->processToolSet($dataIsolation, $flow, $exportData, $processedToolSetIds);

        // 2. handle子process节点
        $this->processSubFlowNodes($dataIsolation, $flow, $exportData, $processedFlowCodes, $processedToolSetIds);

        // 3. handletool节点
        $this->processToolNodes($dataIsolation, $flow, $exportData, $processedFlowCodes, $processedToolSetIds);
    }

    /**
     * handletool集.
     */
    private function processToolSet(
        FlowDataIsolation $dataIsolation,
        DelightfulFlowEntity $flow,
        array &$exportData,
        array &$processedToolSetIds
    ): void {
        $toolSetId = $flow->getToolSetId();
        // skip官方tool(not_grouped)和已handle的tool集
        if (empty($toolSetId) || $toolSetId === 'not_grouped' || in_array($toolSetId, $processedToolSetIds)) {
            return;
        }

        // gettool集info
        $toolSet = $this->delightfulFlowToolSetDomainService->getByCode($dataIsolation, $toolSetId);
        // mark为已handle
        $processedToolSetIds[] = $toolSetId;

        // 添加toexportdata中
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
            // if是子process节点
            if ($node->getNodeType() === NodeType::Sub->value) {
                $subFlowId = $node->getParams()['sub_flow_id'] ?? '';
                // skipnullID和已handle的子process
                if (! $subFlowId || in_array($subFlowId, $processedFlowCodes)) {
                    continue;
                }

                // get子process
                $subFlow = $this->delightfulFlowDomainService->getByCode($dataIsolation, $subFlowId);
                if (! $subFlow || $subFlow->getType() !== Type::Sub) {
                    // 子processnot存inortypenotcorrect，skipbutnot报错
                    continue;
                }

                // mark为已handle
                $processedFlowCodes[] = $subFlowId;

                // 添加toexportdata中
                $exportData['sub_flows'][$subFlowId] = $subFlow->toArray();

                // 递归handle子process中的子process和tool
                $this->processFlowForExport($dataIsolation, $subFlow, $exportData, $processedFlowCodes, $processedToolSetIds);
            }
        }
    }

    /**
     * handletool节点和LLM节点中的option_tools.
     */
    private function processToolNodes(
        FlowDataIsolation $dataIsolation,
        DelightfulFlowEntity $flow,
        array &$exportData,
        array &$processedFlowCodes,
        array &$processedToolSetIds
    ): void {
        foreach ($flow->getNodes() as $node) {
            // handle节点type26（直接inparams中havetool_id的tool节点）
            if ($node->getNodeType() === NodeType::Tool->value) {
                $params = $node->getParams();
                $toolId = $params['tool_id'] ?? '';

                if (! $toolId || in_array($toolId, $processedFlowCodes)) {
                    continue;
                }

                // gettoolprocess
                $toolFlow = $this->delightfulFlowDomainService->getByCode($dataIsolation, $toolId);
                if (! $toolFlow) {
                    continue;
                }

                // mark为已handle
                $processedFlowCodes[] = $toolId;

                // 添加toexportdata中
                $exportData['tool_flows'][$toolId] = $toolFlow->toArray();

                // 递归handle
                $this->processFlowForExport($dataIsolation, $toolFlow, $exportData, $processedFlowCodes, $processedToolSetIds);
            }

            // maincheckLLM和Tool节点
            if ($node->getNodeType() === NodeType::LLM->value || $node->getNodeType() === NodeType::Tool->value) {
                $params = $node->getParams();
                if (isset($params['option_tools']) && is_array($params['option_tools'])) {
                    foreach ($params['option_tools'] as $optionToolData) {
                        $toolId = $optionToolData['tool_id'] ?? '';
                        $toolSetId = $optionToolData['tool_set_id'] ?? '';

                        // 判断whether为内置tool，内置tool直接skip
                        if ($this->isBuiltInTool($toolId, $toolSetId)) {
                            continue;
                        }

                        // handletool集quote
                        if (! empty($toolSetId) && $toolSetId !== 'not_grouped' && ! in_array($toolSetId, $processedToolSetIds)) {
                            $toolSet = $this->delightfulFlowToolSetDomainService->getByCode($dataIsolation, $toolSetId);
                            $processedToolSetIds[] = $toolSetId;
                            $exportData['tool_sets'][$toolSetId] = $toolSet->toArray();
                        }

                        // handletoolprocessquote
                        if (! $toolId || in_array($toolId, $processedFlowCodes)) {
                            continue;
                        }

                        // gettoolprocess
                        $toolFlow = $this->delightfulFlowDomainService->getByCode($dataIsolation, $toolId);
                        if (! $toolFlow || $toolFlow->getType() !== Type::Tools) {
                            // toolprocessnot存inortypenotcorrect，skipbutnot报错
                            continue;
                        }

                        // mark为已handle
                        $processedFlowCodes[] = $toolId;

                        // 添加toexportdata中
                        $exportData['tool_flows'][$toolId] = $toolFlow->toArray();

                        // 递归handletoolprocess中的子process和其他tool
                        $this->processFlowForExport($dataIsolation, $toolFlow, $exportData, $processedFlowCodes, $processedToolSetIds);
                    }
                }
            }
        }
    }
}
