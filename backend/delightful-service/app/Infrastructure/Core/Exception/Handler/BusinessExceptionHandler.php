<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\Exception\Handler;

use App\Infrastructure\Core\Exception\BusinessException;
use Hyperf\Codec\Json;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Psr\Http\Message\ResponseInterface;
use stdClass;
use Swow\SocketException;
use Throwable;

class BusinessExceptionHandler extends AbstractExceptionHandler
{
    public function handle(Throwable $throwable, ResponseInterface $response): ResponseInterface
    {
        $this->stopPropagation();
        $data = Json::encode([
            'code' => $throwable->getCode() ?: 2000,
            'message' => $throwable->getMessage(),
            'data' => new stdClass(),
            'error' => [],
        ]);
        if ($throwable instanceof SocketException) {
            // tcplink正常disconnect,不need打印exception
            return $response;
        }
        $this->logger->error(sprintf(
            PHP_EOL . __CLASS__
            . PHP_EOL . 'message:%s ,'
            . PHP_EOL . 'exception_class:%s code:%s, '
            . PHP_EOL . 'file:%s, line:%s,'
            . PHP_EOL . 'trace:%s',
            $throwable->getMessage(),
            get_class($throwable),
            $throwable->getCode(),
            $throwable->getFile(),
            $throwable->getLine(),
            $throwable->getTraceAsString()
        ));
        if ($throwable->getPrevious()) {
            $this->logger->error(sprintf(
                PHP_EOL . __CLASS__
                . PHP_EOL . 'message:%s ,'
                . PHP_EOL . 'exception_class:%s code:%s, '
                . PHP_EOL . 'file:%s, line:%s,'
                . PHP_EOL . 'trace:%s',
                $throwable->getPrevious()->getMessage(),
                get_class($throwable->getPrevious()),
                $throwable->getPrevious()->getCode(),
                $throwable->getPrevious()->getFile(),
                $throwable->getPrevious()->getLine(),
                $throwable->getPrevious()->getTraceAsString()
            ));
        }

        return $response->withStatus(200)
            ->withHeader('Content-Type', 'application/json')
            ->withBody(new SwooleStream($data));
    }

    public function isValid(Throwable $throwable): bool
    {
        return $throwable instanceof BusinessException;
    }
}
