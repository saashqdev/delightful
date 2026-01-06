<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\KnowledgeBase\Facade;

use App\Interfaces\Flow\Facade\Open\AbstractOpenApi;
use Delightful\ApiResponse\Annotation\ApiResponse;

#[ApiResponse(version: 'low_code')]
class KnowledgeBaseOpenApi extends AbstractOpenApi
{
    //    #[Inject]
    //    protected KnowledgeBaseAppService $knowledgeBaseAppService;
    //
    //    #[Inject]
    //    protected KnowledgeBaseFragmentAppService $knowledgeBaseFragmentAppService;
    //
    //    public function save()
    //    {
    //        $authorization = $this->getAuthorization();
    //        $dto = new DelightfulFlowKnowledgeDTO($this->request->all());
    //
    //        $delightfulFlowKnowledgeDO = DelightfulFlowKnowledgeAssembler::creatDO($dto);
    //        $delightfulFlowKnowledgeEntity = $this->knowledgeBaseAppService->save($authorization, $delightfulFlowKnowledgeDO);
    //        return DelightfulFlowKnowledgeAssembler::createDTO($delightfulFlowKnowledgeEntity);
    //    }
    //
    //    public function saveProcess()
    //    {
    //        $authorization = $this->getAuthorization();
    //        $dto = new DelightfulFlowKnowledgeDTO($this->request->all());
    //
    //        $delightfulFlowKnowledgeDO = DelightfulFlowKnowledgeAssembler::creatDO($dto);
    //        $delightfulFlowKnowledgeEntity = $this->knowledgeBaseAppService->saveProcess($authorization, $delightfulFlowKnowledgeDO);
    //        return DelightfulFlowKnowledgeAssembler::createDTO($delightfulFlowKnowledgeEntity);
    //    }
    //
    //    public function queries()
    //    {
    //        $authorization = $this->getAuthorization();
    //        $params = $this->request->all();
    //        $query = new KnowledgeBaseQuery($params);
    //        $query->setOrder(['updated_at' => 'desc']);
    //        $query->setTypes(KnowledgeType::openListValue());
    //        $page = $this->createPage();
    //        $result = $this->knowledgeBaseAppService->queries($authorization, $query, $page);
    //        return DelightfulFlowKnowledgeAssembler::createPageListDTO($result['total'], $result['list'], $page, $result['users']);
    //    }
    //
    //    public function showByBusinessId()
    //    {
    //        $type = $this->request->input('type');
    //        if (! is_null($type)) {
    //            $type = (int) $type;
    //        }
    //        $businessId = (string) $this->request->input('business_id');
    //        $delightfulFlowKnowledgeEntity = $this->knowledgeBaseAppService->getByBusinessId($this->getAuthorization(), $businessId, $type);
    //        if (! $delightfulFlowKnowledgeEntity) {
    //            return null;
    //        }
    //        return DelightfulFlowKnowledgeAssembler::createDTO($delightfulFlowKnowledgeEntity);
    //    }
    //
    //    public function show(string $id)
    //    {
    //        $delightfulFlowKnowledgeEntity = $this->knowledgeBaseAppService->show($this->getAuthorization(), $id);
    //        return DelightfulFlowKnowledgeAssembler::createDTO($delightfulFlowKnowledgeEntity);
    //    }
    //
    //    public function destroy(string $id)
    //    {
    //        $this->knowledgeBaseAppService->destroy($this->getAuthorization(), $id);
    //    }
    //
    //    public function rebuild(string $id)
    //    {
    //        $this->knowledgeBaseAppService->rebuild($this->getAuthorization(), $id, (bool) $this->request->input('force', false));
    //    }
    //
    //    public function similarity()
    //    {
    //        $authorization = $this->getAuthorization();
    //        $knowledgeSimilarity = new KnowledgeSimilarityFilter($this->request->all());
    //
    //        $result = $this->knowledgeBaseAppService->similarity($authorization, $knowledgeSimilarity);
    //        return DelightfulFlowKnowledgeFragmentAssembler::createPageListDTO(count($result), $result, new Page(1, count($result)));
    //    }
    //
    //    public function fragmentSave()
    //    {
    //        $authorization = $this->getAuthorization();
    //        $dto = new DelightfulFlowKnowledgeFragmentDTO($this->request->all());
    //
    //        $DO = DelightfulFlowKnowledgeFragmentAssembler::createDO($dto);
    //        $entity = $this->knowledgeBaseAppService->fragmentSave($authorization, $DO);
    //        return DelightfulFlowKnowledgeFragmentAssembler::createDTO($entity);
    //    }
    //
    //    public function fragmentQueries()
    //    {
    //        $authorization = $this->getAuthorization();
    //        $query = new KnowledgeBaseFragmentQuery($this->request->all());
    //
    //        $query->setOrder(['updated_at' => 'desc']);
    //        $page = $this->createPage();
    //        $result = $this->knowledgeBaseFragmentAppService->queries($authorization, $query, $page);
    //        return DelightfulFlowKnowledgeFragmentAssembler::createPageListDTO($result['total'], $result['list'], $page);
    //    }
    //
    //    public function fragmentShow(string $id)
    //    {
    //        $entity = $this->knowledgeBaseFragmentAppService->fragmentShow($this->getAuthorization(), (int) $id);
    //        return DelightfulFlowKnowledgeFragmentAssembler::createDTO($entity);
    //    }
    //
    //    public function fragmentDestroyByMetadataFilter()
    //    {
    //        $knowledgeCode = $this->request->input('knowledge_code');
    //        $metadataFilter = $this->request->input('metadata_filter');
    //        $this->knowledgeBaseFragmentAppService->fragmentDestroyByMetadataFilter($this->getAuthorization(), $knowledgeCode, $metadataFilter);
    //    }
    //
    //    public function fragmentDestroy(string $id)
    //    {
    //        $this->knowledgeBaseFragmentAppService->destroy($this->getAuthorization(), (int) $id);
    //    }
}
