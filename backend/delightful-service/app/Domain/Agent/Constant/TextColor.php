<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Agent\Constant;

use App\ErrorCode\AgentErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;

use function Hyperf\Translation\__;

enum TextColor: string
{
    case DEFAULT = 'default';
    case GREEN = 'green';
    case ORANGE = 'orange';
    case RED = 'red';

    /**
     * fromstringgetcolorinstance.
     */
    public static function fromString(string $color): self
    {
        return match ($color) {
            self::DEFAULT->value => self::DEFAULT,
            self::GREEN->value => self::GREEN,
            self::ORANGE->value => self::ORANGE,
            self::RED->value => self::RED,
            default => ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, 'agent.interaction_command_status_color_invalid'),
        };
    }

    /**
     * get所havecoloroptionandits国际化tag.
     * @return array<string, string> returncolornameandto应value
     */
    public static function getColorOptions(): array
    {
        return [
            __('agent.text_color_default') => self::DEFAULT->value,
            __('agent.text_color_green') => self::GREEN->value,
            __('agent.text_color_orange') => self::ORANGE->value,
            __('agent.text_color_red') => self::RED->value,
        ];
    }

    /**
     * verifycolorvaluewhethervalid.
     */
    public static function isValid(string $color): bool
    {
        return in_array($color, [
            self::DEFAULT->value,
            self::GREEN->value,
            self::ORANGE->value,
            self::RED->value,
        ], true);
    }
}
