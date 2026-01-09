<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Agent\Assembler;

use App\Domain\Agent\Entity\DelightfulBotThirdPlatformChatEntity;
use App\Domain\Agent\Entity\ValueObject\ThirdPlatformChat\ThirdPlatformChatType;
use App\Infrastructure\Core\PageDTO;
use App\Infrastructure\Core\ValueObject\Page;
use App\Interfaces\Agent\DTO\DelightfulBotThirdPlatformChatDTO;

class DelightfulBotThirdPlatformChatAssembler
{
    public function createDO(DelightfulBotThirdPlatformChatDTO $DTO): DelightfulBotThirdPlatformChatEntity
    {
        $DO = new DelightfulBotThirdPlatformChatEntity();
        $DO->setId($DTO->getId());
        $DO->setBotId($DTO->getBotId());
        $DO->setKey($DTO->getKey());
        $DO->setType(ThirdPlatformChatType::from($DTO->getType()));
        $DO->setEnabled($DTO->isEnabled());
        $DO->setOptions($DTO->getOptions());
        $DO->setIdentification($DTO->getIdentification());
        return $DO;
    }

    public function createDTO(DelightfulBotThirdPlatformChatEntity $DO, bool $desensitize = false): DelightfulBotThirdPlatformChatDTO
    {
        $DTO = new DelightfulBotThirdPlatformChatDTO();
        $DTO->setId($DO->getId());
        $DTO->setBotId($DO->getBotId());
        $DTO->setKey($DO->getKey());
        $DTO->setType($DO->getType()->value);
        $DTO->setEnabled($DO->isEnabled());
        if ($desensitize) {
            $DTO->setOptions(array_map(function ($value) {
                if (is_string($value)) {
                    // retainfrontback 3 position,middlebetweenuse * replace,ifnotenough 6 position,thendirectly ***
                    $length = strlen($value);
                    if ($length <= 6) {
                        return '***';
                    }
                    $start = substr($value, 0, 3);
                    $end = substr($value, -3);
                    return $start . '***' . $end;
                }
                return $value;
            }, $DO->getOptions()));
        } else {
            $DTO->setOptions($DO->getOptions());
        }

        $DTO->setIdentification($DO->getIdentification());
        return $DTO;
    }

    public function createPageDTO(int $total, array $list, Page $page, bool $desensitize = false): PageDTO
    {
        $pageDTO = new PageDTO();
        $pageDTO->setTotal($total);
        $pageDTO->setList(array_map(fn (DelightfulBotThirdPlatformChatEntity $DO) => $this->createDTO($DO, $desensitize), $list));
        $pageDTO->setPage($page->getPage());
        return $pageDTO;
    }
}
