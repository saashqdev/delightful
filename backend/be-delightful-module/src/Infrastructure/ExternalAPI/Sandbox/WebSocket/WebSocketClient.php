<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Infrastructure\ExternalAPI\Sandbox\WebSocket;

use Delightful\BeDelightful\Infrastructure\ExternalAPI\Sandbox\Config\WebSocketConfig;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\Sandbox\WebSocket\Exception\WebSocketConnectionException;
use Hyperf\Codec\Json;
use Psr\Log\LoggerInterface;
use Swow\Psr7\Client\Client;
use Swow\Psr7\Psr7;
use Throwable;

class WebSocketClient
{
    // WebSocket frame type constants
    private const OPCODE_PING = 9;

    private const OPCODE_PONG = 10;

    private const OPCODE_TEXT = 1;

    private const OPCODE_BINARY = 2;

    private ?Client $client = null;

    private int $lastPongTime;

    private bool $isConnected = false;

    private int $readTimeout;

    public function __construct(
        private readonly WebSocketConfig $config,
        private readonly LoggerInterface $logger,
        private readonly string $wsUrl,
        private readonly string $id,
        private readonly ?string $token = null
    ) {
        $this->lastPongTime = time();
        $this->readTimeout = $this->config->getReadTimeout();
    }

    /**
     * Establish WebSocket connection.
     *
     * @throws WebSocketConnectionException Thrown if connection fails
     */
    public function connect(): void
    {
        $urlParts = parse_url($this->wsUrl);
        if ($urlParts === false) {
            throw new WebSocketConnectionException('Invalid WebSocket URL');
        }

        $host = $urlParts['host'] ?? '';
        $port = $urlParts['port'] ?? ($urlParts['scheme'] === 'wss' ? 443 : 80);
        $path = $urlParts['path'] ?? '/';
        $query = isset($urlParts['query']) ? '?' . $urlParts['query'] : '';

        if (empty($host)) {
            throw new WebSocketConnectionException('WebSocket URL missing host address');
        }

        try {
            $this->client = new Client();
            $request = Psr7::createRequest('GET', $path . $query);

            // Add token to HTTP request header
            if ($this->token) {
                $request = $request->withHeader('token', $this->token);
            }

            $this->client->connect($host, $port);
            $this->client->upgradeToWebSocket($request);
            $this->isConnected = true;
            $this->lastPongTime = time();

            $this->logger->info(sprintf('Connected to WebSocket: %s', $this->wsUrl), [
                'id' => $this->id,
            ]);
        } catch (Throwable $e) {
            $this->isConnected = false;
            throw new WebSocketConnectionException(
                sprintf('WebSocket connection failed: %s', $e->getMessage()),
                0,
                $e
            );
        }
    }

    /**
     * Disconnect WebSocket connection.
     */
    public function disconnect(): void
    {
        /* @phpstan-ignore-next-line */
        if ($this->client !== null && method_exists($this->client, 'close')) {
            try {
                $this->client->close();
                $this->isConnected = false;
                $this->logger->info(sprintf('WebSocket connection disconnected: %s', $this->wsUrl));
            } catch (Throwable $e) {
                $this->logger->warning(sprintf('Failed to close WebSocket connection: %s', $e->getMessage()));
            }
        }
    }

    /**
     * Check if WebSocket connection is established.
     *
     * @return bool Connection status
     */
    public function isConnected(): bool
    {
        return $this->isConnected;
    }

    /**
     * Send WebSocket message.
     *
     * @param array $data Message data to send
     * @throws WebSocketConnectionException Thrown if sending fails
     */
    public function send(array $data): void
    {
        if (! $this->isConnected()) {
            throw new WebSocketConnectionException('WebSocket not connected');
        }

        try {
            $jsonData = Json::encode($data);
            $message = Psr7::createWebSocketTextMaskedFrame($jsonData);
            $this->client?->sendWebSocketFrame($message);
        } catch (Throwable $e) {
            throw new WebSocketConnectionException(
                sprintf('Failed to send message: %s', $e->getMessage()),
                0,
                $e
            );
        }
    }

    /**
     * Receive WebSocket message.
     *
     * @return null|array Received message data or null (timeout/no message)
     * @throws WebSocketConnectionException Thrown if receiving fails
     */
    public function receive(): ?array
    {
        if (! $this->isConnected()) {
            throw new WebSocketConnectionException('WebSocket not connected');
        }

        try {
            $frame = $this->client?->recvWebSocketFrame();
            if ($frame === null) {
                return null;
            }

            // Handle Pong frame
            if ($frame->getOpcode() === self::OPCODE_PONG) {
                $this->lastPongTime = time();
                $this->logger->debug(sprintf(
                    'Received Pong frame, ID: %s, Time: %s',
                    $this->id,
                    date('Y-m-d H:i:s')
                ));
                return null; // Pong frame does not need to be passed to upper layer application
            }

            // Handle Ping frame - auto respond with Pong
            if ($frame->getOpcode() === self::OPCODE_PING) {
                try {
                    $pongFrame = Psr7::createWebSocketFrame(
                        opcode: self::OPCODE_PONG,
                        payloadData: $frame->getPayloadData()->getContents(),
                        fin: true,
                        mask: true
                    );
                    $this->client?->sendWebSocketFrame($pongFrame);
                    $this->logger->debug(sprintf(
                        'Received Ping frame and auto responded with Pong, ID: %s',
                        $this->id
                    ));
                } catch (Throwable $e) {
                    $this->logger->warning('Failed to respond with Pong: ' . $e->getMessage());
                }
                return null; // Ping frame does not need to be passed to upper layer application
            }

            // Handle text message
            if ($frame->getOpcode() === self::OPCODE_TEXT) {
                $messageText = (string) $frame->getPayloadData();
                return Json::decode($messageText);
            }

            // Handle binary message
            if ($frame->getOpcode() === self::OPCODE_BINARY) {
                $this->logger->debug(sprintf(
                    'Received binary frame, length: %d, ID: %s',
                    $frame->getPayloadLength(),
                    $this->id
                ));
                // Try to parse binary data as JSON
                try {
                    $messageText = (string) $frame->getPayloadData();
                    return Json::decode($messageText);
                } catch (Throwable $e) {
                    $this->logger->warning('Cannot parse binary frame as JSON: ' . $e->getMessage());
                    return [
                        'type' => 'binary',
                        'data' => base64_encode((string) $frame->getPayloadData()),
                    ];
                }
            }

            $this->logger->debug(sprintf(
                'Received frame of unknown type, opcode: %d, ID: %s',
                $frame->getOpcode(),
                $this->id
            ));
            return null;
        } catch (Throwable $e) {
            throw new WebSocketConnectionException(
                sprintf('Failed to receive message: %s', $e->getMessage()),
                0,
                $e
            );
        }
    }

    /**
     * Send WebSocket protocol-level Ping frame.
     *
     * @return bool Whether sending was successful
     */
    public function sendPing(): bool
    {
        if (! $this->isConnected()) {
            return false;
        }

        try {
            // Create Ping frame
            $pingFrame = Psr7::createWebSocketFrame(
                opcode: self::OPCODE_PING,
                payloadData: $this->config->getHeartbeatMessage(),
                fin: true,
                mask: true
            );

            $this->client?->sendWebSocketFrame($pingFrame);
            $this->logger->debug(sprintf(
                'Sent WebSocket Ping frame, ID: %s',
                $this->id
            ));

            return true;
        } catch (Throwable $e) {
            $this->logger->warning(sprintf(
                'Failed to send Ping frame: %s, ID: %s',
                $e->getMessage(),
                $this->id
            ));
            return false;
        }
    }

    /**
     * Check if Pong response has timed out.
     *
     * @return bool Return true if timeout, otherwise false
     */
    public function isPongTimedOut(): bool
    {
        $pongTimeout = $this->config->getPongTimeout();
        return (time() - $this->lastPongTime) > $pongTimeout;
    }

    /**
     * Get the timestamp of the last received Pong frame.
     */
    public function getLastPongTime(): int
    {
        return $this->lastPongTime;
    }

    /**
     * Set read timeout.
     *
     * @param int $timeout Timeout (seconds)
     */
    public function setReadTimeout(int $timeout): void
    {
        $this->readTimeout = $timeout;
        $this->client?->setReadTimeout($timeout * 1000);
    }

    /**
     * Get the current read timeout setting.
     */
    public function getReadTimeout(): int
    {
        return $this->readTimeout;
    }
}
