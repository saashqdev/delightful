<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

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
    private 
    const OPCODE_PING = 9; 
    private 
    const OPCODE_PONG = 10; 
    private 
    const OPCODE_TEXT = 1; 
    private 
    const OPCODE_BINARY = 2; private ?Client $client = null; 
    private int $lastPongTime; 
    private bool $isConnected = false; 
    private int $readTimeout; 
    public function __construct( 
    private readonly WebSocketConfig $config, 
    private readonly LoggerInterface $logger, 
    private readonly string $wsUrl, 
    private readonly string $id, 
    private readonly ?string $token = null ) 
{
 $this->lastPongTime = time(); $this->readTimeout = $this->config->getReadTimeout(); 
}
 /** * Establish WebSocket connection. * * @throws WebSocketConnectionException Thrown when connection fails */ 
    public function connect(): void 
{
 $urlParts = parse_url($this->wsUrl); if ($urlParts === false) 
{
 throw new WebSocketConnectionException('Invalid WebSocket URL'); 
}
 $host = $urlParts['host'] ?? ''; $port = $urlParts['port'] ?? ($urlParts['scheme'] === 'wss' ? 443 : 80); $path = $urlParts['path'] ?? '/'; $query = isset($urlParts['query']) ? '?' . $urlParts['query'] : ''; if (empty($host)) 
{
 throw new WebSocketConnectionException('WebSocket URL missing host address'); 
}
 try 
{
 $this->client = new Client(); $request = Psr7::createRequest('GET', $path . $query); // Add token to HTTP request header if ($this->token) 
{
 $request = $request->withHeader('token', $this->token); 
}
 $this->client->connect($host, $port); $this->client->upgradeToWebSocket($request); $this->isConnected = true; $this->lastPongTime = time(); $this->logger->info(sprintf('Connected to WebSocket: %s', $this->wsUrl), [ 'id' => $this->id, ]); 
}
 catch (Throwable $e) 
{
 $this->isConnected = false; throw new WebSocketConnectionException( sprintf('WebSocketConnection failed: %s', $e->getMessage()), 0, $e ); 
}
 
}
 /** * DisconnectWebSocketJoin. */ 
    public function disconnect(): void 
{
 /* @phpstan-ignore-next-line */ if ($this->client !== null && method_exists($this->client, 'close')) 
{
 try 
{
 $this->client->close(); $this->isConnected = false; $this->logger->info(sprintf('WebSocket connection disconnected: %s', $this->wsUrl)); 
}
 catch (Throwable $e) 
{
 $this->logger->warning(sprintf('CloseWebSocketConnection failed: %s', $e->getMessage())); 
}
 
}
 
}
 /** * check WebSocketJoinwhether . * * @return bool JoinStatus */ 
    public function isConnected(): bool 
{
 return $this->isConnected; 
}
 /** * SendWebSocketMessage. * * @param array $data SendMessageData * @throws WebSocketConnectionException Send failedThrow */ 
    public function send(array $data): void 
{
 if (! $this->isConnected()) 
{
 throw new WebSocketConnectionException('WebSocketDisconnected'); 
}
 try 
{
 $jsonData = Json::encode($data); $message = Psr7::createWebSocketTextMaskedFrame($jsonData); $this->client?->sendWebSocketFrame($message); 
}
 catch (Throwable $e) 
{
 throw new WebSocketConnectionException( sprintf('SendMessageFailed: %s', $e->getMessage()), 0, $e ); 
}
 
}
 /** * ReceiveWebSocketMessage. * * @return null|array ReceiveMessageDataor nullTimeout/Message * @throws WebSocketConnectionException Receive failedThrow */ 
    public function receive(): ?array 
{
 if (! $this->isConnected()) 
{
 throw new WebSocketConnectionException('WebSocketDisconnected'); 
}
 try 
{
 $frame = $this->client?->recvWebSocketFrame(); if ($frame === null) 
{
 return null; 
}
 // process Pong if ($frame->getOpcode() === self::OPCODE_PONG) 
{
 $this->lastPongTime = time(); $this->logger->debug(sprintf( 'Received PongResponseID: %sTime: %s', $this->id, date('Y-m-d H:i:s') )); return null; // Pongnot needed Passgive Apply 
}
 // process Ping - automatic ResponsePong if ($frame->getOpcode() === self::OPCODE_PING) 
{
 try 
{
 $pongFrame = Psr7::createWebSocketFrame( opcode: self::OPCODE_PONG, payloadData: $frame->getPayloadData()->getContents(), fin: true, mask: true ); $this->client?->sendWebSocketFrame($pongFrame); $this->logger->debug(sprintf( 'Received Pingautomatic ResponsePongID: %s', $this->id )); 
}
 catch (Throwable $e) 
{
 $this->logger->warning('ResponsePongFailed: ' . $e->getMessage()); 
}
 return null; // Pingnot needed Passgive Apply 
}
 // process TextMessage if ($frame->getOpcode() === self::OPCODE_TEXT) 
{
 $messageText = (string) $frame->getPayloadData(); return Json::decode($messageText); 
}
 // process Message if ($frame->getOpcode() === self::OPCODE_BINARY) 
{
 $this->logger->debug(sprintf( 'Received Length: %dID: %s', $frame->getPayloadLength(), $this->id )); // try DataParse as JSON try 
{
 $messageText = (string) $frame->getPayloadData(); return Json::decode($messageText); 
}
 catch (Throwable $e) 
{
 $this->logger->warning('Cannot parse binary frame as JSON: ' . $e->getMessage()); return [ 'type' => 'binary', 'data' => base64_encode((string) $frame->getPayloadData()), ]; 
}
 
}
 $this->logger->debug(sprintf( 'Received UnknownType: %dID: %s', $frame->getOpcode(), $this->id )); return null; 
}
 catch (Throwable $e) 
{
 throw new WebSocketConnectionException( sprintf('ReceiveMessageFailed: %s', $e->getMessage()), 0, $e ); 
}
 
}
 /** * SendWebSocketPing. * * @return bool Sendwhether Success */ 
    public function sendPing(): bool 
{
 if (! $this->isConnected()) 
{
 return false; 
}
 try 
{
 // CreatePing $pingFrame = Psr7::createWebSocketFrame( opcode: self::OPCODE_PING, payloadData: $this->config->getHeartbeatMessage(), fin: true, mask: true ); $this->client?->sendWebSocketFrame($pingFrame); $this->logger->debug(sprintf( 'SendWebSocket PingID: %s', $this->id )); return true; 
}
 catch (Throwable $e) 
{
 $this->logger->warning(sprintf( 'SendPingFailed: %sID: %s', $e->getMessage(), $this->id )); return false; 
}
 
}
 /** * check PongResponsewhether Timeout. * * @return bool IfTimeoutReturn trueOtherwiseReturn false */ 
    public function isPongTimedOut(): bool 
{
 $pongTimeout = $this->config->getPongTimeout(); return (time() - $this->lastPongTime) > $pongTimeout; 
}
 /** * GetFinallyonce Received PongTimestamp. */ 
    public function getLastPongTime(): int 
{
 return $this->lastPongTime; 
}
 /** * Set TimeoutTime. * * @param int $timeout TimeoutTimeseconds  */ 
    public function setReadTimeout(int $timeout): void 
{
 $this->readTimeout = $timeout; $this->client?->setReadTimeout($timeout * 1000); 
}
 /** * Getcurrent Set TimeoutTime. */ 
    public function getReadTimeout(): int 
{
 return $this->readTimeout; 
}
 
}
 
