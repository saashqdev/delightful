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
     * recursionexportprocess相close所havesectionpoint,include子processandtoolprocess.
     */
    public function exportFlow(FlowDataIsolation $dataIsolation, string $flowCode): array
    {
        // get主process
        $mainFlow = $this->delightfulFlowDomainService->getByCode($dataIsolation, $flowCode);
        if (! $mainFlow) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'flow.common.not_found', ['label' => $flowCode]);
        }

        // ensureis主process
        if (! $mainFlow->getType()->isMain()) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'flow.export.not_main_flow', ['label' => $flowCode]);
        }

        // checkwhether存inloopdependency
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

        // alreadyhandleprocessencoding,preventduplicatehandle
        $processedFlowCodes = [$flowCode];
        $processedToolSetIds = [];

        // recursionhandle主processmiddle子processandtool
        $this->processFlowForExport($dataIsolation, $mainFlow, $exportData, $processedFlowCodes, $processedToolSetIds);

        return $exportData;
    }

    /**
     * importassistantprocess
     * 遇toduplicatetoolorprocesswillcreatenewinstance,andpassname区minute.
     */
    public function importFlow(FlowDataIsolation $dataIsolation, array $importData, string $agentId = ''): DelightfulFlowEntity
    {
        // import主process
        $mainFlowData = $importData['main_flow'] ?? null;
        if (! $mainFlowData) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'flow.import.missing_main_flow');
        }

        // storageageIDmappingclose系
        $idMapping = [
            'flows' => [], // 老ID => newID
            'tool_sets' => [], // 老ID => newID
            'nodes' => [], // 老ID => newID
        ];

        // import报告,recordcreate,renameanderrorinfo
        $importReport = [
            'created' => [],
            'renamed' => [],
            'errors' => [],
        ];

        // 1. 先importtoolcollection
        if (! empty($importData['tool_sets'])) {
            foreach ($importData['tool_sets'] as $toolSetId => $toolSetData) {
                try {
                    $newToolSetId = $this->importToolSet($dataIsolation, $toolSetData, $idMapping, $importReport);
                    $idMapping['tool_sets'][$toolSetId] = $newToolSetId;
                } catch (Throwable $e) {
                    $importReport['errors'][] = "importtoolcollection {$toolSetData['name']} fail: {$e->getMessage()}";
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

        // 4. mostbackimport主process,andassociatetofinger定assistant(ifprovideagentId)
        try {
            // ifprovideagentId,settingto主processdatamiddle
            if (! empty($agentId)) {
                $mainFlowData['agent_id'] = $agentId;
            }

            $newMainFlowId = $this->importSingleFlow($dataIsolation, $mainFlowData, $idMapping, $importReport);
            $idMapping['flows'][$mainFlowData['code']] = $newMainFlowId;
        } catch (Throwable $e) {
            $importReport['errors'][] = "import主process {$mainFlowData['name']} fail: {$e->getMessage()}";
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'flow.import.main_flow_failed', ['error' => $e->getMessage()]);
        }

        // 5. getandreturnimportback主process实body
        $mainFlow = $this->delightfulFlowDomainService->getByCode($dataIsolation, $newMainFlowId);
        if (! $mainFlow) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'flow.import.failed', ['label' => $newMainFlowId]);
        }
        // associateprocessandassistant
        if (! empty($agentId)) {
            $this->associateFlowWithAgent($dataIsolation, $mainFlow->getCode(), $agentId);
        }
        return $mainFlow;
    }

    /**
     * validatewhether存inloopdependency
     * use深degree优先searchdetectloopquote.
     */
    public function checkCircularDependency(FlowDataIsolation $dataIsolation, string $flowCode, array $visited = []): bool
    {
        // ifcurrentprocessalreadyinaccesspathmiddle,instructionshapebecomeloop
        if (in_array($flowCode, $visited)) {
            return true; // hair现loopdependency
        }

        // willcurrentprocessaddtoaccesspath
        $visited[] = $flowCode;

        // getprocess实body
        $flow = $this->delightfulFlowDomainService->getByCode($dataIsolation, $flowCode);
        if (! $flow) {
            return false; // processnot存in,not构becomeloop
        }

        // 遍历所havesectionpointcheckdependency
        foreach ($flow->getNodes() as $node) {
            // check子processsectionpoint
            if ($node->getNodeType() === NodeType::Sub->value) {
                $subFlowId = $node->getParams()['sub_flow_id'] ?? '';
                if ($subFlowId && $this->checkCircularDependency($dataIsolation, $subFlowId, $visited)) {
                    return true; // 子processmiddle存inloopdependency
                }
            }

            // checkLLMsectionpointmiddletoolquote
            if ($node->getNodeType() === NodeType::LLM->value || $node->getNodeType() === NodeType::Tool->value) {
                $params = $node->getParams();
                if (isset($params['option_tools']) && is_array($params['option_tools'])) {
                    foreach ($params['option_tools'] as $optionTool) {
                        $toolId = $optionTool['tool_id'] ?? '';
                        $toolSetId = $optionTool['tool_set_id'] ?? '';

                        // inside置toolskiploopdependencycheck
                        if ($toolId && ! $this->isBuiltInTool($toolId, $toolSetId) && $this->checkCircularDependency($dataIsolation, $toolId, $visited)) {
                            return true; // toolquotemiddle存inloopdependency
                        }
                    }
                }
            }
        }

        return false; // nothavedetecttoloopdependency
    }

    /**
     * exportprocessandassistantinfo
     * containprocess所havedatabyandassistant基本info.
     */
    public function exportFlowWithAgent(FlowDataIsolation $dataIsolation, string $flowCode, DelightfulAgentEntity $agent): array
    {
        // getprocessdata
        $flowData = $this->exportFlow($dataIsolation, $flowCode);

        // addassistantinfo
        $agentData = [
            'id' => $agent->getId(),
            'name' => $agent->getAgentName(),
            'description' => $agent->getAgentDescription(),
            'flow_code' => $agent->getFlowCode(),
            'avatar' => $agent->getAgentAvatar(),
            'instruct' => $agent->getInstructs(),
            // canaccording toneedaddotherassistantinfo
        ];

        return [
            'agent' => $agentData,
            'flow' => $flowData,
            'export_time' => date('Y-m-d H:i:s'),
            'export_version' => '1.0.0',
        ];
    }

    /**
     * importprocessandassistantinfo
     * fromexportdatamiddlecreatenewprocessandassistant,andestablishassociate.
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

        // 2. createnewassistantandassociateprocess
        $agentDomainService = di(DelightfulAgentDomainService::class);

        $agentEntity = new DelightfulAgentEntity();
        $agentEntity->setId('');
        $agentEntity->setAgentName($agentData['name'] ?? ('importassistant_' . date('YmdHis')));
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
            // ifcreateassistantfail,butprocessalreadyimport,仍returnprocessinfo
            return [
                'agent_id' => null,
                'agent_error' => $e->getMessage(),
                'flow_id' => $mainFlow->getCode(),
                'flow_name' => $mainFlow->getName(),
            ];
        }
    }

    /**
     * importsingleprocess
     * generatenewIDandchecknameduplicate.
     */
    private function importSingleFlow(FlowDataIsolation $dataIsolation, array $flowData, array &$idMapping, array &$importReport): string
    {
        // recordoriginalnameandID
        $originalName = $flowData['name'] ?? '';
        $originalCode = $flowData['code'] ?? '';

        // generatenewID
        $flowData['code'] = Code::DelightfulFlow->gen();

        // checkwhether存in同名process,if存inthenrename
        $flowType = isset($flowData['type']) ? Type::from($flowData['type']) : Type::Main;
        $newName = $this->generateUniqueName($dataIsolation, $originalName, $flowType);
        if ($newName !== $originalName) {
            $flowData['name'] = $newName;
            $importReport['renamed'][] = "process '{$originalName}' renamefor '{$newName}'";
        }

        // updatesectionpointIDmapping
        $this->updateNodeIdsMapping($flowData, $idMapping);

        // handletoolcollectionIDquote
        if (! empty($flowData['tool_set_id']) && $flowData['tool_set_id'] !== 'not_grouped') {
            $oldToolSetId = $flowData['tool_set_id'];
            $newToolSetId = $idMapping['tool_sets'][$oldToolSetId] ?? $oldToolSetId;
            $flowData['tool_set_id'] = $newToolSetId;
        }

        // updateprocesssectionpointmiddlequoteclose系
        $this->updateFlowReferences($flowData, $idMapping);

        // updateorganizationinfo
        $flowData['organization_code'] = $dataIsolation->getCurrentOrganizationCode();

        // 保留agentIdfield,if存in话
        $agentId = $flowData['agent_id'] ?? '';
        // createprocess实bodyandsave
        $flowEntity = DelightfulFlowFactory::arrayToEntity($flowData);
        $flowEntity->setCreator($dataIsolation->getCurrentUserId());
        $flowEntity->setOrganizationCode($dataIsolation->getCurrentOrganizationCode());

        // settingagentId(if存in)
        if (! empty($agentId)) {
            $flowEntity->setAgentId($agentId);
        }

        // ensuresettingfornew建process
        $flowEntity->setId(0); // settingIDfor0indicatenew建
        $flowEntity->setId(null);
        $savedFlow = $this->delightfulFlowDomainService->create($dataIsolation, $flowEntity);
        $importReport['created'][] = "createprocess: {$savedFlow->getName()} (ID: {$savedFlow->getCode()})";

        return $savedFlow->getCode();
    }

    /**
     * importtoolcollection
     * generatenewIDandchecknameduplicate.
     */
    private function importToolSet(FlowDataIsolation $dataIsolation, array $toolSetData, array &$idMapping, array &$importReport): string
    {
        // recordoriginalnameandID
        $originalName = $toolSetData['name'] ?? '';
        $originalCode = $toolSetData['code'] ?? '';

        // generatenewID
        $toolSetData['code'] = Code::DelightfulFlowToolSet->gen();

        // checkwhether存in同名toolcollection,if存inthenrename
        $newName = $this->generateUniqueToolSetName($dataIsolation, $originalName);
        if ($newName !== $originalName) {
            $toolSetData['name'] = $newName;
            $importReport['renamed'][] = "toolcollection '{$originalName}' renamefor '{$newName}'";
        }

        // updateorganizationinfo
        $toolSetData['organization_code'] = $dataIsolation->getCurrentOrganizationCode();
        $toolSetData['created_uid'] = $dataIsolation->getCurrentUserId();
        $toolSetData['updated_uid'] = $dataIsolation->getCurrentUserId();

        // 移exceptmaybeimpactcreate逻辑field
        unset($toolSetData['created_at'], $toolSetData['updated_at'], $toolSetData['id']);

        // settingcreate实body必wantfield
        $toolSetData['id'] = 0; // ensuresettingfornew建
        $toolSetData['created_at'] = new DateTime();
        $toolSetData['updated_at'] = new DateTime();

        // usefactorymethodcreatetoolcollection实body
        $toolSetEntity = DelightfulFlowToolSetFactory::arrayToEntity($toolSetData);

        // savetoolcollection
        $savedToolSet = $this->delightfulFlowToolSetDomainService->create($dataIsolation, $toolSetEntity);
        $importReport['created'][] = "createtoolcollection: {$savedToolSet->getName()} (ID: {$savedToolSet->getCode()})";

        // recordageIDmappingclose系
        $idMapping['tool_sets'][$originalCode] = $savedToolSet->getCode();

        return $savedToolSet->getCode();
    }

    /**
     * generate唯oneprocessname
     * whendetectto同名processo clock,add(n)back缀
     */
    private function generateUniqueName(FlowDataIsolation $dataIsolation, string $name, Type $type): string
    {
        $newName = $name;
        $counter = 1;

        // toolnotuse重名,因fortoolcollectionnotsame
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
     * generate唯onetoolcollectionname
     * whendetectto同名toolcollectiono clock,add(n)back缀
     */
    private function generateUniqueToolSetName(FlowDataIsolation $dataIsolation, string $name): string
    {
        $newName = $name;
        $counter = 1;

        // usequeryobjectcheckwhether存in同名toolcollection
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
     * updatesectionpointIDmapping
     * for所havesectionpointgeneratenewIDand维护mappingclose系.
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

            // generatenewsectionpointID
            $newNodeId = IdGenerator::getUniqueId32();
            $idMapping['nodes'][$oldNodeId] = $newNodeId;
            $nodeData['node_id'] = $newNodeId;
        }
    }

    /**
     * updateprocessmiddlequoteclose系
     * includesectionpointquote,子processquote,toolquoteetc.
     */
    private function updateFlowReferences(array &$flowData, array $idMapping): void
    {
        if (empty($flowData['nodes'])) {
            return;
        }

        foreach ($flowData['nodes'] as &$nodeData) {
            $nodeType = $nodeData['node_type'] ?? 0;

            // updatesectionpointparametermiddlequote
            if (! empty($nodeData['params'])) {
                // 子processsectionpoint
                if ($nodeType === NodeType::Sub->value) {
                    // update子processquote
                    if (isset($nodeData['params']['sub_flow_id'])) {
                        $oldSubFlowId = $nodeData['params']['sub_flow_id'];
                        $nodeData['params']['sub_flow_id'] = $idMapping['flows'][$oldSubFlowId] ?? $oldSubFlowId;
                    }
                }

                // handletype26toolsectionpoint直接quote
                if ($nodeType === NodeType::Tool->value) {
                    if (isset($nodeData['params']['tool_id'])) {
                        $oldToolId = $nodeData['params']['tool_id'];
                        $newToolId = $idMapping['flows'][$oldToolId] ?? $oldToolId;
                        $nodeData['params']['tool_id'] = $newToolId;
                    }
                }

                // toolsectionpointorLLMsectionpoint
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

                // handleparametermiddle表达type
                $this->updateExpressionReferences($nodeData['params'], $idMapping);
            }

            // 通usehandle input field (if存inandforarray)
            if (isset($nodeData['input']) && is_array($nodeData['input'])) {
                $this->processSpecialNodeFieldValue($nodeData['input'], $idMapping);
            }

            // 通usehandle output field (if存inandforarray)
            if (isset($nodeData['output']) && is_array($nodeData['output'])) {
                $this->processSpecialNodeFieldValue($nodeData['output'], $idMapping);
            }

            // updatefront置sectionpointquote
            if (isset($nodeData['prev_nodes']) && is_array($nodeData['prev_nodes'])) {
                $prevNodes = [];
                foreach ($nodeData['prev_nodes'] as $prevNodeId) {
                    $newPrevNodeId = $idMapping['nodes'][$prevNodeId] ?? $prevNodeId;
                    $prevNodes[] = $newPrevNodeId;
                }
                $nodeData['prev_nodes'] = $prevNodes;
            }

            // updateback续sectionpointquote
            if (isset($nodeData['next_nodes']) && is_array($nodeData['next_nodes'])) {
                $nextNodes = [];
                foreach ($nodeData['next_nodes'] as $nextNodeId) {
                    $newNextNodeId = $idMapping['nodes'][$nextNodeId] ?? $nextNodeId;
                    $nextNodes[] = $newNextNodeId;
                }
                $nodeData['next_nodes'] = $nextNodes;
            }
        }

        // handleedgesmiddlesourceandtargetquote
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

                // updatesourceHandlemiddlemaybecontainsectionpointIDquote
                if (isset($edge['sourceHandle']) && is_string($edge['sourceHandle'])) {
                    foreach ($idMapping['nodes'] as $oldId => $newId) {
                        // ensureoldIdisstringtype
                        $oldIdStr = (string) $oldId;
                        $newIdStr = (string) $newId;

                        // usejustthen表达typeensureonlyreplacecompleteID
                        if (preg_match('/^' . preg_quote($oldIdStr, '/') . '_/', $edge['sourceHandle'])) {
                            $edge['sourceHandle'] = preg_replace('/^' . preg_quote($oldIdStr, '/') . '/', $newIdStr, $edge['sourceHandle']);
                        }
                    }
                }

                // updateedgeID(ifhave)
                if (isset($edge['id'])) {
                    $edge['id'] = IdGenerator::getUniqueId32();
                }
            }
        }
    }

    /**
     * recursionhandlearraymiddle表达typequote
     * findandupdate所havecontainsectionpointID表达typefield.
     */
    private function updateExpressionReferences(array &$data, array $idMapping): void
    {
        foreach ($data as &$item) {
            if (is_array($item)) {
                // recursionhandle嵌setarray
                $this->updateExpressionReferences($item, $idMapping);
            } elseif (is_string($item)) {
                // skipfinger令quote(instructions.*)
                if (strpos($item, 'instructions.') === 0) {
                    continue;
                }

                // checkwhethercontainsectionpointIDquote(format如:nodeId.fieldName)
                foreach ($idMapping['nodes'] as $oldNodeId => $newNodeId) {
                    // ensureIDisstringtype
                    $oldNodeIdStr = (string) $oldNodeId;
                    $newNodeIdStr = (string) $newNodeId;

                    // usejustthen表达typeensureonlyreplacecompletesectionpointID
                    if (preg_match('/^' . preg_quote($oldNodeIdStr, '/') . '\./', $item)) {
                        $fieldName = substr($item, strlen($oldNodeIdStr));
                        $item = $newNodeIdStr . $fieldName;
                        break; // 找tomatchbackexitloop
                    }
                }
            }
        }

        // handleobjectshapetype表达typevalue(如form结构middlefield)
        if (isset($data['field'])) {
            $field = $data['field'];
            if (is_string($field)) {
                // skipfinger令quote
                if (strpos($field, 'instructions.') === 0) {
                    return;
                }

                // checkwhethercontainsectionpointIDquote
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
     * judgewhetherforinside置tool
     * inside置toolnotneed重newcreate,can直接use.
     */
    private function isBuiltInTool(string $toolId, string $toolSetId): bool
    {
        // commoninside置toolcollectionfront缀
        $builtInToolSetPrefixes = [
            'file_box',      // file盒toolcollection
            'search_engine', // searchenginetoolcollection
            'web_browse',    // webpagebrowsetoolcollection
            'system',        // systemtoolcollection
            'knowledge',     // knowledge basetoolcollection
        ];

        // judgewhether属atinside置toolcollection
        foreach ($builtInToolSetPrefixes as $prefix) {
            if ($toolSetId === $prefix || strpos($toolSetId, $prefix . '_') === 0) {
                return true;
            }
        }

        // judgetoolIDwhetherbytoolcollectionIDopenhead,thisisinside置toolcommon模type
        if (! empty($toolSetId) && strpos($toolId, $toolSetId . '_') === 0) {
            return true;
        }

        // getconfigurationmiddleinside置toolcolumn表(ifhave)
        $builtInTools = $this->config->get('flow.built_in_tools', []);
        if (in_array($toolId, $builtInTools)) {
            return true;
        }

        return false;
    }

    /**
     * associateprocessandassistant
     * inimportprocessbackwillitsandfinger定assistantassociate.
     */
    private function associateFlowWithAgent(FlowDataIsolation $dataIsolation, string $flowCode, string $agentId): void
    {
        if (empty($agentId) || empty($flowCode)) {
            return;
        }

        $agentDomainService = di(DelightfulAgentDomainService::class);
        // settingprocesscodeandsaveassistant
        $agentDomainService->associateFlowWithAgent($agentId, $flowCode);
    }

    /**
     * handle特殊表达typevaluefield.
     */
    private function processSpecialNodeFieldValue(array &$value, array $idMapping): void
    {
        foreach ($value as $key => &$item) {
            if (is_array($item)) {
                // recursionhandle嵌setarray
                $this->processSpecialNodeFieldValue($item, $idMapping);
            } elseif (is_string($item)) {
                // handlestringmiddlesectionpointIDquote
                $this->updateStringNodeReference($item, $idMapping);
            }
        }

        // 特殊handleconst_valuearraymiddleobject
        if (isset($value['const_value']) && is_array($value['const_value'])) {
            foreach ($value['const_value'] as &$constItem) {
                if (is_array($constItem)) {
                    // handleobjectshapetypeconst_valueitem
                    if (isset($constItem['value']) && is_string($constItem['value'])) {
                        $this->updateStringNodeReference($constItem['value'], $idMapping);
                    }
                    // recursionhandleotherfield
                    $this->processSpecialNodeFieldValue($constItem, $idMapping);
                } elseif (is_string($constItem)) {
                    // handlestringshapetypeconst_valueitem
                    $this->updateStringNodeReference($constItem, $idMapping);
                }
            }
        }

        // handleexpression_valuemiddlequote
        if (isset($value['expression_value']) && is_array($value['expression_value'])) {
            $this->processExpressionValue($value['expression_value'], $idMapping);
        }
    }

    /**
     * updatestringmiddlesectionpointIDquote.
     */
    private function updateStringNodeReference(string &$str, array $idMapping): void
    {
        // skipfinger令quote(instructions.*)
        if (strpos($str, 'instructions.') === 0) {
            return;
        }

        // checkwhethercontainsectionpointIDquote(format如:nodeId.fieldName)
        foreach ($idMapping['nodes'] as $oldNodeId => $newNodeId) {
            $oldNodeIdStr = (string) $oldNodeId;
            $newNodeIdStr = (string) $newNodeId;

            // usejustthen表达typeensureonlyreplacecompletesectionpointID
            if (preg_match('/^' . preg_quote($oldNodeIdStr, '/') . '\./', $str)) {
                $fieldName = substr($str, strlen($oldNodeIdStr));
                $str = $newNodeIdStr . $fieldName;
                break; // 找tomatchbackexitloop
            }
        }
    }

    /**
     * handle表达typevaluemiddlesectionpointquote.
     */
    private function processExpressionValue(array &$expressionValue, array $idMapping): void
    {
        foreach ($expressionValue as &$item) {
            if (is_array($item)) {
                // recursionhandle嵌setarray
                $this->processExpressionValue($item, $idMapping);
            } elseif (is_string($item)) {
                // handlestringmiddlesectionpointIDquote
                $this->updateStringNodeReference($item, $idMapping);
            }
        }

        // handleobjectshapetype表达typevalue(如form结构middlefield)
        if (isset($expressionValue['field'])) {
            $field = $expressionValue['field'];
            if (is_string($field)) {
                $this->updateStringNodeReference($field, $idMapping);
                $expressionValue['field'] = $field;
            }
        }

        // handle嵌setvaluefield
        if (isset($expressionValue['value']) && is_array($expressionValue['value'])) {
            $this->processExpressionValue($expressionValue['value'], $idMapping);
        }

        // handleconst_valuetype嵌set结构
        if (isset($expressionValue['const_value']) && is_array($expressionValue['const_value'])) {
            $this->processExpressionValue($expressionValue['const_value'], $idMapping);
        }

        // handleexpression_valuetype嵌set结构
        if (isset($expressionValue['expression_value']) && is_array($expressionValue['expression_value'])) {
            $this->processExpressionValue($expressionValue['expression_value'], $idMapping);
        }

        // handleform结构middlefieldarray
        if (isset($expressionValue['form']) && is_array($expressionValue['form'])) {
            foreach ($expressionValue['form'] as &$formItem) {
                if (isset($formItem['field']) && is_string($formItem['field'])) {
                    $this->updateStringNodeReference($formItem['field'], $idMapping);
                }

                // recursionhandleformItemmiddleothermaybefield
                if (is_array($formItem)) {
                    $this->updateExpressionReferences($formItem, $idMapping);
                }
            }
        }
    }

    /**
     * recursionhandleprocessmiddle子processandtoolquote.
     */
    private function processFlowForExport(
        FlowDataIsolation $dataIsolation,
        DelightfulFlowEntity $flow,
        array &$exportData,
        array &$processedFlowCodes,
        array &$processedToolSetIds
    ): void {
        // 1. handletoolcollection
        $this->processToolSet($dataIsolation, $flow, $exportData, $processedToolSetIds);

        // 2. handle子processsectionpoint
        $this->processSubFlowNodes($dataIsolation, $flow, $exportData, $processedFlowCodes, $processedToolSetIds);

        // 3. handletoolsectionpoint
        $this->processToolNodes($dataIsolation, $flow, $exportData, $processedFlowCodes, $processedToolSetIds);
    }

    /**
     * handletoolcollection.
     */
    private function processToolSet(
        FlowDataIsolation $dataIsolation,
        DelightfulFlowEntity $flow,
        array &$exportData,
        array &$processedToolSetIds
    ): void {
        $toolSetId = $flow->getToolSetId();
        // skip官方tool(not_grouped)andalreadyhandletoolcollection
        if (empty($toolSetId) || $toolSetId === 'not_grouped' || in_array($toolSetId, $processedToolSetIds)) {
            return;
        }

        // gettoolcollectioninfo
        $toolSet = $this->delightfulFlowToolSetDomainService->getByCode($dataIsolation, $toolSetId);
        // markforalreadyhandle
        $processedToolSetIds[] = $toolSetId;

        // addtoexportdatamiddle
        $exportData['tool_sets'][$toolSetId] = $toolSet->toArray();
    }

    /**
     * handle子processsectionpoint.
     */
    private function processSubFlowNodes(
        FlowDataIsolation $dataIsolation,
        DelightfulFlowEntity $flow,
        array &$exportData,
        array &$processedFlowCodes,
        array &$processedToolSetIds
    ): void {
        foreach ($flow->getNodes() as $node) {
            // ifis子processsectionpoint
            if ($node->getNodeType() === NodeType::Sub->value) {
                $subFlowId = $node->getParams()['sub_flow_id'] ?? '';
                // skipnullIDandalreadyhandle子process
                if (! $subFlowId || in_array($subFlowId, $processedFlowCodes)) {
                    continue;
                }

                // get子process
                $subFlow = $this->delightfulFlowDomainService->getByCode($dataIsolation, $subFlowId);
                if (! $subFlow || $subFlow->getType() !== Type::Sub) {
                    // 子processnot存inortypenotcorrect,skipbutnot报错
                    continue;
                }

                // markforalreadyhandle
                $processedFlowCodes[] = $subFlowId;

                // addtoexportdatamiddle
                $exportData['sub_flows'][$subFlowId] = $subFlow->toArray();

                // recursionhandle子processmiddle子processandtool
                $this->processFlowForExport($dataIsolation, $subFlow, $exportData, $processedFlowCodes, $processedToolSetIds);
            }
        }
    }

    /**
     * handletoolsectionpointandLLMsectionpointmiddleoption_tools.
     */
    private function processToolNodes(
        FlowDataIsolation $dataIsolation,
        DelightfulFlowEntity $flow,
        array &$exportData,
        array &$processedFlowCodes,
        array &$processedToolSetIds
    ): void {
        foreach ($flow->getNodes() as $node) {
            // handlesectionpointtype26(直接inparamsmiddlehavetool_idtoolsectionpoint)
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

                // markforalreadyhandle
                $processedFlowCodes[] = $toolId;

                // addtoexportdatamiddle
                $exportData['tool_flows'][$toolId] = $toolFlow->toArray();

                // recursionhandle
                $this->processFlowForExport($dataIsolation, $toolFlow, $exportData, $processedFlowCodes, $processedToolSetIds);
            }

            // maincheckLLMandToolsectionpoint
            if ($node->getNodeType() === NodeType::LLM->value || $node->getNodeType() === NodeType::Tool->value) {
                $params = $node->getParams();
                if (isset($params['option_tools']) && is_array($params['option_tools'])) {
                    foreach ($params['option_tools'] as $optionToolData) {
                        $toolId = $optionToolData['tool_id'] ?? '';
                        $toolSetId = $optionToolData['tool_set_id'] ?? '';

                        // judgewhetherforinside置tool,inside置tool直接skip
                        if ($this->isBuiltInTool($toolId, $toolSetId)) {
                            continue;
                        }

                        // handletoolcollectionquote
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
                            // toolprocessnot存inortypenotcorrect,skipbutnot报错
                            continue;
                        }

                        // markforalreadyhandle
                        $processedFlowCodes[] = $toolId;

                        // addtoexportdatamiddle
                        $exportData['tool_flows'][$toolId] = $toolFlow->toArray();

                        // recursionhandletoolprocessmiddle子processandothertool
                        $this->processFlowForExport($dataIsolation, $toolFlow, $exportData, $processedFlowCodes, $processedToolSetIds);
                    }
                }
            }
        }
    }
}
