<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Provider\Event\Subscribe;

use App\Application\Provider\Service\ProviderModelSyncAppService;
use App\Domain\Provider\Event\ProviderConfigCreatedEvent;
use App\Domain\Provider\Event\ProviderConfigUpdatedEvent;
use Delightful\AsyncEvent\Kernel\Annotation\AsyncListener;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Logger\LoggerFactory;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * 同modeltoOfficialservice商listen器.
 * listenservice商configurationcreate/updateevent，from外部APIpullmodel并同toOfficialservice商.
 */
#[AsyncListener]
#[Listener]
class SyncModelsToOfficialListener implements ListenerInterface
{
    private LoggerInterface $logger;

    public function __construct(
        private ContainerInterface $container
    ) {
        $this->logger = $this->container->get(LoggerFactory::class)->get('ProviderModelSync');
    }

    public function listen(): array
    {
        return [
            ProviderConfigCreatedEvent::class,
            ProviderConfigUpdatedEvent::class,
        ];
    }

    public function process(object $event): void
    {
        try {
            $syncService = $this->container->get(ProviderModelSyncAppService::class);

            match (true) {
                $event instanceof ProviderConfigCreatedEvent => $this->handleProviderConfig($event, $syncService, 'created'),
                $event instanceof ProviderConfigUpdatedEvent => $this->handleProviderConfig($event, $syncService, 'updated'),
                default => null,
            };
        } catch (Throwable $e) {
            $this->logger->error('from外部API同modelfailed', [
                'event' => get_class($event),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * processservice商configurationcreateorupdateevent.
     * if是Officialservice商and是官方organization，thenfrom外部APIpullmodel并同.
     */
    private function handleProviderConfig(
        ProviderConfigCreatedEvent|ProviderConfigUpdatedEvent $event,
        ProviderModelSyncAppService $syncService,
        string $action
    ): void {
        $this->logger->info("收toservice商configuration{$action}event", [
            'config_id' => $event->providerConfigEntity->getId(),
            'organization_code' => $event->organizationCode,
            'action' => $action,
        ]);

        $syncService->syncModelsFromExternalApi(
            $event->providerConfigEntity,
            $event->language,
            $event->organizationCode
        );
    }
}
