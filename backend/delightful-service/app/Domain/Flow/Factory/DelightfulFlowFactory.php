<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Factory;

use App\Domain\Flow\Entity\DelightfulFlowEntity;
use App\Domain\Flow\Entity\ValueObject\ConstValue;
use App\Domain\Flow\Entity\ValueObject\Node;
use App\Domain\Flow\Entity\ValueObject\NodeInput;
use App\Domain\Flow\Entity\ValueObject\NodeOutput;
use App\Domain\Flow\Entity\ValueObject\Type;
use App\Domain\Flow\Repository\Persistence\Model\DelightfulFlowModel;
use DateTime;
use Delightful\FlowExprEngine\ComponentFactory;

class DelightfulFlowFactory
{
    public static function modelToEntity(DelightfulFlowModel $delightfulFlowModel): DelightfulFlowEntity
    {
        return self::arrayToEntity($delightfulFlowModel->toArray(), 'v0');
    }

    public static function arrayToEntity(array $delightfulFlowArray, string $defaultNodeVersion = ''): DelightfulFlowEntity
    {
        $delightfulFlowEntity = new DelightfulFlowEntity();

        $delightfulFlowEntity->setId($delightfulFlowArray['id']);
        $delightfulFlowEntity->setCode($delightfulFlowArray['code']);
        $delightfulFlowEntity->setVersionCode($delightfulFlowArray['version_code']);
        $delightfulFlowEntity->setName($delightfulFlowArray['name']);
        $delightfulFlowEntity->setDescription($delightfulFlowArray['description']);
        $delightfulFlowEntity->setIcon($delightfulFlowArray['icon'] ?? '');
        $delightfulFlowEntity->setToolSetId($delightfulFlowArray['tool_set_id'] ?? ConstValue::TOOL_SET_DEFAULT_CODE);
        $delightfulFlowEntity->setType(Type::from($delightfulFlowArray['type']));
        $delightfulFlowEntity->setEnabled($delightfulFlowArray['enabled']);
        $delightfulFlowEntity->setVersionCode($delightfulFlowArray['version_code']);
        $delightfulFlowEntity->setOrganizationCode($delightfulFlowArray['organization_code']);
        $delightfulFlowEntity->setCreator($delightfulFlowArray['created_uid'] ?? $delightfulFlowArray['creator'] ?? '');
        $delightfulFlowEntity->setCreatedAt(new DateTime($delightfulFlowArray['created_at']));
        $delightfulFlowEntity->setModifier($delightfulFlowArray['updated_uid'] ?? $delightfulFlowArray['modifier'] ?? '');
        $delightfulFlowEntity->setUpdatedAt(new DateTime($delightfulFlowArray['updated_at']));
        $delightfulFlowEntity->setEdges($delightfulFlowArray['edges'] ?? []);
        if (! empty($delightfulFlowArray['global_variable'])) {
            $delightfulFlowEntity->setGlobalVariable(ComponentFactory::fastCreate($delightfulFlowArray['global_variable']));
        }
        $nodes = [];
        foreach ($delightfulFlowArray['nodes'] ?? [] as $nodeArr) {
            if (! isset($nodeArr['node_type'])) {
                continue;
            }
            if (! isset($nodeArr['node_version']) || $nodeArr['node_version'] === '') {
                $nodeArr['node_version'] = $defaultNodeVersion;
            }
            $node = new Node($nodeArr['node_type'], $nodeArr['node_version']);
            $node->setNodeId($nodeArr['node_id']);
            $node->setDebug($nodeArr['debug'] ?? false);
            $node->setName($nodeArr['name']);
            $node->setDescription($nodeArr['description']);
            $node->setMeta($nodeArr['meta']);
            $node->setParams($nodeArr['params']);
            $node->setNextNodes($nodeArr['next_nodes']);
            $input = new NodeInput();
            $output = new NodeOutput();
            $input->setForm(ComponentFactory::fastCreate($nodeArr['input']['form'] ?? [], lazy: true));
            $output->setForm(ComponentFactory::fastCreate($nodeArr['output']['form'] ?? [], lazy: true));
            $node->setInput($input);
            $node->setOutput($output);
            $systemOutput = new NodeOutput();
            $systemOutput->setForm(ComponentFactory::fastCreate($nodeArr['system_output']['form'] ?? [], lazy: true));
            $node->setSystemOutput($systemOutput);
            // 这里除了检测还会初始化data，所以不要删除
            $node->validate();

            $nodes[] = $node;
        }

        $delightfulFlowEntity->setNodes($nodes);
        $delightfulFlowEntity->collectNodes();

        return $delightfulFlowEntity;
    }
}
