<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\Agent\Repository\Facade;

use App\Infrastructure\Core\ValueObject\Page;
use Delightful\BeDelightful\Domain\Agent\Entity\BeDelightfulAgentEntity;
use Delightful\BeDelightful\Domain\Agent\Entity\ValueObject\Query\BeDelightfulAgentQuery;
use Delightful\BeDelightful\Domain\Agent\Entity\ValueObject\BeDelightfulAgentDataIsolation;

interface BeDelightfulAgentRepositoryInterface
{
    public function getByCode(BeDelightfulAgentDataIsolation $dataIsolation, string $code): ?BeDelightfulAgentEntity;

    /**
     * @return array{total: int, list: array<BeDelightfulAgentEntity>}
     */
    public function queries(BeDelightfulAgentDataIsolation $dataIsolation, BeDelightfulAgentQuery $query, Page $page): array;

    /**
     * 保存BeDelightful Agent.
     */
    public function save(BeDelightfulAgentDataIsolation $dataIsolation, BeDelightfulAgentEntity $entity): BeDelightfulAgentEntity;

    /**
     * 删除BeDelightful Agent.
     */
    public function delete(BeDelightfulAgentDataIsolation $dataIsolation, string $code): bool;

    /**
     * 统计指定创建者的智能体数量.
     */
    public function countByCreator(BeDelightfulAgentDataIsolation $dataIsolation, string $creator): int;
}
