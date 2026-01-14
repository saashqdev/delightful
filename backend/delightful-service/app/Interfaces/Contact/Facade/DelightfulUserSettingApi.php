<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Contact\Facade;

use App\Application\Contact\Service\DelightfulUserSettingAppService;
use App\Domain\Contact\Entity\ValueObject\Query\DelightfulUserSettingQuery;
use App\Infrastructure\Core\AbstractApi;
use App\Interfaces\Contact\Assembler\DelightfulUserSettingAssembler;
use App\Interfaces\Contact\DTO\DelightfulUserSettingDTO;
use BeDelightful\ApiResponse\Annotation\ApiResponse;
use Hyperf\Di\Annotation\Inject;

#[ApiResponse('low_code')]
class DelightfulUserSettingApi extends AbstractApi
{
    #[Inject]
    protected DelightfulUserSettingAppService $delightfulUserSettingAppService;

    public function save()
    {
        $authorization = $this->getAuthorization();

        $dto = new DelightfulUserSettingDTO($this->request->all());
        $entity = DelightfulUserSettingAssembler::createEntity($dto);

        $savedEntity = $this->delightfulUserSettingAppService->save($authorization, $entity);

        return DelightfulUserSettingAssembler::createDTO($savedEntity);
    }

    public function get(string $key)
    {
        $authorization = $this->getAuthorization();

        $entity = $this->delightfulUserSettingAppService->get($authorization, $key);

        if (! $entity) {
            return null;
        }

        return DelightfulUserSettingAssembler::createDTO($entity);
    }

    public function queries()
    {
        $authorization = $this->getAuthorization();
        $page = $this->createPage();

        $query = new DelightfulUserSettingQuery($this->request->all());

        $result = $this->delightfulUserSettingAppService->queries($authorization, $query, $page);

        return DelightfulUserSettingAssembler::createPageListDTO(
            total: $result['total'],
            list: $result['list'],
            page: $page
        );
    }

    public function saveProjectTopicModelConfig(string $topicId)
    {
        $authorization = $this->getAuthorization();
        $model = $this->request->input('model', []);
        $imageModel = $this->request->input('image_model', []);

        $userSetting = $this->delightfulUserSettingAppService->saveProjectTopicModelConfig($authorization, $topicId, $model, $imageModel);
        return $userSetting->getValue();
    }

    public function getProjectTopicModelConfig(string $topicId)
    {
        $authorization = $this->getAuthorization();
        $userSetting = $this->delightfulUserSettingAppService->getProjectTopicModelConfig($authorization, $topicId);
        return $userSetting?->getValue() ?? [];
    }
}
