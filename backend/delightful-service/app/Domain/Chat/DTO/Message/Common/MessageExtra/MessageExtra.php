<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\DTO\Message\Common\MessageExtra;

use App\Domain\Chat\DTO\Message\Common\MessageExtra\BeAgent\BeAgentExtra;
use App\Infrastructure\Core\AbstractDTO;

class MessageExtra extends AbstractDTO
{
    protected ?BeAgentExtra $superAgent;

    public function __construct(?array $data = null)
    {
        if (isset($data['be_agent'])) {
            $this->superAgent = new BeAgentExtra($data['be_agent']);
        }
        parent::__construct();
    }

    public function getBeAgent(): ?BeAgentExtra
    {
        return $this->superAgent ?? null;
    }

    public function setBeAgent(null|array|BeAgentExtra $superAgent): void
    {
        if ($superAgent instanceof BeAgentExtra) {
            $this->superAgent = $superAgent;
        } elseif (is_array($superAgent)) {
            $this->superAgent = new BeAgentExtra($superAgent);
        } else {
            $this->superAgent = null;
        }
    }
}
