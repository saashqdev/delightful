<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Application\BeAgent\Event\Subscribe;

use Delightful\BeDelightful\Application\BeAgent\Service\AccountAppService;
use Hyperf\Amqp\Annotation\Consumer;
use Hyperf\Amqp\Message\ConsumerMessage;
use Hyperf\Amqp\Result;
use Hyperf\Contract\StdoutLoggerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Throwable;

#[Consumer(exchange: 'organization_created_message', routingKey: 'organization_created_message', queue: 'organization_created_message', nums: 1)]
class InitAccountMessageSubscriber extends ConsumerMessage
{
    public function __construct(
        private readonly AccountAppService $accountAppService,
        private readonly StdoutLoggerInterface $logger
    ) {
    }

    public function consumeMessage($event, AMQPMessage $message): Result
    {
        try {
            $this->logger->debug(sprintf(
                'Received organization created message, event: %s',
                json_encode($event, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ));

            // Extract necessary information
            $organizationCode = $event['organization_code'] ?? '';

            // Parameter validation
            if (empty($organizationCode)) {
                $this->logger->error(sprintf(
                    'Incomplete message parameters, $organizationCode: %s',
                    $organizationCode,
                ));
                return Result::ACK;
            }
            $this->accountAppService->initAccount($organizationCode);
            $this->logger->info(sprintf('Super Delightful account initialized, organization code: %s', $organizationCode));

            return Result::ACK;
        } catch (Throwable $e) {
            $this->logger->error(sprintf(
                'Failed to process Super Delightful account message: %s, event: %s',
                $e->getMessage(),
                json_encode($event, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ));

            return Result::ACK; // Acknowledge message even on error to avoid message accumulation
        }
    }
}
