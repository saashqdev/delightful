<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

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
    private readonly ?string $token = null ) 
{
 $this->client = new WebSocketClient($config, $logger, $wsUrl, $taskId, $token); $this->lastMessageTime = time(); 
}
 /** * Establish WebSocket connection. * * @throws WebSocketConnectionException Thrown when connection fails */ 
    public function connect(): void 
{
 try 
{
 $this->client->connect(); $this->logger->info(sprintf( 'WebSocketJoinSuccessTaskID: %sURL: %s', $this->taskId, $this->wsUrl )); 
}
 catch (Throwable $e) 
{
 $this->logger->error(sprintf( 'WebSocketConnection failed: %sTaskID: %s', $e->getMessage(), $this->taskId )); throw $e; 
}
 
}
 /** * DisconnectWebSocketJoin. */ 
    public function disconnect(): void 
{
 try 
{
 $this->client->disconnect(); $this->logger->info(sprintf( 'WebSocketDisconnectJoinTaskID: %s', $this->taskId )); 
}
 catch (Throwable $e) 
{
 $this->logger->warning(sprintf( 'WebSocketDisconnectConnection failedTaskID: %sError: %s', $this->taskId, $e->getMessage() )); 
}
 
}
 /** * SendWebSocketMessage. * * @param array $data SendMessageData * @throws WebSocketConnectionException Send failedThrow */ 
    public function send(array $data): void 
{
 try 
{
 $this->client->send($data); $this->lastMessageTime = time(); 
}
 catch (Throwable $e) 
{
 $this->logger->error(sprintf( 'SendMessageFailed: %sTaskID: %s', $e->getMessage(), $this->taskId )); throw $e; 
}
 
}
 /** * ReceiveWebSocketMessageSupportTimeoutautomatic . * * @param int $timeout ReceiveTimeoutTimeseconds 0table UsingConfigurationDefault value * @param int $maxRetries MaximumRetry0table UsingConfigurationDefault value * @return null|array Receiveoriginal Messageor null * @throws WebSocketConnectionException Connection errorand Retry failedThrow * @throws WebSocketTimeoutException TimeoutThrow */ 
    public function receive( int $timeout = 0, int $maxRetries = 0 ): ?array 
{
 // CertainActualUsingTimeoutTime $actualTimeout = $timeout > 0 ? $timeout : $this->config->getReadTimeout(); $startTime = time(); $endTime = $startTime + $actualTimeout; // CertainRetryParameter $retryCount = 0; $maxRetries = $maxRetries > 0 ? $maxRetries : $this->config->getMaxRetries(); $baseDelay = $this->config->getRetryDelay(); // Mainloop try ReceiveMessageTimeout while (time() < $endTime) 
{
 try 
{
 // check JoinStatus if (! $this->client->isConnected()) 
{
 $this->logger->warning('WebSocket disconnected, attempting to reconnect', [ 'task_id' => $this->taskId, ]); $this->connect(); 
}
 // Set current ReceiveTimeoutTime $remainingTime = $endTime - time(); if ($remainingTime <= 0) 
{
 // Timeout return null; 
}
 // Set Timeoutcheck Status $readTimeout = min($remainingTime, 180); $this->client->setReadTimeout($readTimeout); // ReceiveMessage $rawMessage = $this->client->receive(); if ($rawMessage === null) 
{
 continue; // TimeoutContinuetry 
}
 // UpdateFinallyMessageTime $this->lastMessageTime = time(); // directly Return original Message return $rawMessage; 
}
 catch (WebSocketConnectionException $e) 
{
 ++$retryCount; if ($retryCount > $maxRetries) 
{
 $this->logger->error(sprintf( 'WebSocketConnection failedMaximumRetry: %sTaskID: %sRetry: %d', $e->getMessage(), $this->taskId, $retryCount )); throw $e; // MaximumRetryThrowException 
}
 // Calculate Delayed $delay = min($baseDelay * pow(2, $retryCount - 1), $this->config->getMaxRetryDelay()); $this->logger->warning(sprintf( 'WebSocketConnection failed%dRetryWaiting%dseconds TaskID: %sError: %s', $retryCount, $delay, $this->taskId, $e->getMessage() )); sleep($delay); 
}
 
}
 // Timeout $this->logger->warning(sprintf( 'WebSocketReceiveMessageTimeoutTaskID: %sTimeoutTime: %dseconds ', $this->taskId, $actualTimeout )); throw new WebSocketTimeoutException(sprintf( 'WebSocketReceiveMessageTimeoutTaskID: %sTimeoutTime: %dseconds ', $this->taskId, $actualTimeout )); 
}
 /** * check WebSocketJoinStatus * * @return bool Joinwhether active Status */ 
    public function isConnected(): bool 
{
 return $this->client->isConnected(); 
}
 /** * check Sessionwhether Time. * * @return bool Sessionwhether */ 
    public function isExpired(): bool 
{
 return time() - $this->lastMessageTime > $this->config->getMaxIdleTime(); 
}
 /** * Getcurrent SessionTaskID. * * @return string TaskID */ 
    public function getTaskId(): string 
{
 return $this->taskId; 
}
 /** * Getcurrent SessionToken. * * @return null|string TokenValueor null */ 
    public function getToken(): ?string 
{
 return $this->token; 
}
 
}
 
