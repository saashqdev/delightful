<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Flow\Event\Subscribe;

use App\Domain\Flow\Entity\ValueObject\FlowDataIsolation;
use App\Domain\Flow\Event\DelightfulFlowPublishedEvent;
use App\Domain\Flow\Service\DelightfulFlowDomainService;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Psr\Container\ContainerInterface;

#[Listener]
readonly class DelightfulFlowCreateRoutineSubscriber implements ListenerInterface
{
    public function __construct(private ContainerInterface $container)
    {
    }

    public function listen(): array
    {
        return [
            DelightfulFlowPublishedEvent::class,
        ];
    }

    public function process(object $event): void
    {
        if (! $event instanceof DelightfulFlowPublishedEvent) {
            return;
        }
        $delightfulFlow = $event->delightfulFlowEntity;

        $this->container->get(DelightfulFlowDomainService::class)->createRoutine(FlowDataIsolation::create(), $delightfulFlow);
    }
}
