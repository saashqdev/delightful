<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Flow\Facade\Admin;

use App\Application\Flow\Service\DelightfulFlowAppService;
use App\Application\Flow\Service\DelightfulFlowExecuteAppService;
use App\Application\MCP\Service\MCPServerAppService;
use App\Domain\Flow\Entity\ValueObject\Query\DelightfulFLowQuery;
use App\Domain\MCP\Entity\ValueObject\Query\MCPServerQuery;
use App\Infrastructure\Core\ValueObject\Page;
use App\Interfaces\Flow\Assembler\Flow\DelightfulFlowAssembler;
use App\Interfaces\Flow\Assembler\Knowledge\DelightfulFlowKnowledgeAssembler;
use App\Interfaces\Flow\Assembler\Node\DelightfulFlowNodeAssembler;
use App\Interfaces\Flow\Assembler\ToolSet\DelightfulFlowToolSetAssembler;
use App\Interfaces\MCP\Assembler\MCPServerAssembler;
use Delightful\ApiResponse\Annotation\ApiResponse;
use Hyperf\Di\Annotation\Inject;

#[ApiResponse(version: 'low_code')]
class DelightfulFlowFlowAdminApi extends AbstractFlowAdminApi
{
    #[Inject]
    protected DelightfulFlowAppService $delightfulFlowAppService;

    #[Inject]
    protected DelightfulFlowExecuteAppService $delightfulFlowExecuteAppService;

    #[Inject]
    protected MCPServerAppService $mcpServerAppService;

    /**
     * get所havesectionpointversion.
     */
    public function nodeVersions()
    {
        $this->getAuthorization();
        return [
            'nodes' => $this->delightfulFlowAppService->nodeVersions(),
        ];
    }

    /**
     * getsectionpointconfigurationtemplate.
     */
    public function nodeTemplate()
    {
        $this->getAuthorization();

        $nodeDTO = DelightfulFlowNodeAssembler::createNodeDTOByMixed($this->request->all());
        $nodeDO = DelightfulFlowNodeAssembler::createNodeDO($nodeDTO);

        $node = $this->delightfulFlowAppService->getNodeTemplate($this->getAuthorization(), $nodeDO);

        return DelightfulFlowNodeAssembler::createNodeDTO($node);
    }

    /**
     * sectionpointsinglepointdebug.
     */
    public function singleDebugNode()
    {
        $authorization = $this->getAuthorization();
        $nodeDTO = DelightfulFlowNodeAssembler::createNodeDTOByMixed($this->request->all());
        $nodeDO = DelightfulFlowNodeAssembler::createNodeDO($nodeDTO);
        return $this->delightfulFlowAppService->singleDebugNode(
            $authorization,
            $nodeDO,
            (array) $this->request->input('node_contexts', []),
            (array) $this->request->input('trigger_config', [])
        )?->toArray();
    }

    /**
     * savefoundationinformation.
     */
    public function saveFlow()
    {
        $authorization = $this->getAuthorization();
        $delightfulFlowDTO = DelightfulFlowAssembler::createDelightfulFlowDTOByMixed($this->request->all());
        $delightfulFlowDO = DelightfulFlowAssembler::createDelightfulFlowDO($delightfulFlowDTO);

        $delightfulFlow = $this->delightfulFlowAppService->save($authorization, $delightfulFlowDO);
        $icons = $this->delightfulFlowAppService->getIcons($delightfulFlow->getOrganizationCode(), [$delightfulFlow->getIcon()]);

        return DelightfulFlowAssembler::createDelightfulFlowDTO($delightfulFlow, $icons);
    }

    /**
     * trial operationline.
     */
    public function flowDebug(string $flowId)
    {
        $authorization = $this->getAuthorization();
        $delightfulFlowDTO = DelightfulFlowAssembler::createDelightfulFlowDTOByMixed($this->request->all());
        $delightfulFlowDO = DelightfulFlowAssembler::createDelightfulFlowDO($delightfulFlowDTO);
        $delightfulFlowDO->setCode($flowId);

        // 触hairmethod,触hairdata
        $triggerConfig = $this->request->input('trigger_config', []);

        return $this->delightfulFlowExecuteAppService->testRun($authorization, $delightfulFlowDO, $triggerConfig);
    }

    /**
     * query.
     */
    public function queries()
    {
        $authorization = $this->getAuthorization();
        $params = $this->request->all();
        $query = new DelightfulFLowQuery($params);
        $query->setOrder(['updated_at' => 'desc']);
        $page = $this->createPage();
        $result = $this->delightfulFlowAppService->queries($authorization, $query, $page);
        return DelightfulFlowAssembler::createPageListDTO($result['total'], $result['list'], $page, $result['users'], $result['icons']);
    }

    /**
     * querytool.
     */
    public function queryTools()
    {
        $authorization = $this->getAuthorization();
        $params = $this->request->all();
        $query = new DelightfulFLowQuery($params);
        $result = $this->delightfulFlowAppService->queryTools($authorization, $query);
        return DelightfulFlowAssembler::createPageListDTO($result['total'], $result['list'], Page::createNoPage());
    }

    /**
     * querycanusetoolcollection.
     */
    public function queryToolSets()
    {
        $withBuiltin = (bool) $this->request->input('with_builtin', true);
        $result = $this->delightfulFlowAppService->queryToolSets($this->getAuthorization(), $withBuiltin);
        return DelightfulFlowToolSetAssembler::createPageListDTO($result['total'], $result['list'], Page::createNoPage(), $result['users'], $result['icons']);
    }

    public function queryMCPList()
    {
        $authorization = $this->getAuthorization();
        $page = $this->createPage();

        $office = (bool) $this->request->input('office', false);

        $query = new MCPServerQuery($this->request->all());
        $query->setOrder(['id' => 'desc']);
        $query->setEnabled(true);
        $result = $this->mcpServerAppService->availableQueries($authorization, $query, $page, $office);

        return MCPServerAssembler::createSelectPageListDTO(
            total: $result['total'],
            list: $result['list'],
            page: $page,
            icons: $result['icons'],
        );
    }

    /**
     * querycanusetoquantityknowledge base.
     */
    public function queryKnowledge()
    {
        $result = $this->delightfulFlowAppService->queryKnowledge($this->getAuthorization());
        return DelightfulFlowKnowledgeAssembler::createPageListDTO($result['total'], $result['list'], Page::createNoPage(), $result['users']);
    }

    /**
     * detail.
     */
    public function show(string $flowId)
    {
        $delightfulFlow = $this->delightfulFlowAppService->getByCode($this->getAuthorization(), $flowId);
        $icons = $this->delightfulFlowAppService->getIcons($delightfulFlow->getOrganizationCode(), [$delightfulFlow->getIcon()]);
        return DelightfulFlowAssembler::createDelightfulFlowDTO($delightfulFlow, $icons);
    }

    public function showParams(string $flowId)
    {
        $delightfulFlow = $this->delightfulFlowAppService->getByCode($this->getAuthorization(), $flowId);
        return DelightfulFlowAssembler::createDelightfulFlowParamsDTO($delightfulFlow);
    }

    /**
     * enable/disable.
     */
    public function changeEnable(string $flowId)
    {
        // fromrequestmiddlegetenableparameter,ifnothave传递thennotimpact原havelogic
        $enable = $this->request->has('enable') ? (bool) $this->request->input('enable') : null;
        $this->delightfulFlowAppService->changeEnable($this->getAuthorization(), $flowId, $enable);
    }

    /**
     * delete.
     */
    public function remove(string $flowId)
    {
        $this->delightfulFlowAppService->remove($this->getAuthorization(), $flowId);
    }

    public function expressionDataSource()
    {
        $this->getAuthorization();
        return $this->delightfulFlowAppService->expressionDataSource();
    }
}
