<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Flow\Assembler\Flow;

use App\Domain\Contact\Entity\DelightfulUserEntity;
use App\Domain\Flow\Entity\DelightfulFlowEntity;
use App\Domain\Flow\Entity\ValueObject\Type;
use App\Infrastructure\Core\ValueObject\Page;
use App\Interfaces\Flow\Assembler\Node\DelightfulFlowNodeAssembler;
use App\Interfaces\Flow\DTO\Flow\DelightfulFlowDTO;
use App\Interfaces\Flow\DTO\Flow\DelightfulFlowListDTO;
use App\Interfaces\Flow\DTO\Flow\DelightfulFlowParamDTO;
use App\Interfaces\Flow\DTO\Node\NodeDTO;
use App\Interfaces\Flow\DTO\Node\NodeInputDTO;
use App\Interfaces\Flow\DTO\Node\NodeOutputDTO;
use App\Interfaces\Kernel\Assembler\FileAssembler;
use App\Interfaces\Kernel\Assembler\OperatorAssembler;
use App\Interfaces\Kernel\DTO\PageDTO;
use Delightful\CloudFile\Kernel\Struct\FileLink;

class DelightfulFlowAssembler
{
    public static function createDelightfulFlowDTOByMixed(mixed $data): ?DelightfulFlowDTO
    {
        if ($data instanceof DelightfulFlowDTO) {
            return $data;
        }
        if (is_array($data)) {
            return new DelightfulFlowDTO($data);
        }
        return null;
    }

    public static function createDelightfulFlowDO(DelightfulFlowDTO $delightfulFlowDTO): DelightfulFlowEntity
    {
        $delightfulFlow = new DelightfulFlowEntity();
        $delightfulFlow->setCode((string) $delightfulFlowDTO->getId());
        $delightfulFlow->setName($delightfulFlowDTO->getName());
        $delightfulFlow->setDescription($delightfulFlowDTO->getDescription());
        $delightfulFlow->setIcon(FileAssembler::formatPath($delightfulFlowDTO->getIcon()));
        $delightfulFlow->setToolSetId($delightfulFlowDTO->getToolSetId());
        $delightfulFlow->setType(Type::from($delightfulFlowDTO->getType()));
        $delightfulFlow->setEnabled($delightfulFlowDTO->isEnabled());
        $delightfulFlow->setNodes(array_map(fn (NodeDTO $nodeDTO) => DelightfulFlowNodeAssembler::createNodeDO($nodeDTO), $delightfulFlowDTO->getNodes()));
        $delightfulFlow->setEdges($delightfulFlowDTO->getEdges());
        $delightfulFlow->setGlobalVariable($delightfulFlowDTO->getGlobalVariable());
        return $delightfulFlow;
    }

    /**
     * @param array<string,FileLink> $icons
     */
    public static function createDelightfulFlowDTO(DelightfulFlowEntity $delightfulFlowEntity, array $icons = [], array $users = []): DelightfulFlowDTO
    {
        $delightfulFlowDTO = new DelightfulFlowDTO($delightfulFlowEntity->toArray());
        $delightfulFlowDTO->setId($delightfulFlowEntity->getCode());
        $delightfulFlowDTO->setIcon(FileAssembler::getUrl($icons[$delightfulFlowEntity->getIcon()] ?? null));
        $delightfulFlowDTO->setUserOperation($delightfulFlowEntity->getUserOperation());

        $delightfulFlowDTO->setCreator($delightfulFlowEntity->getCreator());
        $delightfulFlowDTO->setCreatedAt($delightfulFlowEntity->getCreatedAt());
        $delightfulFlowDTO->setModifier($delightfulFlowEntity->getModifier());
        $delightfulFlowDTO->setUpdatedAt($delightfulFlowEntity->getUpdatedAt());
        $delightfulFlowDTO->setCreatorInfo(OperatorAssembler::createOperatorDTOByUserEntity($users[$delightfulFlowEntity->getCreator()] ?? null, $delightfulFlowEntity->getCreatedAt()));
        $delightfulFlowDTO->setModifierInfo(OperatorAssembler::createOperatorDTOByUserEntity($users[$delightfulFlowEntity->getModifier()] ?? null, $delightfulFlowEntity->getUpdatedAt()));
        return $delightfulFlowDTO;
    }

    public static function createDelightfulFlowParamsDTO(DelightfulFlowEntity $delightfulFlowEntity): DelightfulFlowParamDTO
    {
        $delightfulFlowDTO = new DelightfulFlowParamDTO($delightfulFlowEntity->toArray());
        $delightfulFlowDTO->setId($delightfulFlowEntity->getCode());

        $input = new NodeInputDTO();
        $input->setForm($delightfulFlowEntity->getInput()?->getForm());
        $input->setWidget($delightfulFlowEntity->getInput()?->getWidget());
        $delightfulFlowDTO->setInput($input);

        $output = new NodeOutputDTO();
        $output->setForm($delightfulFlowEntity->getOutput()?->getForm());
        $output->setWidget($delightfulFlowEntity->getOutput()?->getWidget());
        $delightfulFlowDTO->setOutput($output);

        return $delightfulFlowDTO;
    }

    /**
     * @param DelightfulFlowEntity[] $list
     * @param array<string,DelightfulUserEntity> $users
     * @param array<string,FileLink> $icons
     */
    public static function createPageListDTO(int $total, array $list, Page $page, array $users = [], array $icons = []): PageDTO
    {
        $list = array_map(fn (DelightfulFlowEntity $delightfulFlowEntity) => self::createDelightfulFlowListDTO($delightfulFlowEntity, $users, $icons), $list);
        return new PageDTO($page->getPage(), $total, $list);
    }

    /**
     * @param array<string,DelightfulUserEntity> $users
     * @param array<string,FileLink> $icons
     */
    protected static function createDelightfulFlowListDTO(DelightfulFlowEntity $delightfulFlowEntity, array $users = [], array $icons = []): DelightfulFlowListDTO
    {
        $delightfulFlowDTO = new DelightfulFlowListDTO($delightfulFlowEntity->toArray());
        $delightfulFlowDTO->setId($delightfulFlowEntity->getCode());
        $delightfulFlowDTO->setIcon(FileAssembler::getUrl($icons[$delightfulFlowEntity->getIcon()] ?? null));
        $delightfulFlowDTO->setCreatorInfo(OperatorAssembler::createOperatorDTOByUserEntity(
            user: $users[$delightfulFlowEntity->getCreator()] ?? null,
            dateTime: $delightfulFlowEntity->getCreatedAt()
        ));
        $delightfulFlowDTO->setModifierInfo(OperatorAssembler::createOperatorDTOByUserEntity(
            user: $users[$delightfulFlowEntity->getModifier()] ?? null,
            dateTime: $delightfulFlowEntity->getUpdatedAt()
        ));
        $delightfulFlowDTO->setUserOperation($delightfulFlowEntity->getUserOperation());

        // onlytooltime才display入参out参
        if ($delightfulFlowEntity->getType()->isTools()) {
            $input = new NodeInputDTO();
            $input->setForm($delightfulFlowEntity->getInput()?->getForm());
            $input->setWidget($delightfulFlowEntity->getInput()?->getWidget());
            $delightfulFlowDTO->setInput($input);

            $output = new NodeOutputDTO();
            $output->setForm($delightfulFlowEntity->getOutput()?->getForm());
            $output->setWidget($delightfulFlowEntity->getOutput()?->getWidget());
            $delightfulFlowDTO->setOutput($output);

            $customSystemOutput = new NodeInputDTO();
            $customSystemOutput->setForm($delightfulFlowEntity->getCustomSystemInput()?->getForm());
            $customSystemOutput->setWidget($delightfulFlowEntity->getCustomSystemInput()?->getWidget());
            $delightfulFlowDTO->setCustomSystemInput($customSystemOutput);
        }

        return $delightfulFlowDTO;
    }
}
