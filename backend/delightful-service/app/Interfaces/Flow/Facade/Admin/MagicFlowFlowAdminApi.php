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
    protected DelightfulFlowAppService $magicFlowAppService;

    #[Inject]
    protected DelightfulFlowExecuteAppService $magicFlowExecuteAppService;

    #[Inject]
    protected MCPServerAppService $mcpServerAppService;

    /**
     * 获取所有节点的版本.
     */
    public function nodeVersions()
    {
        $this->getAuthorization();
        return [
            'nodes' => $this->magicFlowAppService->nodeVersions(),
        ];
    }

    /**
     * 获取节点配置模板.
     */
    public function nodeTemplate()
    {
        $this->getAuthorization();

        $nodeDTO = DelightfulFlowNodeAssembler::createNodeDTOByMixed($this->request->all());
        $nodeDO = DelightfulFlowNodeAssembler::createNodeDO($nodeDTO);

        $node = $this->magicFlowAppService->getNodeTemplate($this->getAuthorization(), $nodeDO);

        return DelightfulFlowNodeAssembler::createNodeDTO($node);
    }

    /**
     * 节点单点调试.
     */
    public function singleDebugNode()
    {
        $authorization = $this->getAuthorization();
        $nodeDTO = DelightfulFlowNodeAssembler::createNodeDTOByMixed($this->request->all());
        $nodeDO = DelightfulFlowNodeAssembler::createNodeDO($nodeDTO);
        return $this->magicFlowAppService->singleDebugNode(
            $authorization,
            $nodeDO,
            (array) $this->request->input('node_contexts', []),
            (array) $this->request->input('trigger_config', [])
        )?->toArray();
    }

    /**
     * 保存基础信息.
     */
    public function saveFlow()
    {
        $authorization = $this->getAuthorization();
        $magicFlowDTO = DelightfulFlowAssembler::createDelightfulFlowDTOByMixed($this->request->all());
        $magicFlowDO = DelightfulFlowAssembler::createDelightfulFlowDO($magicFlowDTO);

        $magicFlow = $this->magicFlowAppService->save($authorization, $magicFlowDO);
        $icons = $this->magicFlowAppService->getIcons($magicFlow->getOrganizationCode(), [$magicFlow->getIcon()]);

        return DelightfulFlowAssembler::createDelightfulFlowDTO($magicFlow, $icons);
    }

    /**
     * 试运行.
     */
    public function flowDebug(string $flowId)
    {
        $authorization = $this->getAuthorization();
        $magicFlowDTO = DelightfulFlowAssembler::createDelightfulFlowDTOByMixed($this->request->all());
        $magicFlowDO = DelightfulFlowAssembler::createDelightfulFlowDO($magicFlowDTO);
        $magicFlowDO->setCode($flowId);

        // 触发方式、触发数据
        $triggerConfig = $this->request->input('trigger_config', []);

        return $this->magicFlowExecuteAppService->testRun($authorization, $magicFlowDO, $triggerConfig);
    }

    /**
     * 查询.
     */
    public function queries()
    {
        $authorization = $this->getAuthorization();
        $params = $this->request->all();
        $query = new DelightfulFLowQuery($params);
        $query->setOrder(['updated_at' => 'desc']);
        $page = $this->createPage();
        $result = $this->magicFlowAppService->queries($authorization, $query, $page);
        return DelightfulFlowAssembler::createPageListDTO($result['total'], $result['list'], $page, $result['users'], $result['icons']);
    }

    /**
     * 查询工具.
     */
    public function queryTools()
    {
        $authorization = $this->getAuthorization();
        $params = $this->request->all();
        $query = new DelightfulFLowQuery($params);
        $result = $this->magicFlowAppService->queryTools($authorization, $query);
        return DelightfulFlowAssembler::createPageListDTO($result['total'], $result['list'], Page::createNoPage());
    }

    /**
     * 查询可用工具集.
     */
    public function queryToolSets()
    {
        $withBuiltin = (bool) $this->request->input('with_builtin', true);
        $result = $this->magicFlowAppService->queryToolSets($this->getAuthorization(), $withBuiltin);
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
     * 查询可用向量知识库.
     */
    public function queryKnowledge()
    {
        $result = $this->magicFlowAppService->queryKnowledge($this->getAuthorization());
        return DelightfulFlowKnowledgeAssembler::createPageListDTO($result['total'], $result['list'], Page::createNoPage(), $result['users']);
    }

    /**
     * 详情.
     */
    public function show(string $flowId)
    {
        $magicFlow = $this->magicFlowAppService->getByCode($this->getAuthorization(), $flowId);
        $icons = $this->magicFlowAppService->getIcons($magicFlow->getOrganizationCode(), [$magicFlow->getIcon()]);
        return DelightfulFlowAssembler::createDelightfulFlowDTO($magicFlow, $icons);
    }

    public function showParams(string $flowId)
    {
        $magicFlow = $this->magicFlowAppService->getByCode($this->getAuthorization(), $flowId);
        return DelightfulFlowAssembler::createDelightfulFlowParamsDTO($magicFlow);
    }

    /**
     * 启用/禁用.
     */
    public function changeEnable(string $flowId)
    {
        // 从请求中获取enable参数，如果没有传递则不影响原有逻辑
        $enable = $this->request->has('enable') ? (bool) $this->request->input('enable') : null;
        $this->magicFlowAppService->changeEnable($this->getAuthorization(), $flowId, $enable);
    }

    /**
     * 删除.
     */
    public function remove(string $flowId)
    {
        $this->magicFlowAppService->remove($this->getAuthorization(), $flowId);
    }

    public function expressionDataSource()
    {
        $this->getAuthorization();
        return $this->magicFlowAppService->expressionDataSource();
    }
}
