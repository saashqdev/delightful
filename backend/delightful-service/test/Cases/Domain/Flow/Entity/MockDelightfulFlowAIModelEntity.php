<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace HyperfTest\Cases\Domain\Flow\Entity;

use App\Domain\Flow\Entity\DelightfulFlowAIModelEntity;
use BeDelightful\ObjectGenerator\ObjectGeneratorFactory;

class MockDelightfulFlowAIModelEntity
{
    public static function createMockDelightfulFlowAIModelEntity(string $name): ?DelightfulFlowAIModelEntity
    {
        $file = BASE_PATH . '/test/Stub/DelightfulFlowAIModelEntity/' . $name . '.json';
        if (! file_exists($file)) {
            return null;
        }
        $json = file_get_contents($file);
        $entity = new DelightfulFlowAIModelEntity();
        ObjectGeneratorFactory::object()->shouldBindJson($entity, $json);
        return $entity;
    }
}
