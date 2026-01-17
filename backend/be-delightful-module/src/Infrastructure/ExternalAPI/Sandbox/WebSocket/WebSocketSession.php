<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Infrastructure\ExternalAPI\Sandbox\WebSocket;

use Delightful\BeDelightful\Infrastructure\ExternalAPI\Sandbox\Config\WebSocketConfig;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\Sandbox\WebSocket\Exception\WebSocketConnectionException;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\Sandbox\WebSocket\Exception\WebSocketTimeoutException;
use Psr\Log\LoggerInterface;
use Throwable;

class WebSocketSession
{
    private WebSocketClient $client;

    private int $lastMessageTime;

    public function __construct(
        private readonly WebSocketConfig $config,
        private readonly LoggerInterface $logger,
        private readonly string $wsUrl,
        private readonly string $taskId,
        private readonly ?string $token = null
    ) {
        $this->client = new WebSocketClient($config, $logger, $wsUrl, $taskId, $token);
        $this->lastMessageTime = time();
    }

    /**
     * Establish WebSocket connection.
     *
     * @throws WebSocketConnectionException Thrown if connection fails
     */
    public function connect(): void
    {
        try {
            $this->client->connect();
            $this->logger->info(sprintf(
                'WebSocket connection successful, task ID: %s, URL: %s',
                $this->taskId,
                $this->wsUrl
            ));
        } catch (Throwable $e) {
            $this->logger->error(sprintf(
                'WebSocket connection failed: %s, task ID: %s',
                $e->getMessage(),
                $this->taskId
            ));
            throw $e;
        }
    }

    /**
     * Disconnect WebSocket connection.
     */
    public function disconnect(): void
    {
        try {
            $this->client->disconnect();
            $this->logger->info(sprintf(
                'WebSocket disconnected, task ID: %s',
                $this->taskId
            ));
        } catch (Throwable $e) {
            $this->logger->warning(sprintf(
                'Failed to disconnect WebSocket, task ID: %s, error: %s',
                $this->taskId,
                $e->getMessage()
            ));
        }
    }

    /**
     * Send WebSocket message.
     *
     * @param array $data Message data to send
     * @throws WebSocketConnectionException Thrown if sending fails
     */
    public function send(array $data): void
    {
        try {
            $this->client->send($data);
            $this->lastMessageTime = time();
        } catch (Throwable $e) {
            $this->logger->error(sprintf(
                'Failed to send message: %s, task ID: %s',
                $e->getMessage(),
                $this->taskId
            ));
            throw $e;
        }
    }

    /**
     * Receive WebSocket message with timeout and auto-reconnect support.
     *
     * @param int $timeout Receive timeout (seconds), 0 uses config default value
     * @param int $maxRetries Max retry count (0 uses config default value)
     * @return null|array Received raw message or null
     * @throws WebSocketConnectionException Thrown if connection error and retry fails
     * @throws WebSocketTimeoutException Thrown on operation timeout
     */
    public function receive(
        int $timeout = 0,
        int $maxRetries = 0
    ): ?array {
        // Determine actual timeout to use
        $actualTimeout = $timeout > 0 ? $timeout : $this->config->getReadTimeout();
        $startTime = time();
        $endTime = $startTime + $actualTimeout;

        // Determine retry parameters
        $retryCount = 0;
        $maxRetries = $maxRetries > 0 ? $maxRetries : $this->config->getMaxRetries();
        $baseDelay = $this->config->getRetryDelay();

        // Main loop: try to receive messages until timeout
        while (time() < $endTime) {
            try {
                // Check connection status
                if (! $this->client->isConnected()) {
                    $this->logger->warning('WebSocket not connected, attempting to reconnect', [
                        'task_id' => $this->taskId,
                    ]);
                    $this->connect();
                }

                // Set the timeout for current receive operation
                $remainingTime = $endTime - time();
                if ($remainingTime <= 0) {
                    // Total operation timeout
                    return null;
                }

                // Set shorter read timeout to check status periodically
                $readTimeout = min($remainingTime, 180);
                $this->client->setReadTimeout($readTimeout);

                // Receive message
                $rawMessage = $this->client->receive();
                if ($rawMessage === null) {
                    continue; // Current read timeout, continue trying
                }

                // Update last message time
                $this->lastMessageTime = time();

                // Directly return raw message
                return $rawMessage;
            } catch (WebSocketConnectionException $e) {
                ++$retryCount;
                if ($retryCount > $maxRetries) {
                    $this->logger->error(sprintf(
                        'WebSocket connection failed and exceeded max retries: %s, task ID: %s, retry count: %d',
                        $e->getMessage(),
                        $this->taskId,
                        $retryCount
                    ));
                    throw $e; // Exceeded max retries, throw exception
                }

                // Calculate exponential backoff delay
                $delay = min($baseDelay * pow(2, $retryCount - 1), $this->config->getMaxRetryDelay());
                $this->logger->warning(sprintf(
                    'WebSocket connection failed, attempt %d retry, waiting %d seconds, task ID: %s, error: %s',
                    $retryCount,
                    $delay,
                    $this->taskId,
                    $e->getMessage()
                ));

                sleep($delay);
            }
        }

        // Operation timeout
        $this->logger->warning(sprintf(
            'WebSocket receive message timeout, task ID: %s, timeout: %d seconds',
            $this->taskId,
            $actualTimeout
        ));

        throw new WebSocketTimeoutException(sprintf(
            'WebSocket receive message timeout, task ID: %s, timeout: %d seconds',
            $this->taskId,
            $actualTimeout
        ));
    }

    /**
     * Check WebSocket connection status
     *
     * @return bool Whether connection is active
     */
    public function isConnected(): bool
    {
        return $this->client->isConnected();
    }

    /**
     * Check if session has expired (inactive for long time).
     *
     * @return bool Whether session has expired
     */
    public function isExpired(): bool
    {
        return time() - $this->lastMessageTime > $this->config->getMaxIdleTime();
    }

    /**
     * Get task ID of current session.
     *
     * @return string Task ID
     */
    public function getTaskId(): string
    {
        return $this->taskId;
    }

    /**
     * Get token of current session.
     *
     * @return null|string Token value or null
     */
    public function getToken(): ?string
    {
        return $this->token;
    }
}
