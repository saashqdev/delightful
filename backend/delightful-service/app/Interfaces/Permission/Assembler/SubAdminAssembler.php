<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Permission\Assembler;

use App\Domain\Permission\Entity\RoleEntity;
use App\Infrastructure\Core\ValueObject\Page;
use App\Interfaces\Kernel\DTO\PageDTO;

class SubAdminAssembler
{
    /**
     * will RoleEntity convertforarrayformat，供front端use。
     */
    public static function toArray(RoleEntity $entity): array
    {
        return [
            'id' => (string) $entity->getId(),
            'name' => $entity->getName(),
            'status' => $entity->getStatus(),
            'permission_tag' => $entity->getPermissionTag(),
            'permissions' => $entity->getPermissions(),
            'user_ids' => $entity->getUserIds(),
            'updated_uid' => $entity->getUpdatedUid(),
            'created_at' => $entity->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updated_at' => $entity->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * buildpagination DTO。
     *
     * @param RoleEntity[] $list
     */
    public static function createPageListDTO(int $total, array $list, Page $page): PageDTO
    {
        $listArray = array_map(static fn (RoleEntity $entity) => self::toArray($entity), $list);
        return new PageDTO($page->getPage(), $total, $listArray);
    }

    public static function assembleWithUserInfo(RoleEntity $entity, array $userInfoList, array $updatedUser = []): array
    {
        $data = self::toArray($entity);
        $data['users'] = $userInfoList; // user详细infocolumntable
        $data['updated_user'] = $updatedUser;
        return $data;
    }
}
